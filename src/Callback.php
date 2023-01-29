<?php

namespace codesaur\Router;

class Callback
{
    private $_callable;
    
    private array $_params = [];
    
    public function __construct($callable)
    {
        $this->_callable = $callable;
    }
    
    public function getCallable()
    {
        return $this->_callable;
    }

    public function getParameters(): array
    {
        return $this->_params;
    }
    
    public function setParameters(array $parameters)
    {
        $this->_params = $parameters;
    }
}
