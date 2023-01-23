<?php

namespace codesaur\Router;

interface RouterInterface
{
    public function getRoutes(): array;
    
    public function merge(RouterInterface $router);
    
    public function match(string $pattern, string $method): Callback|null;
    public function generate(string $routeName, array $params): string;
}
