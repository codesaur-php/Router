<?php

namespace codesaur\Router;

use OutOfRangeException;
use InvalidArgumentException;

class Router implements RouterInterface
{
    private $_routes = array();
    private $_names = array();
    private $_last;
    
    const FILTERS_REGEX = '/\{(int:|uint:|float:|utf8:)?(\w+)}/';

    public function &__call(string $method, array $properties)
    {
        if (empty($properties[0])
                || empty($properties[1])
                || !is_callable($properties[1])
        ) {
            throw new InvalidArgumentException('Invalid route configuration for ' . __CLASS__ . ":$method");
        }
        
        $this->_last = $properties[0];
        $this->_routes[$this->_last][$method] = new Callback($properties[1]);

        return $this;
    }
    
    public function name(string $ruleName)
    {
        if (isset($this->_last)) {
            $this->_names[$ruleName] = $this->_last;
            unset($this->_last);
        }
    }
    
    public function match(string $path, string $method)
    {
        foreach ($this->_routes as $pattern => $route) {
            foreach ($route as $methods => $callback) {
                if (!in_array($method, explode('_', $methods))) {
                    continue;
                }

                $filters = array();
                $paramMatches = array();
                if (preg_match_all(self::FILTERS_REGEX, $pattern, $paramMatches)) {
                    foreach ($paramMatches[2] as $index => $param) {
                        switch ($paramMatches[1][$index]) {
                            case 'int:': $filters[$param] = self::INT_REGEX; break;
                            case 'uint:': $filters[$param] = self::UNSIGNED_INT_REGEX; break;
                            case 'float:': $filters[$param] = self::FLOAT_REGEX; break;
                            case 'utf8:': $filters[$param] = self::UTF8_REGEX; break;
                            default: $filters[$param] = self::DEFAULT_REGEX;
                        }
                    }
                }
                
                $matches = array();
                $regex = $this->getPatternRegex($pattern, $filters);
                if (!preg_match("@^$regex/?$@i", $path, $matches)
                        || count($paramMatches[2]) != (count($matches) - 1)
                ) {
                    continue;
                }
                
                $params = array();
                foreach ($paramMatches[2] as $key => $name) {
                    if (isset($matches[$key + 1])) {
                        $filter = $filters[$name];
                        if ($filter == self::DEFAULT_REGEX) {
                            $params[$name] = $matches[$key + 1];
                        } elseif ($filter == self::UTF8_REGEX) {
                            $params[$name] = urldecode($matches[$key + 1]);
                        } elseif ($filter == self::FLOAT_REGEX) {
                            $params[$name] = (float)$matches[$key + 1];
                        } else {
                            $params[$name] = (int)$matches[$key + 1];
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
        $this->_routes = array_merge($this->_routes, $router->getRoutes());
    }
    
    public function generate(string $ruleName, array $params)
    {
        if (!isset($this->_names[$ruleName])) {
            if (defined('CODESAUR_DEVELOPMENT')
                    && CODESAUR_DEVELOPMENT
            ) {
                error_log("NO RULE: $ruleName");
            }
            
            throw new OutOfRangeException(__CLASS__ . ": Route with rule named [$ruleName] not found");
        }

        $pattern = $this->_names[$ruleName];
        if (empty($params)) {
            return $pattern;
        }
        
        $paramMatches = array();
        if (preg_match_all(self::FILTERS_REGEX, $pattern, $paramMatches)) {
            foreach ($paramMatches[2] as $index => $key) {
                if (isset($params[$key])) {
                    $filter = $paramMatches[1][$index];
                    switch ($filter) {
                        case 'float:':
                            if (!is_numeric($params[$key])) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be float value");
                            }
                            break;
                        case 'int:':
                            if (!is_int($params[$key])) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be integer value");
                            }
                            break;
                        case 'uint':
                            $is_uint = filter_var($params[$key], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
                            if ($is_uint === false) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be unsigned integer value");
                            }
                            break;
                    }
                    $pattern = preg_replace('/\{' . $filter . '(\w+)\}/', $params[$key], $pattern, 1);
                }
            }
        }
        
        return $pattern;
    }
    
    public function getRoutes(): array
    {
        return $this->_routes;
    }
    
    final function getPatternRegex($pattern, array $filters): string
    {
        $parts = explode('/', $pattern);
        foreach ($parts as &$part) {
            if ($part != '' && $part[0] != '{') {
                $part = urlencode($part);
            }
        }
        return preg_replace_callback(self::FILTERS_REGEX, function ($matches) use ($filters) {
            return isset($matches[2]) && isset($filters[$matches[2]]) ? $filters[$matches[2]] : '(\w+)';
        }, implode('/', $parts));
    }
}
