<?php

namespace codesaur\Router\Example;

/**
 * -----------------------------------------------------------------------------
 * codesaur/router - Жишээ скрипт
 * -----------------------------------------------------------------------------
 *
 * Энэ файл нь codesaur/router ашиглан маршрутуудыг үүсгэх,
 * тааруулах (match), болон callback гүйцэтгэх жишээг бүрэн харуулна.
 *
 * Багтаасан жишээнүүд:
 *   • GET / POST маршрут бүртгэх
 *   • Динамик параметртэй маршрут авах
 *       {firstname}, {int:id}, {uint:b}, {float:number} гэх мэт
 *   • Нэртэй route → URL generate хийх
 *   • Controller болон Closure callback хоёрыг хоёуланг нь дэмжих
 *   • 10,000 маршрутын generate & match хурд шалгах тест
 *
 * -----------------------------------------------------------------------------
 */

\ini_set('display_errors', 'On');
\error_reporting(\E_ALL);

require '../vendor/autoload.php';

use codesaur\Router\Callback;
use codesaur\Router\Router;

/**
 * ExampleController - Демонстрацийн зориулалттай controller
 *
 * Энэ controller нь codesaur/router пакетийн бүх боломжуудыг харуулах
 * жишээ method-уудыг агуулна.
 *
 * @package codesaur\Router\Example
 */
class ExampleController
{
    /**
     * Энгийн GET / маршрут
     *
     * Үндсэн хуудас - бүх маршрутуудын жагсаалтыг харуулна.
     *
     * @return void
     */
    public function index()
    {
        // Script байгаа үндсэн замыг автоматаар тодорхойлох
        $base = \rtrim(\dirname($_SERVER['SCRIPT_NAME']), '/');
        echo '<h3>Энэ бол codesaur/router багцын туршилтын жишээ!</h3>';
        echo "<p>Доорх нь энэ жишээ файл дээр бүртгэгдсэн бүх маршрутууд:</p>";
        echo "<ul>";
        echo "<li><a href='{$base}/echo/test'>/echo/test</a></li>";
        echo "<li><a href='{$base}/hello/Тэмүжин/Хан'>/hello/{firstname}/{lastname}</a></li>";
        echo "<li><a href='{$base}/test-all-filters/word/f/f/10/20/1.5/sample'>/test-all-filters/*</a></li>";
        echo "<li><a href='{$base}/numeric/7.53'>/numeric/{float}</a></li>";
        echo "<li><a href='{$base}/sum/5/7'>/sum/{int:a}/{uint:b}</a></li>";
        echo "<li><a href='{$base}/generate'>/generate (URL үүсгэх тест)</a></li>";
        echo "<li><a href='{$base}/speed/test'>/speed/test (Гүйцэтгэл тест)</a></li>";
        echo "<li><a href='{$base}/сайнуу/Наранхүү'>/сайнуу/Наранхүү (POST хүсэлт байх ёстой)</a></li>";
        echo "</ul>";
    }

    /**
     * Нэр угтах
     *
     * Хоёр параметртэй маршрутын жишээ.
     *
     * @param string $firstname Нэрийн эхний хэсэг
     * @param string|null $lastname Нэрийн сүүлийн хэсэг (сонголттой)
     * @return void
     */
    public function greetings(string $firstname, ?string $lastname = null)
    {
        $name = $firstname;
        if (!empty($lastname)) {
            $name .= " $lastname";
        }
        echo "<br/>Сайн байна уу, $name!";
    }

    /**
     * Нэг үг хэвлэх
     *
     * Нэг параметртэй маршрутын жишээ.
     *
     * @param string $singleword Хэвлэх үг
     * @return void
     */
    public function echo(string $singleword)
    {
        echo "<br/>Нэг үг: $singleword";
    }

