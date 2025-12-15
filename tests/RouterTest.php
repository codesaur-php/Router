<?php

namespace codesaur\Router\Tests;

use PHPUnit\Framework\TestCase;

use codesaur\Router\Callback;
use codesaur\Router\Router;

/**
 * Router классын тестүүд
 *
 * Энэ тест класс нь Router классын бүх функцүүдийг шалгана:
 * - Маршрут бүртгэх (GET, POST, PUT, DELETE)
 * - Нэртэй маршрут үүсгэх
 * - Маршрут тааруулах (match)
 * - URL үүсгэх (generate)
 * - Router нэгтгэх (merge)
 * - Параметрийн төрөл шалгах (int, uint, float, string)
 */
class RouterTest extends TestCase
{
    /**
     * @var Router
     */
    private Router $router;

    /**
     * Тест бүрийн өмнө router объект үүсгэнэ
     */
    protected function setUp(): void
    {
        $this->router = new Router();
    }

    /**
     * Энгийн GET маршрут бүртгэх тест
     */
    public function testRegisterSimpleGetRoute(): void
    {
        $callback = function () {
            return 'Hello';
        };

        $this->router->GET('/hello', $callback);
        $routes = $this->router->getRoutes();

        $this->assertArrayHasKey('/hello', $routes);
        $this->assertArrayHasKey('GET', $routes['/hello']);
        $this->assertInstanceOf(Callback::class, $routes['/hello']['GET']);
    }

    /**
     * POST маршрут бүртгэх тест
     */
    public function testRegisterPostRoute(): void
    {
        $callback = function () {
            return 'POST response';
        };

        $this->router->POST('/users', $callback);
        $routes = $this->router->getRoutes();

        $this->assertArrayHasKey('/users', $routes);
        $this->assertArrayHasKey('POST', $routes['/users']);
    }

    /**
     * PUT болон DELETE маршрут бүртгэх тест
     */
    public function testRegisterPutAndDeleteRoutes(): void
    {
        $putCallback = function () {
            return 'PUT';
        };
        $deleteCallback = function () {
            return 'DELETE';
        };

        $this->router->PUT('/users/{int:id}', $putCallback);
        $this->router->DELETE('/users/{int:id}', $deleteCallback);

        $routes = $this->router->getRoutes();
        $this->assertArrayHasKey('PUT', $routes['/users/{int:id}']);
        $this->assertArrayHasKey('DELETE', $routes['/users/{int:id}']);
    }

