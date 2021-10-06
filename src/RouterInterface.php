<?php

namespace codesaur\Router;

interface RouterInterface
{
    const INT_REGEX = '(-?\d+)';
    const UNSIGNED_INT_REGEX ='(\d+)';
    const FLOAT_REGEX = '(-?\d+|-?\d*\.\d+)';
    const UTF8_REGEX = '([A-Za-z0-9%_,!~&)(=;\'\$\.\*\]\[\@\-]+)';
    const DEFAULT_REGEX = '(\w+)';
    
    public function getRoutes(): array;
    
    public function merge(RouterInterface $router);
    
    public function match(string $pattern, string $method);
    public function generate(string $routeName, array $params);
}
