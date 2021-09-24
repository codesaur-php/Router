<?php

namespace codesaur\Router;

interface RouterInterface
{
    public function match(string $pattern, string $method);
    public function generate(string $routeName, array $params);
}
