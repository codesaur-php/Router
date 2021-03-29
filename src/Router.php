<?php declare(strict_types=1);

namespace codesaur\Router;

use BadMethodCallException;
use InvalidArgumentException;

class Router implements RouterInterface
{    
    private $_pipe = '';
    
    private $_routes = array();
    
    const HTTP_REQUEST_METHODS = array(
        'GET',
        'POST',
        'PUT',
        'HEAD',
        'DELETE',
        'PATCH',
        'OPTIONS',
    );
        
    public function __call(string $method, array $properties) : Route
    {
        $uppercase_method = strtoupper($method);
        
        if (is_array($properties[0])) {
            $methods = $properties[0];
            array_shift($properties);
        } elseif ($uppercase_method === 'ANY') {
            $methods = self::HTTP_REQUEST_METHODS;
        } elseif (in_array($uppercase_method, self::HTTP_REQUEST_METHODS)) {
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

        $filters = array();
        preg_match_all(self::PARAMS_FILTER, $pattern, $params);        
        foreach ($params[2] as $index => $param) {
            switch ($params[1][$index]) {
                case self::PARAM_INT: $filters[$param] = self::FILTER_INT; break;
                case self::PARAM_UNSIGNED_INT: $filters[$param] = self::FILTER_UNSIGNED_INT; break;
                case self::PARAM_FLOAT: $filters[$param] = self::FILTER_FLOAT; break;
                default: $filters[$param] = self::FILTER_STRING;
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
            if (!in_array($method, $route->getMethods())) {
                continue;
            }
            
            $pattern_regex = '@^' . $this->getPipe();
            $pattern_regex .= $route->getRegex(self::PARAMS_FILTER);
            $pattern_regex .= '/?$@i';
            if (!preg_match($pattern_regex, $pattern, $matches)) {
                continue;
            }
        
            $params = [];
            if (preg_match_all(self::PARAMS_FILTER, $route->getPattern(), $paramKeys)) {
                if (count($paramKeys[2]) !== (count($matches) - 1)) {
                    continue;
                }
                foreach ($paramKeys[2] as $key => $name) {
                    if (isset($matches[$key + 1])) {
                        $filter = $route->getFilters()[$name];
                        if ($filter === self::FILTER_STRING
                        ) {
                            $params[$name] = $matches[$key + 1];
                        } elseif ($filter === self::FILTER_FLOAT
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
        
        if ($pattern === $this->getPipe() . '/' . __FUNCTION__) {
            die(get_class($this));
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
        if ($params && preg_match_all(self::PARAMS_FILTER, $pattern, $paramKeys)) {
            foreach ($paramKeys[2] as $index => $key) {
                if (isset($params[$key])) {                        
                    $filter = $route->getFilters()[$key];
                    switch ($filter) {
                        case self::FILTER_FLOAT: 
                            if (!is_numeric($params[$key])) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be float value!");
                            }
                            break;
                        case self::FILTER_INT: 
                            if (!is_int($params[$key])) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be integer value!");
                            }
                            break;
                        case self::FILTER_UNSIGNED_INT:
                            $is_uint = filter_var($params[$key], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
                            if ($is_uint === false) {
                                throw new InvalidArgumentException(__CLASS__ . ": [$pattern] Route parameter expected to be unsigned integer value!");
                            }
                            break;
                    }

                    $pattern = preg_replace('/\{' . $paramKeys[1][$index] . '(\w+)\}/', $params[$key], $pattern, 1);
                }
            }
        }
        
        return $pattern;
    }
    
    public function getPipe(): string
    {
        return $this->_pipe;
    }
    
    public function setPipe(string $pipe)
    {
        $this->_pipe = rtrim($pipe, '/');
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