    /**
     * Бүх төрлийн filter шалгах
     *
     * Олон төрлийн параметр (string, int, uint, float) агуулсан маршрутын жишээ.
     *
     * @param string $singleword Энгийн string параметр
     * @param string $firstname Энгийн string параметр
     * @param string $lastname Энгийн string параметр
     * @param int $a INTEGER төрлийн параметр (сөрөг тоо зөвшөөрнө)
     * @param int $b UNSIGNED INTEGER төрлийн параметр (зөвхөн эерэг)
     * @param float $number FLOAT төрлийн параметр
     * @param string $word Энгийн string параметр
     * @return void
     */
    public function test(
        string $singleword,
        string $firstname,
        string $lastname,
        int $a,
        int $b,
        float $number,
        string $word
    ) {
        var_dump($singleword, $firstname, $lastname, $a, $b, $number, $word);
    }

    /**
     * POST request хүлээн авах
     *
     * POST method-тай маршрутын жишээ. $_POST массив-аас өгөгдөл уншина.
     *
     * @return void
     */
    public function post()
    {
        if (empty($_POST['firstname'])) {
            die("Алдаа: Хүсэлт буруу байна!");
        }

        $name = $_POST['firstname'];
        if (!empty($_POST['lastname'])) {
            $name .= " {$_POST['lastname']}";
        }

        echo "<br/>Сайн уу, $name!";
    }

    /**
     * Float параметртэй тест
     *
     * FLOAT төрлийн параметртэй маршрутын жишээ.
     *
     * @param float $number Бутархай тоо
     * @return void
     */
    public function number(float $number)
    {
        \var_dump($number);
    }
}

/* -----------------------------------------------------------------------------
 *  ROUTES - Маршрут бүртгэх хэсэг
 *
 *  Доорх маршрутууд нь codesaur/router пакетийн бүх боломжуудыг харуулна:
 *  - GET, POST method-ууд
 *  - Динамик параметрүүд ({int:id}, {uint:page}, {float:price}, {slug})
 *  - Нэртэй маршрутууд (named routes)
 *  - Controller болон Closure callback-ууд
 * ---------------------------------------------------------------------------*/

$router = new Router();

/* Энгийн GET / маршрут - үндсэн хуудас */
$router->GET('/', [ExampleController::class, 'index']);

/* POST /сайнуу/{firstname} - Монгол үсэг дэмжих жишээ */
$router->POST('/сайнуу/{firstname}', [ExampleController::class, 'greetings']);

/* GET /echo/{singleword} - Нэг параметртэй, нэртэй маршрут */
$router->GET('/echo/{singleword}', [ExampleController::class, 'echo'])
    ->name('echo');

/* GET /hello/{firstname}/{lastname} - Хоёр параметртэй, нэртэй маршрут */
$router->GET('/hello/{firstname}/{lastname}', [ExampleController::class, 'greetings'])
    ->name('hello');

/* Бүх төрлийн regex filter-тэй маршрут - int, uint, float, string */
$router->GET('/test-all-filters/{singleword}/{firstname}/{lastname}/{int:a}/{uint:b}/{float:number}/{word}',
    [ExampleController::class, 'test']
)->name('test-filters');

/* POST form test - POST method-тай маршрут */
$router->POST('/hello', [ExampleController::class, 'post']);

/* Float parameter - FLOAT төрлийн параметртэй маршрут */
$router->GET('/numeric/{float:number}', [ExampleController::class, 'number'])
    ->name('float');

/* Closure - нийлбэр - Closure callback ашиглах жишээ */
$router->GET('/sum/{int:a}/{uint:b}', function (int $a, int $b) {
    $sum = $a + $b;
    echo "<br/>$a + $b = $sum";
})->name('sum');

/* URL generate тест - Нэртэй маршрутуудын URL үүсгэх жишээ */
$router->GET('/generate', function () use ($router)
{
    echo "<h3>URL үүсгэх тест (generate)</h3>";

    echo 'echo → ' . $router->generate('echo', ['singleword' => 'Амжилт']) . '<br/>';
    echo 'hello → ' . $router->generate('hello', [
        'firstname' => 'Наранхүү',
        'lastname' => 'codesaur'
    ]) . '<br/>';
    echo 'sum → ' . $router->generate('sum', ['a' => 7, 'b' => 13]) . '<br/>';
    echo 'float → ' . $router->generate('float', ['number' => 753.9]) . '<br/>';
    echo 'test-filters → ' . $router->generate('test-filters', [
        'singleword' => 'demo',
        'firstname' => 'Болд',
        'lastname' => 'Баатар',
        'a' => -10,
        'b' => 999,
        'number' => 17.55,
        'word' => 'Жишээ текст'
    ]) . '<br/>';

    // Script байгаа үндсэн замыг автоматаар тодорхойлох
    $base = \rtrim(\dirname($_SERVER['SCRIPT_NAME']), '/');
    if ($base === '') {
        $base = '/';
    }
    echo '<br/><b>Гүйцэтгэл тест рүү:</b> <a href="' . $base . '/speed/test">/speed/test</a>';
});

