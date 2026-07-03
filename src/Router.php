<?php

namespace codesaur\Router;

/**
 * Class Router
 *
 * codesaur Framework-ийн хөнгөн жинтэй маршрутчилал (routing) шийдлийн үндсэн Router класс.
 *
 * Энэхүү Router нь дараах үйлдлүүдийг гүйцэтгэнэ:
 * - Маршрут бүртгэх (динамик __call ашиглан: $router->GET('/news', ...) хэлбэрээр)
 * - {int:id}, {float:price}, {uint:page}, {slug} гэх мэт параметртэй маршрут боловсруулах
 * - Request path болон HTTP method-д тохирох маршрутыг match() ашиглан олох
 * - Route name -> URL generate хийх (reverse routing)
 * - Per-route middleware дэмжих (Route::middleware()-ээр)
 *
 * `RouterInterface` нь хамгийн минимал contract-г л заадаг (зөвхөн `match()`).
 * Бусад generate/pattern/name зэрэг нь Router class-ын concrete API юм.
 *
 * @package codesaur\Router
 */
class Router implements RouterInterface
{
    /**
     * Бүртгэлтэй бүх маршрутууд.
     *
     * Бүтэц: pattern бүрд method-map, method бүрд 2-tuple `[callable, middleware]`:
     *   [
     *     pattern => [
     *       method => [
     *         0 => callable,        // энэ (pattern, method) route-ийн handler
     *         1 => [middleware...], // энэ (pattern, method) route-ийн middleware жагсаалт
     *       ],
     *       ...
     *     ]
     *   ]
     *
     * Granularity - юу юутай хэрхэн харьцаж байгаа:
     * ----------------------------------------------------------------------
     *   pattern + method  ->  callable     (per-METHOD granularity)
     *   pattern + method  ->  middleware   (per-METHOD granularity)
     * ----------------------------------------------------------------------
     *
     * **callable болон middleware хоёулаа (pattern, method) хосоор индекслэгдэнэ.**
     * Express, Laravel, Slim, Fastify зэрэг mainstream router-уудтай нийцэж байна:
     *
     *   $router->GET('/api/users', $list);                                // public read
     *   $router->POST('/api/users', $create)->middleware([Auth::class]);  // protected write
     *
     * - GET /api/users -> middleware байхгүй (public)
     * - POST /api/users -> [Auth::class] middleware ажиллана
     *
     * **Compound methods (GET_POST)** - нэг дуудалт олон method-д бүртгэнэ.
     * Эдгээрийг registration үед салгаж тус тусдаа entry болгож хадгалдаг:
     *
     *   $router->GET_POST('/foo', $h)->middleware([Auth::class]);
     *   // -> $routes['/foo']['GET']  = [$h, [Auth::class]]
     *   // -> $routes['/foo']['POST'] = [$h, [Auth::class]]
     *
     * **Append semantics** - олон `->middleware([...])` дуудвал шинэ middleware-үүд
     * тухайн (pattern, method) entry-д цуглуулагдана (replace биш).
     *
     * @var array<string, array<string, array{
     *     0: callable|array{class-string, string},
     *     1: list<class-string|callable|\Psr\Http\Server\MiddlewareInterface>
     * }>>
     */
    protected array $routes = [];

    /**
     * Route name -> pattern reverse index.
     *
     * Бүтэц:
     *   [
     *     ruleName => pattern,
     *     ...
     *   ]
     *
     * Granularity - name нь юутай хэрхэн харьцаж байгаа:
     * ----------------------------------------------------------------------
     *   ruleName  ->  pattern   (per-PATTERN granularity, HTTP method-аас үл хамаарна)
     * ----------------------------------------------------------------------
     *
     * - **name нь зөвхөн pattern-руу заана** - method биш. Тиймээс нэг URL
     *   pattern дээр GET, POST хоёулаа бүртгэгдсэн ч `->name('foo')` нь
     *   `/foo` URL-ийн нэр - method-аас үл хамаарна. `generate('foo')` нь
     *   зөвхөн URL string л буцаах учраас method-ийн ялгаа орохгүй.
     *
     * - **Нэг name -> зөвхөн нэг pattern**. Ижил name-ийг өөр pattern-д оноох
     *   гэвэл `registerName()` нь `\LogicException` шиднэ (strict mode).
     *
     * - `generate($name)`-д `O(1)` lookup болохын тулд reverse-indexed array.
     *
     * @var array<string, string>
     */
    protected array $name_patterns = [];

