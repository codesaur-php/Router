<?php declare(strict_types=1);

namespace codesaur\Router;

interface RouterInterface
{
    const PARAM_STRING = 'string:';
    const FILTER_STRING = '(\w+)';
    
    const PARAM_INT = 'int:';
    const FILTER_INT ='(-?\d+)';
    
    const PARAM_UNSIGNED_INT = 'uint:';
    const FILTER_UNSIGNED_INT = '(\d+)';
    
    const PARAM_FLOAT = 'float:';
    const FILTER_FLOAT = '(-?\d+|-?\d*\.\d+)';
    
    const PARAMS_FILTER = '/\{(string:|int:|uint:|float:)?(\w+)\}/';
    
    public function match(string $pattern ,string $method): ?Route;
    public function generate(string $routeName, array $params): ?string;
}
