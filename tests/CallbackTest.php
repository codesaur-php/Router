<?php

namespace codesaur\Router\Tests;

use PHPUnit\Framework\TestCase;

use codesaur\Router\Callback;

/**
 * Callback классын тестүүд
 *
 * Энэ тест класс нь Callback классын бүх функцүүдийг шалгана:
 * - Callback үүсгэх
 * - Callable авах
 * - Параметрүүд set/get хийх
 */
class CallbackTest extends TestCase
{
    /**
     * Closure callback үүсгэх тест
     */
    public function testCreateCallbackWithClosure(): void
    {
        $closure = function () {
            return 'test';
        };

        $callback = new Callback($closure);
        $this->assertInstanceOf(Callback::class, $callback);
        $this->assertInstanceOf(\Closure::class, $callback->getCallable());
    }

    /**
     * Function callback үүсгэх тест
     */
    public function testCreateCallbackWithFunction(): void
    {
        $callback = new Callback('strlen');
        $this->assertInstanceOf(Callback::class, $callback);
        $this->assertEquals('strlen', $callback->getCallable());
    }

    /**
     * Array callback үүсгэх тест (controller method)
     */
    public function testCreateCallbackWithArray(): void
    {
        $callable = [TestController::class, 'index'];
        $callback = new Callback($callable);

        $this->assertInstanceOf(Callback::class, $callback);
        $this->assertIsArray($callback->getCallable());
        $this->assertEquals(TestController::class, $callback->getCallable()[0]);
        $this->assertEquals('index', $callback->getCallable()[1]);
    }

    /**
     * Анхны параметрүүд хоосон байх тест
     */
    public function testInitialParametersAreEmpty(): void
    {
        $callback = new Callback(function () {
        });

        $params = $callback->getParameters();
        $this->assertIsArray($params);
        $this->assertEmpty($params);
    }

    /**
     * Параметрүүд set хийх тест
     */
    public function testSetParameters(): void
    {
        $callback = new Callback(function () {
        });

        $params = ['id' => 10, 'name' => 'test'];
        $callback->setParameters($params);

        $this->assertEquals($params, $callback->getParameters());
    }

    /**
     * Параметрүүд дахин set хийх тест
     */
    public function testSetParametersMultipleTimes(): void
    {
        $callback = new Callback(function () {
        });

        $callback->setParameters(['id' => 10]);
        $this->assertEquals(['id' => 10], $callback->getParameters());

        $callback->setParameters(['id' => 20, 'name' => 'new']);
        $this->assertEquals(['id' => 20, 'name' => 'new'], $callback->getParameters());
    }

    /**
     * Олон төрлийн параметр set хийх тест
     */
    public function testSetParametersWithDifferentTypes(): void
    {
        $callback = new Callback(function () {
        });

        $params = [
            'id' => 10,
            'name' => 'test',
            'price' => 19.99,
            'active' => true,
            'tags' => ['tag1', 'tag2']
        ];

        $callback->setParameters($params);
        $result = $callback->getParameters();

        $this->assertEquals(10, $result['id']);
        $this->assertEquals('test', $result['name']);
        $this->assertEquals(19.99, $result['price']);
        $this->assertTrue($result['active']);
        $this->assertIsArray($result['tags']);
    }

    /**
     * Хоосон параметр set хийх тест
     */
    public function testSetEmptyParameters(): void
    {
        $callback = new Callback(function () {
        });

        $callback->setParameters(['id' => 10]);
        $callback->setParameters([]);

        $this->assertEmpty($callback->getParameters());
    }

    /**
     * Callable-г буцаах тест
     */
    public function testGetCallable(): void
    {
        $originalCallable = function ($x) {
            return $x * 2;
        };

        $callback = new Callback($originalCallable);
        $returnedCallable = $callback->getCallable();

        $this->assertEquals($originalCallable, $returnedCallable);
        $this->assertEquals(10, $returnedCallable(5));
    }

    /**
     * Static method callback тест
     */
    public function testStaticMethodCallback(): void
    {
        $callable = [TestController::class, 'staticMethod'];
        $callback = new Callback($callable);

        $this->assertIsArray($callback->getCallable());
        $this->assertEquals(TestController::class, $callback->getCallable()[0]);
    }
}