/* -----------------------------------------------------------------------------
 *  ГҮЙЦЭТГЭЛ ШАЛГАХ - 10,000 generate & match
 *
 *  Энэ маршрут нь router-ийн гүйцэтгэлийг шалгана:
 *  - 10,000 удаа URL generate хийх
 *  - 10,000 удаа маршрут match хийх
 *  - Хугацаа хэмжих
 * ---------------------------------------------------------------------------*/
$router->GET('/speed/test', function () use ($router)
{
    $count = 10000;

    /* ---- URL generate хурд ---- */
    echo "<h3>Гүйцэтгэл шалгах тест (generate & match)</h3>";
    echo "<p>$count удаагийн generate болон match тест хийгдэнэ.</p>";
    $routes = [];
    $index = $count;
    $start_generate = \microtime(true);
    while ($index > 0) {
        $routes[] = $router->generate('hello', [
            'firstname' => 'Тэмүжин',
            'lastname' => 'Хан'
        ]);
        $index--;
    }
    $end_generate = \microtime(true);

    /* ---- match() шалгах хурд ---- */
    echo "<b>URL үүсгэх хугацаа:</b> " . ($end_generate - $start_generate) . " сек<br/>";
    $start_match = \microtime(true);
    while ($index < $count) {
        $router->match($routes[$index], 'GET');
        $index++;
    }
    $end_match = \microtime(true);
    echo "<b>Маршрут тааруулах хугацаа:</b> " . ($end_match - $start_match) . " сек<br/>";

})->name('speed-test');


/* -----------------------------------------------------------------------------
 *  REQUEST → MATCH → DISPATCH
 *
 *  Энэ хэсэг нь орж ирсэн HTTP request-ийг боловсруулна:
 *  1. URL-ийг цэвэрлэх (query string, trailing slash)
 *  2. Маршрут тааруулах (match)
 *  3. Callback гүйцэтгэх (dispatch)
 * ---------------------------------------------------------------------------*/

/* URL-ийг цэвэрлэх - query string болон давхардсан slash-уудыг арилгах */
$request_uri = \preg_replace('/\/+/', '\\1/', $_SERVER['REQUEST_URI']);
if (($pos = \strpos($request_uri, '?')) !== false) {
    $request_uri = \substr($request_uri, 0, $pos);
}

$uri_path = \rtrim($request_uri, '/');
$sp_lngth = \strlen(dirname($_SERVER['SCRIPT_NAME']));
$target_path = $sp_lngth > 1 ? \substr($uri_path, $sp_lngth) : $uri_path;
if (empty($target_path)) {
    $target_path = '/';
}

/* Маршрут тааруулах - орж ирсэн path болон HTTP method-д тохирох маршрутыг олох */
$callback = $router->match($target_path, $_SERVER['REQUEST_METHOD']);

if (!$callback instanceof Callback) {
    \http_response_code(404);
    die("Тохирох маршрут олдсонгүй: [" . \rawurldecode($target_path) . "]");
}

/* Callback гүйцэтгэх - Closure эсвэл Controller method дуудах */
$callable = $callback->getCallable();
$parameters = $callback->getParameters();

if ($callable instanceof \Closure) {
    \call_user_func_array($callable, $parameters);
} else {
    $class = $callable[0];
    $action = $callable[1];

    if (!\class_exists($class)) {
        die("Controller класс олдсонгүй: $class");
    }

    $controller = new $class();

    if (!\method_exists($controller, $action)) {
        die("Action олдсонгүй: $action ($class)");
    }

    \call_user_func_array([$controller, $action], $parameters);
}
