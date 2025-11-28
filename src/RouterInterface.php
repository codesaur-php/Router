<?php

namespace codesaur\Router;

/**
 * Interface RouterInterface
 *
 * Хөнгөн жинтэй маршрутчилал (routing) хийхийн тулд router хэрэгжүүлэх ёстой
 * үндсэн шаардлагуудыг тодорхойлсон интерфэйс.
 *
 * Энэ интерфэйс нь:
 *  - Бүртгэлтэй бүх маршрутыг авах
 *  - Өөр router-тай нэгтгэх
 *  - Request-тэй таарах маршрутыг шалгах
 *  - Route name-ээс URL үүсгэх
 * зэрэг боломжуудыг заавал хэрэгжүүлэх шаардлагатай.
 *
 * @package codesaur\Router
 */
interface RouterInterface
{
    /**
     * Router дотор бүртгэлтэй бүх маршрутын жагсаалтыг буцаана.
     *
     * @return array Маршрутын массив
     */
    public function getRoutes(): array;
    
    /**
     * Өөр Router-ийн маршрутуудыг энэ Router-тэй нэгтгэнэ.
     *
     * Ихэвчлэн модулиудын routes.php-г үндсэн router-тэй нэгтгэхэд ашиглагдана.
     *
     * @param RouterInterface $router Нэмэлт router
     * @return void
     */
    public function merge(RouterInterface $router);
    
    /**
     * Орж ирсэн URL pattern болон HTTP method дээр үндэслэн
     * тохирох маршрутыг хайж буцаана.
     *
     * Таарах маршрут олдвол Callback объект буцаана,
     * олдохгүй бол null буцаана.
     *
     * @param string $pattern Хайлтын URL (/news/123 гэх мэт)
     * @param string $method HTTP method (GET, POST, PUT, DELETE...)
     * @return Callback|null Таарсан маршрут
     */
    public function match(string $pattern, string $method): Callback|null;

    /**
     * Route name дээр үндэслэн URL үүсгэнэ.
     *
     * Жишээ:
     *     generate('news-view', ['id' => 10])
     *     -> "/news/10"
     *
     * @param string $routeName Маршрутын нэр
     * @param array<string, mixed> $params Дамжуулах параметрүүд
     * @return string Үүсгэсэн URL
     */
    public function generate(string $routeName, array $params): string;
}
