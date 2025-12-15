<?php

namespace codesaur\Router\Tests;

/**
 * Тест helper класс
 * 
 * Тестүүдэд ашиглах helper классууд
 */
class TestController
{
    public function index(): string
    {
        return 'index';
    }

    public static function staticMethod(): string
    {
        return 'static';
    }
}
