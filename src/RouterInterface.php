<?php

namespace codesaur\Router;

interface RouterInterface
{
    public function getRoutes(): array;    
    public function match(string $pattern, string $method);
    public function merge(RouterInterface $router): bool;
    public function generate(string $routeName, array $params);
}
