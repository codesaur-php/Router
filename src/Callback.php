<?php

namespace codesaur\Router;

/**
 * Class Callback
 *
 * Router-ийн маршрут бүрт тохирох callable (function, method, closure гэх мэт)
 * болон тухайн callable-д дамжуулах параметрүүдийг хадгалах жижиг wrapper класс.
 *
 * Энэ класс нь маршрутын үед динамик параметрүүдийг илгээхэд ашиглагдана.
 *
 * @package codesaur\Router
 */
class Callback
{
    /**
     * Callable объект.
     *
     * Энэ нь function, anonymous function, static method буюу array callable
     * аль нь ч байж болно.
     *
     * @var callable
     */
    private $_callable;
    
    /**
     * Callable-д дамжуулах параметрийн жагсаалт.
     *
     * @var array<string, mixed>
     */
    private array $_params = [];
    
    /**
     * Callback constructor.
     *
     * @param callable $callable Гүйцэтгэх callable объект
     */
    public function __construct($callable)
    {
        $this->_callable = $callable;
    }
    
    /**
     * Бүртгэлтэй callable-г буцаана.
     *
     * @return callable
     */
    public function getCallable()
    {
        return $this->_callable;
    }

    /**
     * Маршрутаас дамжуулагдах параметрүүдийг буцаана.
     *
     * @return array<string, mixed> Параметрийн массив
     */
    public function getParameters(): array
    {
        return $this->_params;
    }
    
    /**
     * Callable-д дамжуулах параметрүүдийг онооно.
     *
     * @param array<string, mixed> $parameters Параметрүүд
     * @return void
     */
    public function setParameters(array $parameters)
    {
        $this->_params = $parameters;
    }
}
