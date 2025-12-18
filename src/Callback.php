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
     * @var callable|array{class-string, string}
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
     * @param callable|array{class-string, string} $callable Гүйцэтгэх callable объект
     *                                                       (function, Closure, array [Class, 'method'], гэх мэт)
     */
    public function __construct($callable)
    {
        $this->_callable = $callable;
    }
    
    /**
     * Бүртгэлтэй callable-г буцаана.
     *
     * @return callable|array{class-string, string} Гүйцэтгэх callable объект
     */
    public function getCallable()
    {
        return $this->_callable;
    }

    /**
     * Маршрутаас дамжуулагдах параметрүүдийг буцаана.
     *
     * Параметрүүд нь route pattern-ийн динамик хэсгүүдээс гаргаж авсан утгууд юм.
     * Жишээ: /news/{int:id} pattern-д /news/10 path таарвал ['id' => 10] буцаана.
     *
     * @return array<string, mixed> Параметрийн массив (түлхүүр нь параметрийн нэр)
     */
    public function getParameters(): array
    {
        return $this->_params;
    }
    
    /**
     * Callable-д дамжуулах параметрүүдийг онооно.
     *
     * Энэ метод нь ихэвчлэн Router::match() методоор дуудагдаж, route pattern-аас
     * гаргаж авсан параметрүүдийг Callback объектод хадгална.
     *
     * @param array<string, mixed> $parameters Параметрүүд (түлхүүр нь параметрийн нэр)
     * @return void
     */
    public function setParameters(array $parameters)
    {
        $this->_params = $parameters;
    }
}
