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
     * @return array<string, array<string, Callback>> Маршрутын массив.
     *         Структур: ['pattern' => ['METHOD' => Callback объект]]
     */
    public function getRoutes(): array;
    
    /**
     * Өөр Router-ийн маршрутуудыг энэ Router-тэй нэгтгэнэ.
     *
     * Ихэвчлэн модулиудын routes.php-г үндсэн router-тэй нэгтгэхэд ашиглагдана.
     * Нэгтгэхдээ route name-ууд мөн нэгтгэгдэнэ. Хэрэв ижил нэртэй route байвал
     * эхний router-ийнх нь давуу тал болно.
     *
     * @param RouterInterface $router Нэмэлт router (маршрутуудыг нэгтгэх)
     * @return void
     */
    public function merge(RouterInterface $router);
    
    /**
     * Орж ирсэн URL pattern болон HTTP method дээр үндэслэн
     * тохирох маршрутыг хайж буцаана.
     *
     * Таарах маршрут олдвол Callback объект буцаана (динамик параметрүүд
     * аль хэдийн set хийгдсэн байна), олдохгүй бол null буцаана.
     *
     * @param string $pattern Хайлтын URL path (/news/123 гэх мэт)
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH...)
     * @return Callback|null Таарсан маршрут, эсвэл null
     */
    public function match(string $pattern, string $method): Callback|null;

    /**
     * Route name дээр үндэслэн URL үүсгэнэ (reverse routing).
     *
     * Жишээ:
     *     generate('news-view', ['id' => 10])
     *     -> "/news/10"
     *
     * @param string $routeName Маршрутын нэр (name() методоор бүртгэсэн)
     * @param array<string, mixed> $params Дамжуулах параметрүүд
     *                                      (жишээ: ['id' => 10, 'slug' => 'test'])
     * @return string Үүсгэсэн URL path
     * @throws \OutOfRangeException Хэрэв route name олдохгүй бол
     * @throws \InvalidArgumentException Хэрэв параметрийн төрөл буруу бол
     */
    public function generate(string $routeName, array $params): string;
}
