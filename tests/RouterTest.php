<?php

namespace codesaur\Router\Tests;

use PHPUnit\Framework\TestCase;

use codesaur\Router\Route;
use codesaur\Router\Router;

/**
 * Router классын тестүүд
 *
 * Энэ тест класс нь Router классын бүх функцүүдийг шалгана:
 * - Маршрут бүртгэх (GET, POST, PUT, DELETE)
 * - Нэртэй маршрут үүсгэх
 * - Маршрут тааруулах (match) - буцаах төрөл: ?array tuple [callable, params]
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
     * Shape: $routes[pattern][method] = [callable, middleware]
     */
    public function testRegisterSimpleGetRoute(): void
    {
        $callable = function () {
            return 'Hello';
        };

        $this->router->GET('/hello', $callable);
        $routes = $this->router->getRoutes();

        $this->assertArrayHasKey('/hello', $routes);
        $this->assertArrayHasKey('GET', $routes['/hello']);
        $entry = $routes['/hello']['GET'];
        $this->assertCount(2, $entry);
        $this->assertSame($callable, $entry[0]);     // callable
        $this->assertSame([], $entry[1]);            // middleware (хоосон)
    }

    /**
     * POST маршрут бүртгэх тест
     */
    public function testRegisterPostRoute(): void
    {
        $callable = function () {
            return 'POST response';
        };

        $this->router->POST('/users', $callable);
        $routes = $this->router->getRoutes();

        $this->assertArrayHasKey('/users', $routes);
        $this->assertArrayHasKey('POST', $routes['/users']);
        $this->assertSame($callable, $routes['/users']['POST'][0]);
    }

    /**
     * PUT болон DELETE маршрут бүртгэх тест
     */
    public function testRegisterPutAndDeleteRoutes(): void
    {
        $putCallable = function () {
            return 'PUT';
        };
        $deleteCallable = function () {
            return 'DELETE';
        };

        $this->router->PUT('/users/{int:id}', $putCallable);
        $this->router->DELETE('/users/{int:id}', $deleteCallable);

        $routes = $this->router->getRoutes();
        $this->assertArrayHasKey('PUT', $routes['/users/{int:id}']);
        $this->assertArrayHasKey('DELETE', $routes['/users/{int:id}']);
        $this->assertSame($putCallable, $routes['/users/{int:id}']['PUT'][0]);
        $this->assertSame($deleteCallable, $routes['/users/{int:id}']['DELETE'][0]);
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
     * pattern() - filter prefix-уудыг хасч JS-д бэлэн pattern буцаах
     */
    public function testPatternStripsFilterPrefixes(): void
    {
        $this->router->GET('/news/{int:id}/{slug}', function () {
        })->name('news-view');

        $this->assertEquals(
            '/news/{id}/{slug}',
            $this->router->pattern('news-view')
        );
    }

    /**
     * pattern() - бүх filter type-уудыг (int, uint, float, utf8, default) зөв
     * боловсруулах
     */
    public function testPatternHandlesAllFilterTypes(): void
    {
        $this->router->GET(
            '/api/{int:id}/{uint:page}/{float:price}/{utf8:name}/{slug}',
            function () {}
        )->name('mixed');

        $this->assertEquals(
            '/api/{id}/{page}/{price}/{name}/{slug}',
            $this->router->pattern('mixed')
        );
    }

    /**
     * pattern() - параметргүй маршрутыг өөрчлөхгүй буцаах
     */
    public function testPatternKeepsStaticRouteUnchanged(): void
    {
        $this->router->GET('/about', function () {
        })->name('about');

        $this->assertEquals('/about', $this->router->pattern('about'));
    }

    /**
     * pattern() - олдохгүй route name дээр OutOfRangeException шидэх
     */
    public function testPatternThrowsForUnknownRoute(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->router->pattern('nonexistent-route');
    }

    /**
     * Энгийн маршрут тааруулах тест (параметргүй)
     * match() буцаах төрөл: тогтмол [callable, params, middleware] 3-tuple
     */
    public function testMatchSimpleRoute(): void
    {
        $callable = function () {
            return 'matched';
        };

        $this->router->GET('/home', $callable);
        $result = $this->router->match('/home', 'GET');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertSame($callable, $result[0]);
        $this->assertSame([], $result[1]);
        $this->assertSame([], $result[2]);  // middleware хоосон
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
        $this->assertIsArray($result);
        $this->assertEquals(['username' => 'john'], $result[1]);
    }

    /**
     * Integer параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithIntParameter(): void
    {
        $this->router->GET('/news/{int:id}', function () {
        });

        $result = $this->router->match('/news/123', 'GET');
        $this->assertIsArray($result);
        $this->assertEquals(['id' => 123], $result[1]);
        $this->assertIsInt($result[1]['id']);
    }

    /**
     * Сөрөг integer параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithNegativeIntParameter(): void
    {
        $this->router->GET('/number/{int:value}', function () {
        });

        $result = $this->router->match('/number/-42', 'GET');
        $this->assertIsArray($result);
        $this->assertEquals(['value' => -42], $result[1]);
    }

    /**
     * Unsigned integer параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithUintParameter(): void
    {
        $this->router->GET('/page/{uint:page}', function () {
        });

        $result = $this->router->match('/page/5', 'GET');
        $this->assertIsArray($result);
        $this->assertEquals(['page' => 5], $result[1]);
    }

    /**
     * Float параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithFloatParameter(): void
    {
        $this->router->GET('/price/{float:amount}', function () {
        });

        $result = $this->router->match('/price/19.99', 'GET');
        $this->assertIsArray($result);
        $this->assertEquals(['amount' => 19.99], $result[1]);
        $this->assertIsFloat($result[1]['amount']);
    }

    /**
     * Сөрөг float параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithNegativeFloatParameter(): void
    {
        $this->router->GET('/value/{float:num}', function () {
        });

        $result = $this->router->match('/value/-3.14', 'GET');
        $this->assertIsArray($result);
        $this->assertEquals(['num' => -3.14], $result[1]);
    }

    /**
     * Олон параметртэй маршрут тааруулах тест
     */
    public function testMatchRouteWithMultipleParameters(): void
    {
        $this->router->GET('/user/{int:id}/post/{slug}', function () {
        });

        $result = $this->router->match('/user/10/post/my-first-post', 'GET');
        $this->assertIsArray($result);
        $params = $result[1];
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
        $this->assertIsArray($result);
        $this->assertEquals('hello world', $result[1]['query']);
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
     * getRoutes() нь middleware-ыг entry[1]-д буцаах ёстой
     */
    public function testGetRoutesIncludesMiddleware(): void
    {
        $this->router->GET('/admin', function () {
        })->middleware(['AuthMiddleware', 'CsrfMiddleware']);
        $this->router->GET('/public', function () {
        });

        $routes = $this->router->getRoutes();

        $this->assertEquals(['AuthMiddleware', 'CsrfMiddleware'], $routes['/admin']['GET'][1]);
        $this->assertSame([], $routes['/public']['GET'][1]);
    }

    /**
     * getRoutes() entry shape: [callable, middleware]
     */
    public function testGetRoutesEntryShape(): void
    {
        $callable = function () {
        };
        $this->router->GET('/test', $callable)
            ->name('test')
            ->middleware(['MwA']);

        $routes = $this->router->getRoutes();
        $entry = $routes['/test']['GET'];

        $this->assertCount(2, $entry);
        $this->assertSame($callable, $entry[0]);     // [0] callable
        $this->assertSame(['MwA'], $entry[1]);        // [1] middleware

        // Destructuring ажиллах ёстой
        [$cb, $mw] = $entry;
        $this->assertSame($callable, $cb);
        $this->assertSame(['MwA'], $mw);
    }

    /**
     * Controller callback ашиглах тест
     */
    public function testControllerCallback(): void
    {
        $this->router->GET('/test', [TestController::class, 'index']);

        $result = $this->router->match('/test', 'GET');
        $this->assertIsArray($result);
        $callable = $result[0];
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
        $this->assertIsArray($result);
        $this->assertInstanceOf(\Closure::class, $result[0]);
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
        $this->assertIsArray($result);
        $this->assertEquals(['id' => 10], $result[1]);

        // Энгийн маршрут - trailing slash байхгүй байх ёстой
        $this->router->GET('/home', function () {
        });

        $result = $this->router->match('/home', 'GET');
        $this->assertIsArray($result);

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

        $this->assertIsArray($getResult);
        $this->assertIsArray($postResult);
    }

    /**
     * Монгол үсэгтэй параметр тааруулах тест (DEFAULT_REGEX, percent-encoded)
     */
    public function testMatchRouteWithMongolianCharacters(): void
    {
        $this->router->GET('/hello/{name}', function () {
        });

        $encoded = rawurlencode('Наранхүү');
        $result = $this->router->match('/hello/' . $encoded, 'GET');
        $this->assertIsArray($result);
        $this->assertEquals('Наранхүү', $result[1]['name']);
    }

    /**
     * {utf8:} параметр percent-encoded Кирилл тэмдэгтээр тааруулах
     */
    public function testUtf8ParamMatchesPercentEncodedCyrillic(): void
    {
        $this->router->GET('/search/{utf8:query}', function () {
        });

        $encoded = rawurlencode('Монгол');
        $result = $this->router->match('/search/' . $encoded, 'GET');
        $this->assertIsArray($result);
        $this->assertEquals('Монгол', $result[1]['query']);
    }

    /**
     * {utf8:} параметр raw UTF-8 тэмдэгтээр тааруулах (server decoded path)
     */
    public function testUtf8ParamMatchesRawUtf8(): void
    {
        $this->router->GET('/search/{utf8:query}', function () {
        });

        $result = $this->router->match('/search/Монгол', 'GET');
        $this->assertIsArray($result);
        $this->assertEquals('Монгол', $result[1]['query']);
    }

    /**
     * {utf8:} параметр хоосон зайтай текст тааруулах
     */
    public function testUtf8ParamMatchesTextWithSpaces(): void
    {
        $this->router->GET('/search/{utf8:query}', function () {
        });

        $encoded = rawurlencode('Сайн байна уу');
        $result = $this->router->match('/search/' . $encoded, 'GET');
        $this->assertIsArray($result);
        $this->assertEquals('Сайн байна уу', $result[1]['query']);
    }

    /**
     * {utf8:} параметр олон параметртэй route дээр ажиллах
     */
    public function testUtf8ParamWithMultipleParams(): void
    {
        $this->router->GET('/user/{int:id}/{utf8:name}', function () {
        })->name('user');

        $encoded = rawurlencode('Наранхүү');
        $result = $this->router->match('/user/42/' . $encoded, 'GET');
        $this->assertIsArray($result);
        $this->assertEquals(42, $result[1]['id']);
        $this->assertEquals('Наранхүү', $result[1]['name']);
    }

    /**
     * {utf8:} нэртэй route-аас URL generate хийх
     */
    public function testUtf8ParamGenerateUrl(): void
    {
        $this->router->GET('/search/{utf8:query}', function () {
        })->name('search');

        $url = $this->router->generate('search', ['query' => 'Монгол']);
        $this->assertEquals('/search/Монгол', $url);
    }

    /**
     * DEFAULT {string} параметр raw UTF-8 тааруулахгүй байх
     */
    public function testDefaultParamDoesNotMatchRawUtf8(): void
    {
        $this->router->GET('/echo/{word}', function () {
        });

        $result = $this->router->match('/echo/Монгол', 'GET');
        $this->assertNull($result);
    }

    /**
     * HEAD request -> GET handler автомат fallback (RFC 7231 sec. 4.3.2)
     *
     * Explicit HEAD route байхгүй бол GET handler-ыг автоматаар буцаах ёстой.
     */
    public function testHeadFallsBackToGet(): void
    {
        $getHandler = function () {
            return 'GET body';
        };

        $this->router->GET('/resource', $getHandler);

        // HEAD request -> GET handler-д очно
        $result = $this->router->match('/resource', 'HEAD');
        $this->assertIsArray($result);
        $this->assertSame($getHandler, $result[0]);
    }

    /**
     * Explicit HEAD route нь GET fallback-аас өмнө таарна
     *
     * Хэрэв developer тусгайлан HEAD route бүртгэсэн бол GET-ээс илүү давуу талтай.
     */
    public function testExplicitHeadTakesPrecedenceOverGetFallback(): void
    {
        $getHandler = function () {
            return 'GET body';
        };
        $headHandler = function () {
            return 'HEAD only';
        };

        $this->router->GET('/resource', $getHandler);
        $this->router->HEAD('/resource', $headHandler);

        $result = $this->router->match('/resource', 'HEAD');
        $this->assertIsArray($result);
        $this->assertSame($headHandler, $result[0], 'Explicit HEAD ёстой давуу талтай байна');
    }

    /**
     * HEAD fallback нь параметртэй route дээр ч ажиллана
     */
    public function testHeadFallbackPreservesParameters(): void
    {
        $this->router->GET('/news/{int:id}', function () {
        });

        $result = $this->router->match('/news/42', 'HEAD');
        $this->assertIsArray($result);
        $this->assertEquals(['id' => 42], $result[1]);
        $this->assertIsInt($result[1]['id']);
    }

    /**
     * POST-only route-д HEAD таарахгүй (null буцаана)
     *
     * HEAD нь зөвхөн GET-ээс fallback хийгдэх ёстой, бусад method-аас биш.
     */
    public function testHeadDoesNotFallbackToPost(): void
    {
        $this->router->POST('/api/data', function () {
        });

        $result = $this->router->match('/api/data', 'HEAD');
        $this->assertNull($result, 'POST-only route-д HEAD таарах ёсгүй');
    }

    /**
     * Route бүртгэхэд Route object буцаах ёстой
     */
    public function testRegisteringRouteReturnsRouteObject(): void
    {
        $route = $this->router->GET('/test', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/test', $route->pattern);
    }

    /**
     * Route::name() дуудлага нь Router-д нэрийг бүртгэх ёстой
     */
    public function testRouteNameRegistersNameInRouter(): void
    {
        $this->router->GET('/news/{int:id}', function () {
        })->name('news.view');

        $url = $this->router->generate('news.view', ['id' => 42]);
        $this->assertEquals('/news/42', $url);
    }

    /**
     * Route::name() нь Route өөрийгөө буцаах ёстой (chainable)
     */
    public function testRouteNameReturnsSelfForChaining(): void
    {
        $route = $this->router->GET('/foo', function () {
        });

        $returned = $route->name('foo');
        $this->assertSame($route, $returned);
    }

    /**
     * Олон нэр нэг pattern-руу заах нь exception шидэхгүй (idempotent registration)
     */
    public function testMultipleNamesOnSamePatternAllResolveToIt(): void
    {
        $this->router->GET('/about', function () {
        })->name('about')->name('about.page')->name('static.about');

        $this->assertEquals('/about', $this->router->generate('about'));
        $this->assertEquals('/about', $this->router->generate('about.page'));
        $this->assertEquals('/about', $this->router->generate('static.about'));
    }

    /**
     * Router::registerName() public API нь шууд дуудах боломжтой
     */
    public function testRegisterNamePublicApi(): void
    {
        $this->router->GET('/foo', function () {
        });

        // Pattern-ыг шууд бүртгэх
        $this->router->registerName('foo', '/foo');

        $this->assertEquals('/foo', $this->router->generate('foo'));
    }

    /**
     * Ижил нэрийг өөр pattern-д оноох гэвэл strict mode-ийн дагуу exception шиднэ
     */
    public function testDuplicateNameOnDifferentPatternThrows(): void
    {
        $this->router->GET('/users', function () {
        })->name('users');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Route name [users] is already registered');

        $this->router->GET('/admin', function () {
        })->name('users');
    }

    /**
     * Ижил нэрийг ижил pattern-д давтан оноох нь idempotent (exception шидэхгүй)
     */
    public function testDuplicateNameOnSamePatternIsIdempotent(): void
    {
        $this->router->GET('/users', function () {
        })->name('users')->name('users');

        $this->assertEquals('/users', $this->router->generate('users'));
    }

    /**
     * Route-ийн pattern property нь readonly байх ёстой
     */
    public function testRoutePatternIsReadonly(): void
    {
        $route = $this->router->GET('/test', function () {
        });

        $this->expectException(\Error::class);
        // PHP 8.2 readonly class - property assignment runtime error
        $route->pattern = '/changed';
    }

    /**
     * Route::middleware() нь Router-д middleware бүртгэх ёстой
     * Буцаах нь $result[2] позицийн дагуу
     */
    public function testRouteMiddlewareRegistersInRouter(): void
    {
        $this->router->GET('/admin', function () {
        })->middleware(['AuthMiddleware', 'CsrfMiddleware']);

        $result = $this->router->match('/admin', 'GET');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['AuthMiddleware', 'CsrfMiddleware'], $result[2]);
    }

    /**
     * Middleware байхгүй route-д $result[2] нь хоосон array
     */
    public function testRouteWithoutMiddlewareHasEmptyArray(): void
    {
        $this->router->GET('/public', function () {
        });

        $result = $this->router->match('/public', 'GET');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertSame([], $result[2]);
    }

    /**
     * Олон `->middleware()` chain нь append semantics
     */
    public function testMultipleMiddlewareCallsAppend(): void
    {
        $this->router->GET('/x', function () {
        })
            ->middleware(['A'])
            ->middleware(['B', 'C']);

        $result = $this->router->match('/x', 'GET');
        $this->assertEquals(['A', 'B', 'C'], $result[2]);
    }

    /**
     * Pattern-уудын middleware тус тусдаа isolated байх ёстой
     */
    public function testMiddlewareIsolationPerPattern(): void
    {
        $this->router->GET('/a', function () {
        })->middleware(['MwA']);

        $this->router->GET('/b', function () {
        })->middleware(['MwB']);

        $resultA = $this->router->match('/a', 'GET');
        $resultB = $this->router->match('/b', 'GET');

        $this->assertEquals(['MwA'], $resultA[2]);
        $this->assertEquals(['MwB'], $resultB[2]);
    }

    /**
     * Параметртэй route-ийн middleware match-д хэвээр дамжих
     */
    public function testParameterizedRouteMiddleware(): void
    {
        $this->router->GET('/users/{int:id}', function () {
        })->middleware(['AuthMiddleware']);

        $result = $this->router->match('/users/42', 'GET');
        $this->assertEquals(['AuthMiddleware'], $result[2]);
        $this->assertEquals(['id' => 42], $result[1]);
    }

    /**
     * HEAD fallback нь GET handler-ийн middleware-ыг өвлөнө
     */
    public function testHeadFallbackInheritsGetMiddleware(): void
    {
        $this->router->GET('/cached', function () {
        })->middleware(['CacheMiddleware', 'AuthMiddleware']);

        $result = $this->router->match('/cached', 'HEAD');
        $this->assertIsArray($result);
        $this->assertEquals(['CacheMiddleware', 'AuthMiddleware'], $result[2]);
    }

    /**
     * Closure middleware дамжуулж бүртгэх боломжтой
     */
    public function testClosureMiddlewareSupported(): void
    {
        $closureMiddleware = function ($req, $handler) {
            return $handler->handle($req);
        };

        $this->router->GET('/test', function () {
        })->middleware([$closureMiddleware]);

        $result = $this->router->match('/test', 'GET');
        $this->assertCount(1, $result[2]);
        $this->assertSame($closureMiddleware, $result[2][0]);
    }

    /**
     * registerMiddleware() public API нь шууд дуудах боломжтой
     */
    public function testRegisterMiddlewarePublicApi(): void
    {
        $this->router->GET('/foo', function () {
        });

        // (pattern, method) хосын дагуу middleware-ийг post-hoc-оор бүртгэх
        $this->router->registerMiddleware('/foo', 'GET', ['MiddlewareA', 'MiddlewareB']);

        $result = $this->router->match('/foo', 'GET');
        $this->assertEquals(['MiddlewareA', 'MiddlewareB'], $result[2]);
    }

    /**
     * Middleware нь (pattern, method) хосын дагуу scope-той - ижил pattern дээр
     * өөр өөр method-уудын middleware нь тус тусдаа байх ёстой
     */
    public function testMiddlewareIsolationPerMethodOnSamePattern(): void
    {
        $this->router->GET('/api/users', function () {
        });
        $this->router->POST('/api/users', function () {
        })->middleware(['AuthMiddleware', 'CsrfMiddleware']);

        $getResult  = $this->router->match('/api/users', 'GET');
        $postResult = $this->router->match('/api/users', 'POST');

        $this->assertSame([], $getResult[2]);
        $this->assertEquals(['AuthMiddleware', 'CsrfMiddleware'], $postResult[2]);
    }

    /**
     * Compound method (GET_POST) ашигласан үед middleware нь дотоод method бүрд
     * хувилагдаж бүртгэгдэх ёстой
     */
    public function testCompoundMethodMiddlewareAppliesToEachMethod(): void
    {
        $this->router->GET_POST('/foo', function () {
        })->middleware(['Auth']);

        $getResult  = $this->router->match('/foo', 'GET');
        $postResult = $this->router->match('/foo', 'POST');

        $this->assertEquals(['Auth'], $getResult[2]);
        $this->assertEquals(['Auth'], $postResult[2]);
    }

    /**
     * generate() - зөвхөн зарим параметр өгөгдсөн үед яг нэрлэсэн placeholder
     * нь солигдох ёстой (ижил filter-тэй өөр placeholder-т халдахгүй)
     */
    public function testGeneratePartialParamsReplacesCorrectPlaceholder(): void
    {
        $this->router->GET('/point/{int:x}/{int:y}', function () {
        })->name('point');

        $url = $this->router->generate('point', ['y' => 5]);
        $this->assertEquals('/point/{int:x}/5', $url);
    }

    /**
     * generate() - параметрийн утга доторх $1, \1 зэрэг тэмдэгтүүд regex
     * backreference болж тайлагдах ёсгүй
     */
    public function testGenerateValueWithDollarSignIsLiteral(): void
    {
        $this->router->GET('/search/{query}', function () {
        })->name('search');

        $url = $this->router->generate('search', ['query' => 'a$1b']);
        $this->assertEquals('/search/a$1b', $url);
    }

    /**
     * Литерал сегмент доторх цэг (.) regex wildcard байх ёсгүй
     */
    public function testStaticSegmentDotIsLiteralInMatch(): void
    {
        $this->router->GET('/files/{int:id}/manifest.json', function () {
        });

        $result = $this->router->match('/files/1/manifest.json', 'GET');
        $this->assertIsArray($result);
        $this->assertEquals(['id' => 1], $result[1]);

        $result = $this->router->match('/files/1/manifestXjson', 'GET');
        $this->assertNull($result, 'Цэг нь wildcard биш, литерал байх ёстой');
    }

    /**
     * match() буцаах нь үргэлж 3 элементтэй tuple - destructuring шууд ажиллах
     */
    public function testMatchAlwaysReturnsThreeElementTuple(): void
    {
        $this->router->GET('/x', function () {
        });
        $this->router->GET('/y', function () {
        })->middleware(['M']);

        // Middleware-гүй
        [$callable, $params, $middleware] = $this->router->match('/x', 'GET');
        $this->assertIsCallable($callable);
        $this->assertSame([], $params);
        $this->assertSame([], $middleware);

        // Middleware-тэй
        [$callable, $params, $middleware] = $this->router->match('/y', 'GET');
        $this->assertIsCallable($callable);
        $this->assertSame([], $params);
        $this->assertSame(['M'], $middleware);
    }
}
