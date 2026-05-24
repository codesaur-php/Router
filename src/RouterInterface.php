<?php

namespace codesaur\Router;

/**
 * Interface RouterInterface
 *
 * codesaur ecosystem-ийн router-ийн **бүрэн contract**. HTTP application
 * болон бусад системд router-ийн хэрэгжилтээс үл хамаарах boundary болдог.
 *
 * Энэ интерфэйс 4 method-ийг шаарддаг:
 * - `match()` - request path + method-д тохирох маршрутыг олох
 * - `generate()` - route name -> URL (reverse routing)
 * - `pattern()` - route name -> client-side substitution-д бэлэн pattern
 * - `getRoutes()` - бүртгэлтэй бүх маршрутын жагсаалт (introspection)
 *
 * Энэхүү contract нь:
 * - Гадны router (FastRoute, Symfony Routing, AltoRouter гэх мэт)-ийг
 *    adapter-аар нь ороож `codesaur/http-application`-д ашиглах боломжийг олгоно
 * - Raptor болон бусад codesaur app-ууд router-аас үл хамаарах кодтой байх
 * - Concrete `Router` class-ын нэмэлт internal API-уудыг (`registerName()`,
 *    `registerMiddleware()`) interface-аас гадуур үлдээх
 *
 * @package codesaur\Router
 */
interface RouterInterface
{
    /**
     * Орж ирсэн URL path болон HTTP method-д тохирох маршрутыг хайна.
     *
     * Таарах маршрут олдвол **тогтмол 3 элементтэй tuple array** буцаана:
     * - [0] callable - гүйцэтгэх функц, Closure эсвэл [Class, 'method']
     * - [1] params - pattern-аас гаргаж авсан параметрүүд (нэр -> утга)
     * - [2] middleware - энэ route-д ажиллах middleware жагсаалт
     *                       (хоосон бол `[]`)
     *
     * Таарахгүй бол `null` буцаана.
     *
     * **Concrete contract-ийн давуу тал:**
     * - Хамгийн хурдан access: `$result[2]` (hash lookup-гүй positional)
     * - Шууд destructuring: `[$callable, $params, $middleware] = $result;`
     * - Type-safe - IDE/PHPStan tuple shape-ийг бүрэн ойлгоно
     *
     * @param string $path   Хайлтын URL path (/news/123 гэх мэт)
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH...)
     * @return array{
     *     0: callable|array{class-string, string},
     *     1: array<string, mixed>,
     *     2: list<class-string|callable|\Psr\Http\Server\MiddlewareInterface>
     * }|null
     */
    public function match(string $path, string $method): ?array;

    /**
     * Route name дээр үндэслэн URL үүсгэнэ (reverse routing).
     *
     * Нэртэй маршрутын pattern-д параметрүүдийг суулгаж бодит URL үүсгэнэ.
     * Параметрийн төрөл (int, uint, float) шалгагдаж, буруу бол exception шиднэ.
     *
     * NOTE: ENCODING CONTRACT:
     *     Энэ метод параметрийн утгыг pattern-д **түүхийгээр** (raw) substitution
     *     хийнэ - percent-encoding хийдэггүй. Энэ нь зориудаар PSR-7-ийн
     *     `UriInterface::withPath()`-тай нийцэх contract юм:
     *
     * - `withPath()` өөрөө RFC 3986 ёсоор encoding-г хариуцна
     * - Энд encode хийвэл **давхар encoding** үүсэх эрсдэлтэй
     *
     * Жишээ:
     *   $url = $router->generate('news-view', ['id' => 10]);  // "/news/10"
     *
     * @param string $routeName Route name (name() методоор бүртгэсэн)
     * @param array<string, mixed> $params Параметрүүд
     * @return string Үүсгэсэн URL path (raw, encode хийгдээгүй)
     *
     * @throws \OutOfRangeException Route name олдохгүй бол
     * @throws \InvalidArgumentException Параметрийн төрөл буруу бол
     */
    public function generate(string $routeName, array $params = []): string;

    /**
     * Route name -> client-side substitution-д бэлэн URL pattern буцаана.
     *
     * generate()-аас ялгаатай нь параметрийн утга шаардахгүй, зөвхөн filter
     * prefix-уудыг (`int:`, `uint:`, `float:`, `utf8:`) хасч JS-ийн
     * `String.replace('{name}', value)`-д тохирох pattern буцаана.
     *
     * Жишээ:
     *   $router->pattern('news-view');    // '/news/{id}/{slug}'
     *
     * @param string $routeName Route name (name() методоор бүртгэсэн)
     * @return string Filter prefix хасагдсан pattern
     *
     * @throws \OutOfRangeException Route name олдохгүй бол
     */
    public function pattern(string $routeName): string;

    /**
     * Бүртгэлтэй бүх маршрутын жагсаалтыг буцаана (introspection-д зориулагдсан).
     *
     * Бүтэц - (pattern, method) бүрд 2-tuple `[callable, middleware]`:
     *   [
     *     pattern => [
     *       method => [
     *         [0] callable,    // гүйцэтгэх
     *         [1] middleware,  // route-д бүртгэсэн middleware жагсаалт
     *       ]
     *     ]
     *   ]
     *
     * Энэ нь registration-time-ийн route мэдээллийг буцаана - pattern нь outer
     * key учраас params placeholder шаардлагагүй. Match-time-ийн params нь
     * зөвхөн `match()` дотор тооцоологдоно.
     *
     * Хэрэглээний жишээ:
     * - Admin panel-д бүх routes-ыг жагсаах
     * - Sitemap auto-generate хийх
     * - API documentation auto-generate хийх
     *
     * Жишээ destructuring:
     *   foreach ($router->getRoutes() as $pattern => $methods) {
     *       foreach ($methods as $method => [$callable, $middleware]) {
     *           echo "$method $pattern\n";
     *       }
     *   }
     *
     * NOTE: Route name мэдээлэл энд орохгүй. Naming нь тусдаа concern -
     * `generate($name)`-ээр URL хайх, эсвэл сонголтот `pattern($name)`-аар
     * client-side pattern авах.
     *
     * @return array<string, array<string, array{
     *     0: callable|array{class-string, string},
     *     1: list<class-string|callable|\Psr\Http\Server\MiddlewareInterface>
     * }>>
     */
    public function getRoutes(): array;
}