    /**
     * Параметертэй маршрутыг илрүүлэх regex pattern.
     *
     * Энэ regex нь {param}, {int:id}, {uint:page}, {float:price} гэх мэт
     * бүх төрлийн параметрийг илрүүлнэ.
     *
     * Жишээ: /news/{int:id}/{slug}
     *
     * @const string
     */
    const FILTERS_REGEX = '/\{(int:|uint:|float:|utf8:)?(\w+)}/';

    /**
     * INTEGER төрлийн параметрийн regex pattern.
     * Сөрөг болон эерэг бүхэл тоонуудыг зөвшөөрнө.
     *
     * @const string
     */
    const INT_REGEX = '(-?\d+)';

    /**
     * UNSIGNED INTEGER төрлийн параметрийн regex pattern.
     * Зөвхөн эерэг бүхэл тоонуудыг зөвшөөрнө (0 ба түүнээс дээш).
     *
     * @const string
     */
    const UNSIGNED_INT_REGEX = '(\d+)';

    /**
     * FLOAT төрлийн параметрийн regex pattern.
     * Сөрөг болон эерэг бутархай тоонуудыг зөвшөөрнө.
     *
     * @const string
     */
    const FLOAT_REGEX = '(-?\d+|-?\d*\.\d+)';

    /**
     * DEFAULT string төрлийн параметрийн regex pattern.
     * URL-safe тэмдэгтүүд болон зарим тусгай тэмдэгтүүдийг зөвшөөрнө.
     *
     * @const string
     */
    const DEFAULT_REGEX = '([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)';

    /**
     * UTF-8 string төрлийн параметрийн regex pattern.
     * DEFAULT_REGEX дээр нэмэлтээр multibyte UTF-8 тэмдэгтүүд (Кирилл, Монгол гэх мэт)
     * болон хоосон зайг зөвшөөрнө. Percent-encoded болон raw UTF-8 аль алиныг нь таарна.
     *
     * @const string
     */
    const UTF8_REGEX = '([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@ \x80-\xFF\-]+)';

    /**
     * Магик метод - GET, POST, PUT, DELETE гэх мэт маршрут бүртгэнэ.
     *
     * Энэ метод нь динамик аргаар HTTP method-уудыг дуудаж болох болгодог.
     * Method нь том үсгээр бичигдсэн байх ёстой (GET, POST, PUT, DELETE, PATCH).
     *
     * Жишээ:
     *   $router->GET('/news/{int:id}', [NewsController::class, 'view'])->name('news-view');
     *   $router->POST('/users', function() { ... });
     *   $router->PUT('/users/{int:id}', [UserController::class, 'update']);
     *
     * @param string $method HTTP method нэр (GET, POST, PUT, DELETE, PATCH гэх мэт)
     * @param array<mixed> $properties [0] => route pattern (string),
     *                                  [1] => callable
     * @return Route Бүртгэсэн route-ыг илэрхийлэх immutable Route объект.
     *               `->name(...)` гэх мэт fluent API-д ашиглагдана.
     *
     * @throws \InvalidArgumentException Буруу маршрут тохиргоо үед
     *                                   (pattern эсвэл callback хоосон/буруу байвал)
     */
    public function __call(string $method, array $properties): Route
    {
        if (empty($properties[0]) || empty($properties[1])) {
            throw new \InvalidArgumentException(
                'Invalid route configuration for ' . __CLASS__ . ":$method"
            );
        }

        $pattern = $properties[0];

        if (!\is_array($properties[1]) && !\is_callable($properties[1])) {
            throw new \InvalidArgumentException(
                __CLASS__ . ": Invalid callback on route pattern [$pattern]"
            );
        }

        // Compound methods (GET_POST) - split into individual entries
        foreach (\explode('_', $method) as $m) {
            $this->routes[$pattern][$m] ??= [null, []];
            $this->routes[$pattern][$m][0] = $properties[1];
        }

        return new Route($this, $pattern, $method);
    }
    
