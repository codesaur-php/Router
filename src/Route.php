<?php

namespace codesaur\Router;

/**
 * Class Route
 *
 * Жижиг value object - `Router::__call()`-ийн буцаах утга. Route бүртгэгдэх
 * үед үүсгэгдэж, тэр route-д нэр оноох (`name()`) гэх мэт fluent API-г олгоно.
 *
 * Энэ class нь Router-ийг **stateless** байлгахын тулд оршино:
 * - Бүртгэсэн route нь өөрийн pattern context-ыг агуулсан тул `name()`,
 *    `middleware()` зэрэг fluent method-ууд зөрчилгүйгээр ажиллана
 * - PHP 8.2 `readonly` class - immutable
 *
 * Хэрэглээ:
 *   $router->GET('/news/{int:id}', $handler)->name('news.view');
 *   //                                  ^ Route object буцаана
 *
 *   $router->POST('/users', [UsersController::class, 'create'])
 *       ->middleware([CsrfMiddleware::class, AuthMiddleware::class])
 *       ->name('users.create');
 *   //   ^ name() болон middleware() аль алийг нэгэн зэрэг chain хийж болно
 *
 *   $router->GET('/admin', [AdminController::class, 'dashboard'])
 *       ->middleware([AuthMiddleware::class])
 *       ->middleware([AdminOnlyMiddleware::class]);
 *   //   ^ Append semantics - олон удаа дуудвал middleware жагсаалт цуглуулагдана
 *
 * @package codesaur\Router
 */
final readonly class Route
{
    /**
     * @param Router $router Эх Router instance - name болон middleware registration-д ашиглагдана
     * @param string $pattern Энэ route-ын pattern (immutable)
     * @param string $method  HTTP method бүртгэлд ашигласан string (compound 'GET_POST' дэмжинэ).
     *                        middleware() энэ method-ийн scope-д л middleware-ийг нааж бүртгэнэ.
     */
    public function __construct(
        private Router $router,
        public string $pattern,
        public string $method,
    ) {}

    /**
     * Энэ маршрутад нэр оноох (reverse routing-д ашиглагдана).
     *
     * Жишээ:
     *   $router->GET('/news/{int:id}', ...)->name('news.view');
     *   $url = $router->generate('news.view', ['id' => 10]); // -> /news/10
     *
     * @param string $ruleName Route name (уникаль байх ёстой)
     * @return self Method chaining-д зориулж энэ Route объектыг буцаана
     */
    public function name(string $ruleName): self
    {
        $this->router->registerName($ruleName, $this->pattern);
        return $this;
    }

    /**
     * Энэ маршрутад middleware жагсаалт хавсаргах.
     *
     * Scope нь **(pattern + method)** хосоор хязгаарлагдана - method-нь scope-той,
     * Laravel, Express, Slim, Fastify зэрэг mainstream router-уудтай нийцэж байна:
     *
     *   $router->GET('/api/users', $list);                               // middleware алга
     *   $router->POST('/api/users', $create)->middleware([Auth::class]); // зөвхөн POST-д
     *
     *   $router->match('/api/users', 'GET');   // [2] -> []
     *   $router->match('/api/users', 'POST');  // [2] -> [Auth::class]
     *
     * Middleware-уудыг олон төрлөөр зааж болно:
     * - **class-string** - PSR-15 MiddlewareInterface хэрэгжүүлсэн класс,
     *     HTTP-Application нь runtime-д инстанс үүсгэнэ
     * - **callable / Closure** - `function($request, $handler)` гарын үсэг
     * - **MiddlewareInterface instance** - урьдчилан үүсгэсэн object
     *
     * Жишээ:
     *   $router->POST('/users', $handler)
     *       ->middleware([
     *           CsrfMiddleware::class,
     *           [AuthMiddleware::class, 'admin'],   // factory-style
     *           function($req, $handler) { ... },   // inline closure
     *       ]);
     *
     * Append semantics - хэд хэдэн `->middleware([...])` chain дуудвал
     * middleware-ууд цуглуулагдана:
     *   $router->GET('/x', $cb)
     *       ->middleware([A::class])
     *       ->middleware([B::class]);
     *   // Бүртгэгдсэн middleware: [A::class, B::class]
     *
     * Compound method (GET_POST) ашигласан бол middleware нь доорх method бүрд
     * шилжиж бүртгэгдэнэ:
     *   $router->GET_POST('/foo', $h)->middleware([Auth::class]);
     *   // GET /foo -> [Auth::class], POST /foo -> [Auth::class]
     *
     * match() нь route-level middleware-ийг tuple position `[2]`-аар буцаана:
     *   [$callable, $params, $middleware] = $router->match($path, $method);
     * HTTP-Application эдгээр middleware-уудыг execution pipeline-руу нэмж ажиллуулна.
     *
     * @param list<class-string|callable|\Psr\Http\Server\MiddlewareInterface> $middleware
     * @return self Method chaining-д зориулж энэ Route объектыг буцаана
     */
    public function middleware(array $middleware): self
    {
        $this->router->registerMiddleware($this->pattern, $this->method, $middleware);
        return $this;
    }
}
