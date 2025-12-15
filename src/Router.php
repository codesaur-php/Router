<?php

namespace codesaur\Router;

/**
 * Class Router
 *
 * codesaur Framework-ийн хөнгөн жинтэй маршрутчилал (routing) шийдлийн үндсэн Router класс.
 *
 * Энэхүү Router нь дараах үйлдлүүдийг гүйцэтгэнэ:
 *  - Маршрут бүртгэх (динамик __call ашиглан: $router->GET('/news', ...) хэлбэрээр)
 *  - {int:id}, {float:price}, {uint:page}, {slug} гэх мэт параметртэй маршрут боловсруулах
 *  - Request path болон HTTP method-д тохирох маршрутыг match() ашиглан олох
 *  - Route name → URL generate хийх
 *  - Модулийн бусад Router-уудыг merge() ашиглан нэгтгэх
 *
 * Жижиг, тогтвортой, фрэймворкоос үл хамааран standalone байдлаар ашиглаж болно.
 *
 * @package codesaur\Router
 */
class Router implements RouterInterface
{
    /**
     * Бүртгэлтэй бүх маршрутууд.
     *
     * @var array<string, array<string, Callback>>
     */
    protected array $routes = [];
    
    /**
     * Route name → pattern жагсаалт.
     *
     * @var array<string, string>
     */
    protected array $name_patterns = [];
    
    /**
     * Сүүлд бүртгэгдэж буй маршрутын pattern.
     *
     * @var string
     */
    private string $_pattern;

    /**
     * Параметертэй маршрутыг илрүүлэх regex pattern.
     *
     * Энэ regex нь {param}, {int:id}, {uint:page}, {float:price} гэх мэт
     * бүх төрлийн параметрийг илрүүлнэ.
     *
     * Жишээ: /news/{int:id}/{slug}
     *
     * @var string
     */
    const FILTERS_REGEX = '/\{(int:|uint:|float:)?(\w+)}/';

    /**
     * INTEGER төрлийн параметрийн regex pattern.
     * Сөрөг болон эерэг бүхэл тоонуудыг зөвшөөрнө.
     *
     * @var string
     */
    const INT_REGEX = '(-?\d+)';

    /**
     * UNSIGNED INTEGER төрлийн параметрийн regex pattern.
     * Зөвхөн эерэг бүхэл тоонуудыг зөвшөөрнө (0 ба түүнээс дээш).
     *
     * @var string
     */
    const UNSIGNED_INT_REGEX = '(\d+)';

    /**
     * FLOAT төрлийн параметрийн regex pattern.
     * Сөрөг болон эерэг бутархай тоонуудыг зөвшөөрнө.
     *
     * @var string
     */
    const FLOAT_REGEX = '(-?\d+|-?\d*\.\d+)';

    /**
     * DEFAULT string төрлийн параметрийн regex pattern.
     * URL-safe тэмдэгтүүд болон зарим тусгай тэмдэгтүүдийг зөвшөөрнө.
     *
     * @var string
     */
    const DEFAULT_REGEX = '([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)';
    
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
     *                                  [1] => callback (callable|array)
     * @return $this Method chaining-д зориулж router объектыг буцаана
     *
     * @throws \InvalidArgumentException Буруу маршрут тохиргоо үед
     *                                   (pattern эсвэл callback хоосон/буруу байвал)
     */
    public function &__call(string $method, array $properties)
    {
        if (empty($properties[0]) || empty($properties[1])) {
            throw new \InvalidArgumentException(
                'Invalid route configuration for ' . __CLASS__ . ":$method"
            );
        }
        
        $this->_pattern = $properties[0];

        if (\is_array($properties[1]) || \is_callable($properties[1])) {
            $callback = new Callback($properties[1]);
        } else {
            throw new \InvalidArgumentException(
                __CLASS__ . ": Invalid callback on route pattern [$this->_pattern]"
            );
        }
        
        $this->routes[$this->_pattern][$method] = $callback;

        return $this;
    }
    
    /**
     * Сүүлд бүртгэгдсэн маршрутад нэр онооно.
     *
     * Нэртэй маршрутуудыг generate() метод ашиглан URL үүсгэхэд ашиглана.
     * Нэг маршрутад зөвхөн нэг нэр оноож болно. Хэрэв дахин name() дуудвал
     * сүүлд бүртгэгдсэн маршрутын нэрийг шинэчилнэ.
     *
     * Жишээ:
     *   $router->GET('/news/{int:id}', ...)->name('news-view');
     *   $url = $router->generate('news-view', ['id' => 10]); // → /news/10
     *
     * @param string $ruleName Маршрутын нэр (уникаль байх ёстой)
     * @return void
     */
    public function name(string $ruleName)
    {
        if (isset($this->_pattern)) {
            $this->name_patterns[$ruleName] = $this->_pattern;
            unset($this->_pattern);
        }
    }
    