    /**
     * {@inheritDoc}
     *
     * codesaur Router-ийн хэрэгжилтийн онцлог:
     * - **HEAD -> GET авто fallback** (RFC 7231 sec. 4.3.2):
     *    Хэрэв `HEAD` request ирсэн боловч explicit `HEAD` route байхгүй бол
     *    автоматаар `GET` handler рүү очно. Consumer тал response body-г
     *    цэвэрлэх ёстой (HEAD response нь body байх ёсгүй).
     * - Буцаах tuple үргэлж 3 элементтэй: `[callable, params, middleware]`.
     *    Middleware байхгүй route-д ч `$result[2]` нь хоосон `[]` байна.
     */
    public function match(string $path, string $method): ?array
    {
        foreach ($this->routes as $pattern => $methodMap) {

            // Энэ pattern-д шаардсан method бүртгэлгүй бол шууд алгасна
            if (!isset($methodMap[$method])) {
                continue;
            }

            [$callable, $middleware] = $methodMap[$method];

            // Pattern 100% ижил бол параметргүй маршрут - шууд буцаана
            if ($path == $pattern) {
                return [$callable, [], $middleware];
            }

            $filters = [];
            $paramMatches = [];

            // Параметрүүдтэй эсэхийг шалгана - хэрэв параметр байхгүй бол дараагийн route руу шилжинэ
            if (!\preg_match_all(self::FILTERS_REGEX, $pattern, $paramMatches)) {
                continue;
            }

            // Filterүүдийг тодорхойлох - параметрийн төрөл бүрт тохирох regex pattern оноох
            foreach ($paramMatches[2] as $index => $param) {
                switch ($paramMatches[1][$index]) {
                    case 'int:':   $filters[$param] = self::INT_REGEX; break;
                    case 'uint:':  $filters[$param] = self::UNSIGNED_INT_REGEX; break;
                    case 'float:': $filters[$param] = self::FLOAT_REGEX; break;
                    case 'utf8:':  $filters[$param] = self::UTF8_REGEX; break;
                    default:       $filters[$param] = self::DEFAULT_REGEX;
                }
            }

            // Regex таарах эсэх - pattern-ийг regex болгож, path-тай тааруулах
            $matches = [];
            $regex = $this->getPatternRegex($pattern, $filters);

            if (!\preg_match("@^$regex/?$@i", $path, $matches)
                || \count($paramMatches[2]) != (\count($matches) - 1)) {
                continue;
            }

            // Параметрүүдийг parse хийе - төрөл бүрт тохирох утга болгон хөрвүүлэх
            $params = [];
            foreach ($paramMatches[2] as $key => $name) {
                if (isset($matches[$key + 1])) {
                    $filter = $filters[$name];
                    if ($filter == self::DEFAULT_REGEX
                        || $filter == self::UTF8_REGEX
                    ) {
                        // String параметр - URL decode хийх
                        $params[$name] = \rawurldecode($matches[$key + 1]);
                    } elseif ($filter == self::FLOAT_REGEX) {
                        // Float параметр - float болгон хөрвүүлэх
                        $params[$name] = (float) $matches[$key + 1];
                    } else {
                        // Integer параметр (int эсвэл uint) - int болгон хөрвүүлэх
                        $params[$name] = (int) $matches[$key + 1];
                    }
                }
            }

            return [$callable, $params, $middleware];
        }

        // HEAD -> GET авто fallback (RFC 7231 sec. 4.3.2).
        // Explicit HEAD route байгаа бол дээрх loop-д аль хэдийнэ таарсан байх ёстой.
        // Энд хүрсэн бол HEAD-д тохирох route байхгүй гэсэн үг - GET-ээр дахин оролдоё.
        if ($method === 'HEAD') {
            return $this->match($path, 'GET');
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function generate(string $ruleName, array $params = []): string
    {
        if (!isset($this->name_patterns[$ruleName])) {
            throw new \OutOfRangeException(
                __CLASS__ . ": Route with rule named [$ruleName] not found"
            );
        }

        $pattern = $this->name_patterns[$ruleName];

        // Параметргүй бол pattern шууд буцаана
        if (empty($params)) {
            return $pattern;
        }

        // Pattern-аас бүх параметрүүдийг олох
        $paramMatches = [];
        if (\preg_match_all(self::FILTERS_REGEX, $pattern, $paramMatches)) {

            foreach ($paramMatches[2] as $index => $key) {
                if (isset($params[$key])) {
                    $filter = $paramMatches[1][$index];

                    // Параметрийн төрлийг шалгах - буруу бол exception шиднэ
                    switch ($filter) {
                        case 'float:':
                            if (!\is_numeric($params[$key])) {
                                throw new \InvalidArgumentException(
                                    __CLASS__ . ": [$pattern] Route parameter expected to be float value"
                                );
                            }
                            break;

                        case 'int:':
                            if (!\is_int($params[$key])) {
                                throw new \InvalidArgumentException(
                                    __CLASS__ . ": [$pattern] Route parameter expected to be integer value"
                                );
                            }
                            break;

                        case 'uint:':
                            // Unsigned integer - зөвхөн 0 ба түүнээс дээш утга зөвшөөрнө
                            $is_uint = \filter_var($params[$key], \FILTER_VALIDATE_INT, [
                                'options' => ['min_range' => 0]
                            ]);
                            if ($is_uint === false) {
                                throw new \InvalidArgumentException(
                                    __CLASS__ . ": [$pattern] Route parameter expected to be unsigned integer value"
                                );
                            }
                            break;
                    }

                    // Pattern-д параметр суулгах - {int:id} -> бодит утга.
                    // Нэрээр нь ЯГ таарах placeholder-ийг л солино. preg_replace
                    // ашиглавал (1) ижил filter-тэй ӨӨР нэртэй эхний placeholder
                    // солигдох, (2) утга доторх $1, \1 зэрэг backreference болж
                    // тайлагдах хоёр алдаа гарна.
                    $pattern = \str_replace(
                        '{' . $filter . $key . '}',
                        (string) $params[$key],
                        $pattern
                    );
                }
            }
        }

        return $pattern;
    }

    /**
     * {@inheritDoc}
     */
    public function pattern(string $ruleName): string
    {
        if (!isset($this->name_patterns[$ruleName])) {
            throw new \OutOfRangeException(
                __CLASS__ . ": Route with rule named [$ruleName] not found"
            );
        }

        return \preg_replace(
            self::FILTERS_REGEX,
            '{$2}',
            $this->name_patterns[$ruleName]
        );
    }

    /**
     * {@inheritDoc}
     *
     * Storage shape-ийг шууд буцаана. Name мэдээлэл хэрэгтэй бол
     * `generate($name)`-ээр шалгах, эсвэл subclass-аас `$name_patterns`-руу хандах.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Route name -> pattern бүртгэх (internal API).
     *
     * Энэ метод нь Route::name()-аас дуудагдах internal механизм. Хэрэглэгчийн
     * нийтлэг хэрэглээ нь Route::name() chain-р явах:
     *   $router->GET('/news/{int:id}', ...)->name('news-view');
     *
     * Шууд дуудах тохиолдол ховор - pattern нь Route::name()-аар автоматаар
     * дамжуулагдсан. Гэхдээ post-hoc нэр оноох шаардлагатай үед дуудаж болно:
     *   $router->GET('/foo', $cb);
     *   $router->registerName('foo', '/foo');
     *
     * Strict mode - ижил нэрийг өөр pattern-д оноовол `\LogicException` шиднэ.
     * Ижил нэрийг ижил pattern-д давтан оноох нь idempotent:
     *   $router->GET('/users', $cb)->name('users')->name('users');  // no-op
     *
     * @param string $ruleName Маршрутын нэр (уникаль байх ёстой)
     * @param string $pattern Маршрутын pattern
     * @return void
     *
     * @throws \LogicException Ижил нэр өөр pattern-д бүртгэгдэх гэж байгаа бол
     * @internal Route::name() дотроос дуудагдана. Шууд дуудах нь сонголтот.
     */
    public function registerName(string $ruleName, string $pattern): void
    {
        if (isset($this->name_patterns[$ruleName])
            && $this->name_patterns[$ruleName] !== $pattern
        ) {
            throw new \LogicException(\sprintf(
                '%s: Route name [%s] is already registered to pattern [%s]; cannot reassign to [%s]',
                __CLASS__,
                $ruleName,
                $this->name_patterns[$ruleName],
                $pattern
            ));
        }

        $this->name_patterns[$ruleName] = $pattern;
    }

    /**
     * (pattern, method) route-д middleware жагсаалт хавсаргах (internal API).
     *
     * Энэ метод нь Route::middleware()-аас дуудагдах internal механизм. Хэвлэг
     * хэрэглээ нь `$router->GET(...)->middleware([...])` chain юм.
     *
     * Method нь compound байж болно (GET_POST) - тэгвэл доорх method бүрд
     * middleware-ийг тус тусад нь нэмнэ.
     *
     * Append semantics - хэд хэдэн удаа дуудвал middleware-ууд цуглуулагдана:
     *   $router->GET('/x', $cb)->middleware([A::class])->middleware([B::class]);
     *   // Эцэст: $routes['/x']['GET'][1] = [A::class, B::class]
     *
     * Энэ нь base class-ийн __call() дотроос middleware auto-attach хийгээд,
     * хэрэглэгч нэмэлт middleware route-д наахад тохиромжтой.
     *
     * @param string $pattern Маршрутын pattern
     * @param string $method  HTTP method (compound 'GET_POST' дэмжинэ)
     * @param list<class-string|callable|\Psr\Http\Server\MiddlewareInterface> $middleware
     * @return void
     * @internal Route::middleware() дотроос дуудагдана.
     */
    public function registerMiddleware(string $pattern, string $method, array $middleware): void
    {
        foreach (\explode('_', $method) as $m) {
            $this->routes[$pattern][$m] ??= [null, []];
            $this->routes[$pattern][$m][1] = [
                ...$this->routes[$pattern][$m][1],
                ...$middleware,
            ];
        }
    }

    /**
     * Маршрутын pattern-ийг regex болгон хөрвүүлнэ.
     *
     * Энэ метод нь route pattern-ийг (жишээ: /news/{int:id}/{slug}) regex pattern
     * болгон хөрвүүлнэ. Текст хэсгүүдийг URL encode хийж, параметрүүдийг
     * тохирох regex pattern-аар солино.
     *
     * @param string $pattern Route pattern (жишээ: /news/{int:id}/{slug})
     * @param array<string,string> $filters Параметр нэр -> regex pattern mapping
     *                                      (жишээ: ['id' => '(-?\d+)', 'slug' => '(...)'])
     * @return string Бэлтгэсэн regex pattern (match() метод дотор ашиглах)
     */
    private function getPatternRegex(string $pattern, array $filters): string
    {
        $parts = \explode('/', $pattern);

        // Текст хэсгийг URL encode болгоно - URL-д аюулгүй байхын тулд.
        // Дараа нь preg_quote хийнэ: rawurlencode нь `.` болон `~`-ийг
        // хувиргадаггүй тул escape хийхгүй бол `.` нь regex wildcard болж
        // `/files/app.js` pattern `/files/appXjs`-тэй таарах алдаа гарна.
        foreach ($parts as &$part) {
            if ($part != '' && $part[0] != '{') {
                $part = \preg_quote(\rawurlencode($part), '@');
            }
        }

        // {param} -ийг тохирох regex pattern-аар солих
        return \preg_replace_callback(
            self::FILTERS_REGEX,
            function ($matches) use ($filters) {
                return isset($matches[2], $filters[$matches[2]])
                    ? $filters[$matches[2]]
                    : '(\w+)'; // Default fallback regex
            },
            \implode('/', $parts)
        );
    }
}
