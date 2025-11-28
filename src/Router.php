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
     * Параметертэй маршрутыг илрүүлэх regex.
     *
     * Жишээ: /news/{int:id}/{slug}
     *
     * @var string
     */
    const FILTERS_REGEX = '/\{(int:|uint:|float:)?(\w+)}/';

    /** @var string INTEGER төрлийн regex */
    const INT_REGEX = '(-?\d+)';

    /** @var string UNSIGNED INTEGER төрлийн regex */
    const UNSIGNED_INT_REGEX = '(\d+)';

    /** @var string FLOAT төрлийн regex */
    const FLOAT_REGEX = '(-?\d+|-?\d*\.\d+)';

    /** @var string DEFAULT string regex */
    const DEFAULT_REGEX = '([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)';
    
    /**
     * Магик метод — GET, POST, PUT, DELETE гэх мэт маршрут бүртгэнэ.
     *
     * Жишээ:
     *   $router->GET('/news/{int:id}', [NewsController::class, 'view'])->name('news-view');
     *
     * @param string $method HTTP method (get, post, put, delete...)
     * @param array<mixed> $properties [0] pattern, [1] callback
     * @return $this
     *
     * @throws \InvalidArgumentException Буруу маршрут тохиргоо үед
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
     * Энэ маршрутад нэр онооно.
     *
     * Жишээ:
     *   $router->GET('/news/{int:id}', ...)->name('news-view');
     *
     * @param string $ruleName Маршрутын нэр
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
     * @param string $path Орж ирсэн URL (/news/10)
     * @param string $method GET, POST, PUT гэх мэт
     * @return Callback|null Олдвол Callback, эс тэгвээс null
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
     * @param RouterInterface $router Нэмэлт router
     * @return void
     */
    public function merge(RouterInterface $router)
    {
        $this->routes = \array_merge($this->routes, $router->getRoutes());
        
        if (!empty($router->name_patterns)) {
            $this->name_patterns += $router->name_patterns;
        }
    }
    
    /**
     * Route name → URL generate хийнэ.
     *
     * @param string $ruleName Route name
     * @param array<string,mixed> $params Параметрүүд
     * @return string Үүсгэсэн URL
     *
     * @throws \OutOfRangeException Нэртэй маршрут олдохгүй бол
     * @throws \InvalidArgumentException Параметрийн төрөл буруу бол
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
     * @param string $pattern Route pattern (/news/{int:id})
     * @param array<string,string> $filters Param → regex
     * @return string Бэлтгэсэн regex
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