    /**
     * Request path болон HTTP method-д тохирох маршрутыг хайж олно.
     *
     * Энэ метод нь бүртгэлтэй маршрутуудыг дарааллаар шалгаж, таарах эхний
     * маршрутыг буцаана. Динамик параметрүүд олдвол Callback объектод
     * автоматаар set хийгдэнэ.
     *
     * @param string $path Орж ирсэн URL path (/news/10 гэх мэт)
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH гэх мэт)
     * @return Callback|null Таарсан маршрут (Callback объект), эсвэл null
     */
    public function match(string $path, string $method): Callback|null
    {
        foreach ($this->routes as $pattern => $route) {
            foreach ($route as $methods => $callback) {
                
                // Method check: "GET_POST" гэх мэт олон method байж болно.
                if (!\in_array($method, \explode('_', $methods))) {
                    continue;
                }
                
                // Pattern 100% ижил бол параметргүй маршрут
                if ($path == $pattern) {
                    return $callback;
                }

                $filters = [];
                $paramMatches = [];
                
                // Параметрүүдтэй эсэхийг шалгана
                if (!\preg_match_all(self::FILTERS_REGEX, $pattern, $paramMatches)) {
                    continue;
                }

                // Filterүүдийг тодорхойлох
                foreach ($paramMatches[2] as $index => $param) {
                    switch ($paramMatches[1][$index]) {
                        case 'int:':   $filters[$param] = self::INT_REGEX; break;
                        case 'uint:':  $filters[$param] = self::UNSIGNED_INT_REGEX; break;
                        case 'float:': $filters[$param] = self::FLOAT_REGEX; break;
                        default:       $filters[$param] = self::DEFAULT_REGEX;
                    }
                }

                // Regex таарах эсэх
                $matches = [];
                $regex = $this->getPatternRegex($pattern, $filters);

                if (!\preg_match("@^$regex/?$@i", $path, $matches)
                    || \count($paramMatches[2]) != (\count($matches) - 1)) {
                    continue;
                }
                
                // Параметрүүдийг parse хийе
                $params = [];
                foreach ($paramMatches[2] as $key => $name) {
                    if (isset($matches[$key + 1])) {
                        $filter = $filters[$name];
                        if ($filter == self::DEFAULT_REGEX) {
                            $params[$name] = \rawurldecode($matches[$key + 1]);
                        } elseif ($filter == self::FLOAT_REGEX) {
                            $params[$name] = (float) $matches[$key + 1];
                        } else {
                            $params[$name] = (int) $matches[$key + 1];
                        }
                    }
                }

                $callback->setParameters($params);
                
                return $callback;
            }
        }

        return null;
    }
    
    /**
     * Өөр router-ийн маршрутыг энэ router-т нэгтгэнэ.
     *
     * Энэ метод нь модулиудын routes.php файлуудыг үндсэн router-тэй
     * нэгтгэхэд ашиглагдана. Route name-ууд мөн нэгтгэгдэнэ. Хэрэв ижил
     * нэртэй route байвал эхний router-ийнх нь давуу тал болно.
     *
     * @param RouterInterface $router Нэмэлт router (маршрутуудыг нэгтгэх)
     * @return void
     */
    public function merge(RouterInterface $router)
    {
        $this->routes = \array_merge($this->routes, $router->getRoutes());
        
        // Хэрэв нэгтгэж буй router нь Router классын instance бол
        // name_patterns-ийг нэгтгэнэ
        if ($router instanceof Router && !empty($router->name_patterns)) {
            $this->name_patterns += $router->name_patterns;
        }
    }
    
    /**
     * Route name → URL generate хийнэ (reverse routing).
     *
     * Нэртэй маршрутын pattern-д параметрүүдийг суулгаж, бодит URL үүсгэнэ.
     * Параметрийн төрөл (int, uint, float) шалгагдаж, буруу бол exception шиднэ.
     *
     * Жишээ:
     *   $router->GET('/news/{int:id}', ...)->name('news-view');
     *   $url = $router->generate('news-view', ['id' => 10]); // → /news/10
     *
     * @param string $ruleName Route name (name() методоор бүртгэсэн)
     * @param array<string,mixed> $params Параметрүүд (жишээ: ['id' => 10, 'slug' => 'test'])
     * @return string Үүсгэсэн URL path
     *
     * @throws \OutOfRangeException Нэртэй маршрут олдохгүй бол
     * @throws \InvalidArgumentException Параметрийн төрөл буруу бол
     *                                   (жишээ: int шаардлагатай боловч string дамжуулсан)
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
        
        $paramMatches = [];
        if (\preg_match_all(self::FILTERS_REGEX, $pattern, $paramMatches)) {

            foreach ($paramMatches[2] as $index => $key) {
                if (isset($params[$key])) {
                    $filter = $paramMatches[1][$index];

                    // Төрлийг шалгах
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

                    // Pattern-д параметр суулгах
                    $pattern = \preg_replace(
                        '/\{' . $filter . '(\w+)\}/',
                        $params[$key],
                        $pattern,
                        1
                    );
                }
            }
        }
        
        return $pattern;
    }
    
    /**
     * Бүртгэлтэй маршрутуудын жагсаалтыг буцаана.
     *
     * @return array<string, array<string, Callback>>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
    
    /**
     * Маршрутын pattern-ийг regex болгон хөрвүүлнэ.
     *
     * Энэ метод нь route pattern-ийг (жишээ: /news/{int:id}/{slug}) regex pattern
     * болгон хөрвүүлнэ. Текст хэсгүүдийг URL encode хийж, параметрүүдийг
     * тохирох regex pattern-аар солино.
     *
     * @param string $pattern Route pattern (жишээ: /news/{int:id}/{slug})
     * @param array<string,string> $filters Параметр нэр → regex pattern mapping
     *                                      (жишээ: ['id' => '(-?\d+)', 'slug' => '(...)'])
     * @return string Бэлтгэсэн regex pattern (match() метод дотор ашиглах)
     */
    private function getPatternRegex(string $pattern, array $filters): string
    {
        $parts = \explode('/', $pattern);

        // Текст хэсгийг URL encode болгоно
        foreach ($parts as &$part) {
            if ($part != '' && $part[0] != '{') {
                $part = \rawurlencode($part);
            }
        }

        // {param} -ийг өөрийн regex-р солих
        return \preg_replace_callback(
            self::FILTERS_REGEX,
            function ($matches) use ($filters) {
                return isset($matches[2], $filters[$matches[2]])
                    ? $filters[$matches[2]]
                    : '(\w+)';
            },
            \implode('/', $parts)
        );
    }
}
