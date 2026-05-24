<?php

namespace codesaur\Router\Tests;

use PHPUnit\Framework\TestCase;

use codesaur\Router\RouterInterface;

/**
 * Adapter pattern test
 *
 * Энэ тест нь зорилго:
 *   `RouterInterface`-ийг 3rd-party router-уудын adapter хэлбэрээр
 *   ашиглах боломжтой эсэхийг батална. Адаптер нь codesaur Router-ийг
 *   огт ашиглахгүйгээр interface-г implement хийнэ.
 *
 * RouterInterface contract:
 * - match() - request -> route resolution
 * - generate() - route name -> URL (reverse routing)
 * - pattern() - route name -> client-side substitution pattern
 * - getRoutes() - бүртгэлтэй route-уудын жагсаалт
 */
class AdapterPatternTest extends TestCase
{
    /**
     * FastRoute-аналог adapter (mock) - RouterInterface-ийг хэрэгжүүлсэн
     */
    public function testFastRouteStyleAdapter(): void
    {
        $adapter = new class implements RouterInterface {
            public function match(string $path, string $method): ?array
            {
                // FastRoute-маягийн dispatch simulation
                if ($path === '/users/42' && $method === 'GET') {
                    return [
                        function ($id) { return "User $id"; },     // [0] callable
                        ['id' => 42],                              // [1] params
                        ['AuthMiddleware', 'CacheMiddleware'],     // [2] middleware
                    ];
                }
                return null;
            }

            public function generate(string $routeName, array $params = []): string
            {
                if ($routeName === 'users.view') {
                    return '/users/' . ($params['id'] ?? '?');
                }
                throw new \OutOfRangeException("Route '$routeName' not found");
            }

            public function pattern(string $routeName): string
            {
                if ($routeName === 'users.view') {
                    return '/users/{id}';
                }
                throw new \OutOfRangeException("Route '$routeName' not found");
            }

            public function getRoutes(): array
            {
                return [
                    '/users/{id:\d+}' => [
                        'GET' => [
                            function ($id) { return "User $id"; },
                            ['AuthMiddleware', 'CacheMiddleware'],
                        ],
                    ],
                ];
            }
        };

        // match() шалгах
        $result = $adapter->match('/users/42', 'GET');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertIsCallable($result[0]);
        $this->assertEquals(['id' => 42], $result[1]);
        $this->assertEquals(['AuthMiddleware', 'CacheMiddleware'], $result[2]);

        $this->assertNull($adapter->match('/unknown', 'GET'));

        // generate() шалгах
        $this->assertSame('/users/42', $adapter->generate('users.view', ['id' => 42]));

        // pattern() шалгах
        $this->assertSame('/users/{id}', $adapter->pattern('users.view'));

        // getRoutes() shape шалгах: 2-tuple [callable, middleware]
        $routes = $adapter->getRoutes();
        $entry = $routes['/users/{id:\d+}']['GET'];
        $this->assertCount(2, $entry);
        $this->assertIsCallable($entry[0]);
        $this->assertEquals(['AuthMiddleware', 'CacheMiddleware'], $entry[1]);
    }

    /**
     * Symfony Routing маягийн adapter (mock)
     */
    public function testSymfonyStyleAdapter(): void
    {
        $adapter = new class implements RouterInterface {
            /** @var array<string, array{callable, array<string,mixed>}> */
            private array $routes = [
                'GET:/api/users' => [['UsersController', 'index'], []],
                'POST:/api/users' => [['UsersController', 'create'], []],
            ];

            public function match(string $path, string $method): ?array
            {
                $key = "$method:$path";
                if (!isset($this->routes[$key])) {
                    return null;
                }
                [$callable, $params] = $this->routes[$key];
                return [$callable, $params, []];  // middleware байхгүй
            }

            public function generate(string $routeName, array $params = []): string
            {
                throw new \OutOfRangeException("Route '$routeName' not found");
            }

            public function pattern(string $routeName): string
            {
                throw new \OutOfRangeException("Route '$routeName' not found");
            }

            public function getRoutes(): array
            {
                $result = [];
                foreach ($this->routes as $key => [$callable, $params]) {
                    [$method, $path] = \explode(':', $key, 2);
                    $result[$path][$method] = [$callable, []];
                }
                return $result;
            }
        };

        $result = $adapter->match('/api/users', 'GET');
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['UsersController', 'index'], $result[0]);
        $this->assertSame([], $result[2]);

        $this->assertNull($adapter->match('/api/users', 'DELETE'));

        // getRoutes shape: 2-tuple [callable, middleware]
        $routes = $adapter->getRoutes();
        $this->assertArrayHasKey('/api/users', $routes);
        $this->assertCount(2, $routes['/api/users']['GET']);
    }

    /**
     * Custom adapter - middleware-тай ба middleware-гүй route холилдсон
     */
    public function testHybridAdapterWithSelectiveMiddleware(): void
    {
        $adapter = new class implements RouterInterface {
            public function match(string $path, string $method): ?array
            {
                // Public route
                if ($path === '/health' && $method === 'GET') {
                    return [
                        function () { return 'OK'; },
                        [],
                        [],  // middleware байхгүй
                    ];
                }

                // Protected route
                if ($path === '/admin' && $method === 'GET') {
                    return [
                        function () { return 'Admin Panel'; },
                        [],
                        ['AuthMiddleware', 'AdminOnlyMiddleware'],
                    ];
                }

                return null;
            }

            public function generate(string $routeName, array $params = []): string
            {
                throw new \OutOfRangeException("Not supported");
            }

            public function pattern(string $routeName): string
            {
                throw new \OutOfRangeException("Not supported");
            }

            public function getRoutes(): array
            {
                return [];
            }
        };

        // Public route - middleware [2] нь хоосон
        $result = $adapter->match('/health', 'GET');
        $this->assertSame([], $result[2]);

        // Protected route - middleware-тэй
        $result = $adapter->match('/admin', 'GET');
        $this->assertEquals(['AuthMiddleware', 'AdminOnlyMiddleware'], $result[2]);
    }

    /**
     * Destructuring patternу - adapter-аас ирсэн өгөгдлийг шууд хэрэглэнэ
     */
    public function testDestructuringAdapterResult(): void
    {
        $adapter = new class implements RouterInterface {
            public function match(string $path, string $method): ?array
            {
                return [
                    fn(int $id) => "Post $id",
                    ['id' => 100],
                    ['CacheMiddleware'],
                ];
            }

            public function generate(string $routeName, array $params = []): string
            {
                return '/post/' . ($params['id'] ?? '?');
            }

            public function pattern(string $routeName): string
            {
                return '/post/{id}';
            }

            public function getRoutes(): array
            {
                return [];
            }
        };

        // Бид RouterInterface contract-аар ажиллана - 
        // adapter дотроос юу хэрхэн хийдгийг мэдэхгүй
        [$callable, $params, $middleware] = $adapter->match('/post/100', 'GET');

        $this->assertIsCallable($callable);
        $this->assertEquals(['id' => 100], $params);
        $this->assertEquals(['CacheMiddleware'], $middleware);

        // Үр дүнг execute
        $output = \call_user_func_array($callable, $params);
        $this->assertEquals('Post 100', $output);
    }
}
