<?php

namespace codesaur\Router;

class Router implements RouterInterface
{
    protected array $routes = [];
    
    protected array $name_patterns = [];
    
    private string $_pattern;
    
    const FILTERS_REGEX = '/\{(int:|uint:|float:)?(\w+)}/';

    const INT_REGEX = '(-?\d+)';
    const UNSIGNED_INT_REGEX ='(\d+)';
    const FLOAT_REGEX = '(-?\d+|-?\d*\.\d+)';
    const DEFAULT_REGEX = '([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)';
    
    public function &__call(string $method, array $properties)
    {
        if (empty($properties[0]) || empty($properties[1])) {
            throw new \InvalidArgumentException('Invalid route configuration for ' . __CLASS__ . ":$method");
        }
        
        $this->_pattern = $properties[0];
        if (\is_array($properties[1]) || \is_callable($properties[1])) {
            $callback = new Callback($properties[1]);
        } else {
            throw new \InvalidArgumentException(__CLASS__ . ": Invalid callback on route pattern [$this->_pattern]");
        }
        
        $this->routes[$this->_pattern][$method] = $callback;

        return $this;
    }
    
    public function name(string $ruleName)
    {
        if (isset($this->_pattern)) {
            $this->name_patterns[$ruleName] = $this->_pattern;
            unset($this->_pattern);
        }
    }
    
    public function match(string $path, string $method): Callback|null
    {
        foreach ($this->routes as $pattern => $route) {
            foreach ($route as $methods => $callback) {
                if (!\in_array($method, \explode('_', $methods))) {
                    continue;
                }
                
                if ($path == $pattern) {
                    return $callback;
                }

                $filters = [];
                $paramMatches = [];
                if (!\preg_match_all(self::FILTERS_REGEX, $pattern, $paramMatches)) {
                    continue;
                }
                foreach ($paramMatches[2] as $index => $param) {
                    switch ($paramMatches[1][$index]) {
                        case 'int:': $filters[$param] = self::INT_REGEX; break;
                        case 'uint:': $filters[$param] = self::UNSIGNED_INT_REGEX; break;
                        case 'float:': $filters[$param] = self::FLOAT_REGEX; break;
                        default: $filters[$param] = self::DEFAULT_REGEX;
                    }
                }

                $matches = [];
                $regex = $this->getPatternRegex($pattern, $filters);
                if (!\preg_match("@^$regex/?$@i", $path, $matches)
                    || \count($paramMatches[2]) != (\count($matches) - 1)
                ) {
                    continue;
                }
                
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
    
    public function merge(RouterInterface $router)
    {
        $this->routes = \array_merge($this->routes, $router->getRoutes());
        
        if (!empty($router->name_patterns)) {
            $this->name_patterns += $router->name_patterns;
        }
    }
    
    public function generate(string $ruleName, array $params = []): string
    {
        if (!isset($this->name_patterns[$ruleName])) {
            if (\defined('CODESAUR_DEVELOPMENT')
                && CODESAUR_DEVELOPMENT
            ) {
                \error_log("NO RULE: $ruleName");
            }
            
            throw new \OutOfRangeException(__CLASS__ . ": Route with rule named [$ruleName] not found");
        }

        $pattern = $this->name_patterns[$ruleName];
        if (empty($params)) {
            return $pattern;
        }
        
        $paramMatches = [];
        if (\preg_match_all(self::FILTERS_REGEX, $pattern, $paramMatches)) {
            foreach ($paramMatches[2] as $index => $key) {
                if (isset($params[$key])) {
                    $filter = $paramMatches[1][$index];
                    switch ($filter) {
                        case 'float:':
                            if (!\is_numeric($params[$key])) {
                                throw new \InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be float value");
                            }
                            break;
                        case 'int:':
                            if (!\is_int($params[$key])) {
                                throw new \InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be integer value");
                            }
                            break;
                        case 'uint:':
                            $is_uint = \filter_var($params[$key], \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                            if ($is_uint === false) {
                                throw new \InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be unsigned integer value");
                            }
                            break;
                    }
                    $pattern = \preg_replace('/\{' . $filter . '(\w+)\}/', $params[$key], $pattern, 1);
                }
            }
        }
        
        return $pattern;
    }
    
    public function getRoutes(): array
    {
        return $this->routes;
    }
    
    private function getPatternRegex(string $pattern, array $filters): string
    {
        $parts = \explode('/', $pattern);
        foreach ($parts as &$part) {
            if ($part != '' && $part[0] != '{') {
                $part = \rawurlencode($part);
            }
        }
        return \preg_replace_callback(self::FILTERS_REGEX, function ($matches) use ($filters) {
            return isset($matches[2]) && isset($filters[$matches[2]]) ? $filters[$matches[2]] : '(\w+)';
        }, \implode('/', $parts));
    }
}
