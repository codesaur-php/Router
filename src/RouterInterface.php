<?php

namespace codesaur\Router;

interface RouterInterface
{
    public function generate(string $routeName, array $params): ?string;
}
