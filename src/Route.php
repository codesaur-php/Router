<?php declare(strict_types=1);

namespace codesaur\Router;

class Route
{
    private $_methods;
    private $_pattern;
    private $_callback;

    private $_name;
    private $_params;
    private $_filters;
    
    function __construct(
            array  $methods,
            string $pattern,
                   $callback
    ) {
        $this->setMethods($methods);
        $this->setPattern($pattern);
        $this->setCallback($callback);
    }
    
    public function name(string $name)
    {
        $this->_name = $name;
    }
    
    public function getName(): ?string
    {
        return $this->_name;
    }
    
    public function getPattern(): string
    {
        return $this->_pattern;
    }
    
    public function setPattern(string $path)
    {
        $this->_pattern = rtrim($path, '/');
    }
    
    public function getMethods(): array
    {
        return $this->_methods;
    }
    
    public function setMethods(array $methods)
    {
        $this->_methods = $methods;
    }
    
    public function getFilters(): ?array
    {
        return $this->_filters;
    }
    
    public function setFilters(array $filters)
    {
        $this->_filters = $filters;
    }
    
    public function getParameters(): array
    {
        return $this->_params;
    }
    
    public function setParameters(array $parameters)
    {
        $this->_params = $parameters;
    }
    
    public function getCallback()
    {
        return $this->_callback;
    }
    
    public function setCallback($callback)
    {
        $this->_callback = $callback;
    }
    
    public function getRegex(string $filters_regex): string
    {
        return preg_replace_callback($filters_regex, array(&$this, 'getFilterRegex'), $this->getPattern()) ;
    }
    
    final function getFilterRegex($matches): string
    {
        if (isset($matches[2])
                && isset($this->_filters[$matches[2]])) {
            return $this->_filters[$matches[2]];
        }
        
        return '(\w+)';
    }
}