    /**
     * Буруу маршрут тохиргоо үед exception шидэх тест
     */
    public function testInvalidRouteConfigurationThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->router->GET('', function () {
        });
    }

    /**
     * Буруу callback үед exception шидэх тест
     */
    public function testInvalidCallbackThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->router->GET('/test', 'invalid_callback');
    }

    /**
     * Нэртэй маршрут үүсгэх тест
     */
    public function testNamedRoute(): void
    {
        $this->router->GET('/news/{int:id}', function () {
        })->name('news-view');

        $url = $this->router->generate('news-view', ['id' => 10]);
        $this->assertEquals('/news/10', $url);
    }

    /**
     * Энгийн маршрут тааруулах тест (параметргүй)
     */
    public function testMatchSimpleRoute(): void
    {
        $callback = function () {
            return 'matched';
        };

        $this->router->GET('/home', $callback);
        $result = $this->router->match('/home', 'GET');

        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals($callback, $result->getCallable());
    }

    /**
     * Таарахгүй маршрут үед null буцаах тест
     */
    public function testMatchReturnsNullForNonMatchingRoute(): void
    {
        $this->router->GET('/home', function () {
        });

        $result = $this->router->match('/notfound', 'GET');
        $this->assertNull($result);
    }

    /**
     * Буруу HTTP method үед null буцаах тест
     */
    public function testMatchReturnsNullForWrongMethod(): void
    {
        $this->router->GET('/home', function () {
        });

        $result = $this->router->match('/home', 'POST');
        $this->assertNull($result);
    }

    /**
     * String параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithStringParameter(): void
    {
        $this->router->GET('/user/{username}', function () {
        });

        $result = $this->router->match('/user/john', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals(['username' => 'john'], $result->getParameters());
    }

    /**
     * Integer параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithIntParameter(): void
    {
        $this->router->GET('/news/{int:id}', function () {
        });

        $result = $this->router->match('/news/123', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals(['id' => 123], $result->getParameters());
        $this->assertIsInt($result->getParameters()['id']);
    }

    /**
     * Сөрөг integer параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithNegativeIntParameter(): void
    {
        $this->router->GET('/number/{int:value}', function () {
        });

        $result = $this->router->match('/number/-42', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals(['value' => -42], $result->getParameters());
    }

    /**
     * Unsigned integer параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithUintParameter(): void
    {
        $this->router->GET('/page/{uint:page}', function () {
        });

        $result = $this->router->match('/page/5', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals(['page' => 5], $result->getParameters());
    }

    /**
     * Float параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithFloatParameter(): void
    {
        $this->router->GET('/price/{float:amount}', function () {
        });

        $result = $this->router->match('/price/19.99', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals(['amount' => 19.99], $result->getParameters());
        $this->assertIsFloat($result->getParameters()['amount']);
    }

    /**
     * Сөрөг float параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithNegativeFloatParameter(): void
    {
        $this->router->GET('/value/{float:num}', function () {
        });

        $result = $this->router->match('/value/-3.14', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals(['num' => -3.14], $result->getParameters());
    }

    /**
     * Олон параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithMultipleParameters(): void
    {
        $this->router->GET('/user/{int:id}/post/{slug}', function () {
        });

        $result = $this->router->match('/user/10/post/my-first-post', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $params = $result->getParameters();
        $this->assertEquals(10, $params['id']);
        $this->assertEquals('my-first-post', $params['slug']);
    }

    /**
     * URL encode хийгдсэн параметр тааруулах тест
     */
    public function testMatchRouteWithUrlEncodedParameter(): void
    {
        $this->router->GET('/search/{query}', function () {
        });

        $encoded = rawurlencode('hello world');
        $result = $this->router->match('/search/' . $encoded, 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals('hello world', $result->getParameters()['query']);
    }

    /**
     * URL үүсгэх тест (параметргүй)
     */
    public function testGenerateUrlWithoutParameters(): void
    {
        $this->router->GET('/home', function () {
        })->name('home');

        $url = $this->router->generate('home');
        $this->assertEquals('/home', $url);
    }

    /**
     * URL үүсгэх тест (integer параметртэй)
     */
    public function testGenerateUrlWithIntParameter(): void
    {
        $this->router->GET('/news/{int:id}', function () {
        })->name('news-view');

        $url = $this->router->generate('news-view', ['id' => 42]);
        $this->assertEquals('/news/42', $url);
    }

    /**
     * URL үүсгэх тест (unsigned integer параметртэй)
     */
    public function testGenerateUrlWithUintParameter(): void
    {
        $this->router->GET('/page/{uint:page}', function () {
        })->name('page-view');

        $url = $this->router->generate('page-view', ['page' => 5]);
        $this->assertEquals('/page/5', $url);
    }

    /**
     * URL үүсгэх тест (float параметртэй)
     */
    public function testGenerateUrlWithFloatParameter(): void
    {
        $this->router->GET('/price/{float:amount}', function () {
        })->name('price-view');

        $url = $this->router->generate('price-view', ['amount' => 19.99]);
        $this->assertEquals('/price/19.99', $url);
    }

    /**
     * URL үүсгэх тест (олон параметртэй)
     */
    public function testGenerateUrlWithMultipleParameters(): void
    {
        $this->router->GET('/user/{int:id}/post/{slug}', function () {
        })->name('user-post');

        $url = $this->router->generate('user-post', [
            'id' => 10,
            'slug' => 'my-post'
        ]);
        $this->assertEquals('/user/10/post/my-post', $url);
    }

    /**
     * Олдохгүй route name үед exception шидэх тест
     */
    public function testGenerateThrowsExceptionForNonExistentRouteName(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->router->generate('non-existent', []);
    }

    /**
     * Буруу integer параметр үед exception шидэх тест
     */
    public function testGenerateThrowsExceptionForInvalidIntParameter(): void
    {
        $this->router->GET('/news/{int:id}', function () {
        })->name('news-view');

        $this->expectException(\InvalidArgumentException::class);
        $this->router->generate('news-view', ['id' => 'not-a-number']);
    }

    /**
     * Буруу unsigned integer параметр үед exception шидэх тест
     */
    public function testGenerateThrowsExceptionForInvalidUintParameter(): void
    {
        $this->router->GET('/page/{uint:page}', function () {
        })->name('page-view');

        $this->expectException(\InvalidArgumentException::class);
        $this->router->generate('page-view', ['page' => -1]);
    }

    /**
     * Буруу float параметр үед exception шидэх тест
     */
    public function testGenerateThrowsExceptionForInvalidFloatParameter(): void
    {
        $this->router->GET('/price/{float:amount}', function () {
        })->name('price-view');

        $this->expectException(\InvalidArgumentException::class);
        $this->router->generate('price-view', ['amount' => 'not-a-number']);
    }

    /**
     * Router нэгтгэх тест
     */
    public function testMergeRouters(): void
    {
        $router1 = new Router();
        $router1->GET('/route1', function () {
        });

        $router2 = new Router();
        $router2->GET('/route2', function () {
        });

        $this->router->merge($router1);
        $this->router->merge($router2);

        $routes = $this->router->getRoutes();
        $this->assertArrayHasKey('/route1', $routes);
        $this->assertArrayHasKey('/route2', $routes);
    }

    /**
     * Router нэгтгэх тест (route name-уудтай)
     */
    public function testMergeRoutersWithNamedRoutes(): void
    {
        $router1 = new Router();
        $router1->GET('/route1', function () {
        })->name('route1');

        $router2 = new Router();
        $router2->GET('/route2', function () {
        })->name('route2');

        $this->router->merge($router1);
        $this->router->merge($router2);

        $this->assertEquals('/route1', $this->router->generate('route1'));
        $this->assertEquals('/route2', $this->router->generate('route2'));
    }

    /**
     * Бүртгэлтэй маршрутуудын жагсаалт авах тест
     */
    public function testGetRoutes(): void
    {
        $this->router->GET('/route1', function () {
        });
        $this->router->POST('/route2', function () {
        });

        $routes = $this->router->getRoutes();
        $this->assertIsArray($routes);
        $this->assertCount(2, $routes);
        $this->assertArrayHasKey('/route1', $routes);
        $this->assertArrayHasKey('/route2', $routes);
    }

    /**
     * Controller callback ашиглах тест
     */
    public function testControllerCallback(): void
    {
        $this->router->GET('/test', [TestController::class, 'index']);

        $result = $this->router->match('/test', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $callable = $result->getCallable();
        $this->assertIsArray($callable);
        $this->assertEquals(TestController::class, $callable[0]);
        $this->assertEquals('index', $callable[1]);
    }

    /**
     * Closure callback ашиглах тест
     */
    public function testClosureCallback(): void
    {
        $closure = function () {
            return 'closure';
        };

        $this->router->GET('/closure', $closure);
        
        // Таарахгүй маршрут шалгах
        $result = $this->router->match('/route', 'GET');
        $this->assertNull($result);

        // Таарах маршрут шалгах
        $result = $this->router->match('/closure', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertInstanceOf(\Closure::class, $result->getCallable());
    }

    /**
     * Trailing slash-тэй маршрут тааруулах тест
     * 
     * Тайлбар: Router нь trailing slash-ийг зөвхөн параметртэй маршрутуудад дэмждэг.
     * Энгийн маршрутуудад trailing slash байхгүй байх ёстой.
     */
    public function testMatchRouteWithTrailingSlash(): void
    {
        // Параметртэй маршрут - trailing slash дэмжинэ
        $this->router->GET('/user/{int:id}', function () {
        });

        $result = $this->router->match('/user/10/', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals(['id' => 10], $result->getParameters());
        
        // Энгийн маршрут - trailing slash байхгүй байх ёстой
        $this->router->GET('/home', function () {
        });
        
        $result = $this->router->match('/home', 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        
        $result = $this->router->match('/home/', 'GET');
        $this->assertNull($result, 'Энгийн маршрут trailing slash-тэй таарах ёсгүй');
    }

    /**
     * Олон HTTP method-тэй маршрут тааруулах тест
     */
    public function testMatchRouteWithMultipleMethods(): void
    {
        $this->router->GET('/api/data', function () {
        });
        $this->router->POST('/api/data', function () {
        });

        $getResult = $this->router->match('/api/data', 'GET');
        $postResult = $this->router->match('/api/data', 'POST');

        $this->assertInstanceOf(Callback::class, $getResult);
        $this->assertInstanceOf(Callback::class, $postResult);
    }

    /**
     * Монгол үсэгтэй параметр тааруулах тест
     */
    public function testMatchRouteWithMongolianCharacters(): void
    {
        $this->router->GET('/hello/{name}', function () {
        });

        $encoded = rawurlencode('Наранхүү');
        $result = $this->router->match('/hello/' . $encoded, 'GET');
        $this->assertInstanceOf(Callback::class, $result);
        $this->assertEquals('Наранхүү', $result->getParameters()['name']);
    }
}
