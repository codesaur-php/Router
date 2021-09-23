<?php

namespace codesaur\Router;

use BadMethodCallException;
use InvalidArgumentException;

use ReflectionClass;

use Fig\Http\Message\RequestMethodInterface;

class Router implements RouterInterface
{
    protected $methods;
    
    private $_routes = array();

    const INT_REGEX ='(-?\d+)';
    const UNSIGNED_INT_REGEX = '(\d+)';
    const FLOAT_REGEX = '(-?\d+|-?\d*\.\d+)';
    const STRING_REGEX = '([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)';
    const FILTERS_REGEX = '/\{(string:|int:|uint:|float:)?(\w+)}/';
    
    function __construct()
    {
        $refl = new ReflectionClass(RequestMethodInterface::class);
        $this->methods = $refl->getConstants();
    }
    
    public function __call(string $method, array $properties): Route
    {
        $uppercase_method = strtoupper($method);
        
        if ($uppercase_method == 'MAP'
                && is_array($properties[0])
        ) {
            $methods = $properties[0];
            array_shift($properties);
        } elseif ($uppercase_method == 'ANY') {
            $methods = array_values($this->methods);
        } elseif (in_array($uppercase_method, $this->methods)) {
            $methods = array($uppercase_method);
        } else {
            throw new BadMethodCallException('Bad method call for ' . __CLASS__ . ":$method");
        }
        
        if (empty($properties) || empty($properties[1])) {
            throw new InvalidArgumentException('Invalid arguments for ' . __CLASS__ . ":$method");
        }
        
        $pattern = $properties[0];
        
        if (is_array($properties[1])
                || is_callable($properties[1])
        ) {
            $callback = $properties[1];
        } elseif (is_string($properties[1])) {
            $callback = array($properties[1], 'index');
        } else {
            throw new InvalidArgumentException(__CLASS__ . ": Invalid callback on route pattern [$pattern]");
        }
        
        $route = new Route($methods, $pattern, $callback);

        $params = array();
        $filters = array();
        preg_match_all(self::FILTERS_REGEX, $pattern, $params);
        foreach ($params[2] as $index => $param) {
            switch ($params[1][$index]) {
                case 'int:': $filters[$param] = self::INT_REGEX; break;
                case 'uint:': $filters[$param] = self::UNSIGNED_INT_REGEX; break;
                case 'float:': $filters[$param] = self::FLOAT_REGEX; break;
                default: $filters[$param] = self::STRING_REGEX;
            }
        }
        $route->setFilters($filters);
        
        $this->_routes[] = $route;
        
        return end($this->_routes);
    }
    
    public function getRouteByName(string $name): ?Route
    {
        foreach ($this->getRoutes() as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }
        
        return null;
    }
    
    public function match(string $pattern, string $method): ?Route
    {
        foreach ($this->_routes as $route) {
            if (!$route->hasMethod($method)) {
                continue;
            }

            $matches = array();
            $regex = $route->getRegex(self::FILTERS_REGEX);
            if (!preg_match("@^$regex/?$@i", "$pattern", $matches)) {
                continue;
            }
            
            $params = array();
            $paramKeys = array();
            if (preg_match_all(self::FILTERS_REGEX, $route->getPattern(), $paramKeys)) {
                if (count($paramKeys[2]) != (count($matches) - 1)) {
                    continue;
                }
                foreach ($paramKeys[2] as $key => $name) {
                    if (isset($matches[$key + 1])) {
                        $filter = $route->getFilters()[$name];
                        if ($filter === self::STRING_REGEX
                        ) {
                            $params[$name] = urldecode($matches[$key + 1]);
                        } elseif ($filter === self::FLOAT_REGEX
                        ) {
                            $params[$name] = (float)$matches[$key + 1];
                        } else {
                            $params[$name] = (int)$matches[$key + 1];
                        }
                    }
                }
            }
            $route->setParameters($params);

            return $route;
        }

        return null;
    }
    
    public function generate(string $routeName, array $params): ?string
    {
        $route = $this->getRouteByName($routeName);
        if (!$route instanceof Route) {
            if (defined('CODESAUR_DEVELOPMENT')
                    && CODESAUR_DEVELOPMENT
            ) {
                error_log("NO ROUTE: $routeName");
            }

            return null;
        }

        $paramKeys = array();
        $pattern = $route->getPattern();
        if ($params && preg_match_all(self::FILTERS_REGEX, $pattern, $paramKeys)) {
            foreach ($paramKeys[2] as $index => $key) {
                if (isset($params[$key])) {                        
                    $filter = $route->getFilters()[$key];
                    switch ($filter) {
                        case self::FLOAT_REGEX: 
                            if (!is_numeric($params[$key])) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be float value");
                            }
                            break;
                        case self::INT_REGEX: 
                            if (!is_int($params[$key])) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be integer value");
                            }
                            break;
                        case self::UNSIGNED_INT_REGEX:
                            $is_uint = filter_var($params[$key], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
                            if ($is_uint === false) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be unsigned integer value");
                            }
                            break;
                    }

                    $pattern = preg_replace('/\{' . $paramKeys[1][$index] . '(\w+)\}/', $params[$key], $pattern, 1);
                }
            }
        }
        
        return $pattern;
    }
    
    public function getRoutes(): array
    {
        return $this->_routes;
    }
    
    public function merge(Router $router)
    {
        $this->_routes = array_merge($this->_routes, $router->getRoutes());
    }
}
