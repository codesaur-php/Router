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
 *   - GET / POST маршрут бүртгэх
 *   - Динамик параметртэй маршрут авах
 *       {firstname}, {int:id}, {uint:b}, {float:number} гэх мэт
 *   - Нэртэй route -> URL generate хийх
 *   - Controller болон Closure callback хоёрыг хоёуланг нь дэмжих
 *   - 10,000 маршрутын generate & match хурд шалгах тест
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
        echo "<li><a href='{$base}/hello/Temujin/Khan'>/hello/{firstname}/{lastname}</a></li>";
        echo "<li><a href='{$base}/test-all-filters/word/f/f/10/20/1.5/sample'>/test-all-filters/*</a></li>";
        echo "<li><a href='{$base}/unicode/Сайн%20байна%20уу'>/unicode/{utf8:string} (UTF-8 мэдээлэл)</a></li>";
        echo "<li><a href='{$base}/numeric/7.53'>/numeric/{float}</a></li>";
        echo "<li><a href='{$base}/sum/5/7'>/sum/{int:a}/{uint:b}</a></li>";
        echo "<li><a href='{$base}/generate'>/generate (URL үүсгэх тест)</a></li>";
        echo "<li><a href='{$base}/pattern-test'>/pattern-test (Client-side pattern тест)</a></li>";
        echo "<li><a href='{$base}/speed/test'>/speed/test (Гүйцэтгэл тест)</a></li>";
        echo "<li><a href='{$base}/сайнуу/Наранхүү'>/сайнуу/Наранхүү (POST хүсэлт байх ёстой)</a></li>";
        echo "</ul>";
    }

    /**
     * Greetings
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

    /**
     * UTF-8 тэмдэгт мөрийн мэдээлэл харуулах
     *
     * GET /unicode/{utf8:string} маршрутын callback.
     * Өгөгдсөн UTF-8 тэмдэгт мөрийг задлан шинжилж,
     * тэмдэгт тус бүрийн Unicode code point, байтын урт,
     * болон нэрийг харуулна.
     *
     * @param string $string Router-аас аль хэдийн decode хийгдсэн UTF-8 тэмдэгт мөр
     * @return void
     */
    public function unicode(string $string)
    {
        echo "<h3>UTF-8 Unicode мэдээлэл</h3>";
        echo "<p><b>Оролт:</b> " . \htmlspecialchars($string, \ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p><b>Байтын урт:</b> " . \strlen($string) . " байт</p>";
        echo "<p><b>Тэмдэгтийн тоо:</b> " . \mb_strlen($string, 'UTF-8') . "</p>";
        echo "<p><b>Encoding:</b> " . (\mb_detect_encoding($string, 'UTF-8', true) ? 'UTF-8' : 'Тодорхойгүй') . "</p>";

        echo "<table border='1' cellpadding='6' cellspacing='0'>";
        echo "<tr><th>#</th><th>Тэмдэгт</th><th>Code Point</th><th>Hex</th><th>Байт</th></tr>";

        $length = \mb_strlen($string, 'UTF-8');
        for ($i = 0; $i < $length; $i++) {
            $char = \mb_substr($string, $i, 1, 'UTF-8');
            $codepoint = \mb_ord($char, 'UTF-8');
            $hex = \sprintf('U+%04X', $codepoint);
            $bytes = \strlen($char);
            $safeChar = \htmlspecialchars($char, \ENT_QUOTES, 'UTF-8');

            echo "<tr>";
            echo "<td>" . ($i + 1) . "</td>";
            echo "<td style='font-size:1.4em'>{$safeChar}</td>";
            echo "<td>{$codepoint}</td>";
            echo "<td>{$hex}</td>";
            echo "<td>{$bytes}</td>";
            echo "</tr>";
        }

        echo "</table>";
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

/* POST /сайнуу/{utf8:firstname} - Монгол үсэг дэмжих жишээ (UTF-8 параметр) */
$router->POST('/сайнуу/{utf8:firstname}', [ExampleController::class, 'greetings']);

/* GET /echo/{singleword} - Нэг параметртэй, нэртэй маршрут */
$router->GET('/echo/{singleword}', [ExampleController::class, 'echo'])
    ->name('echo');

/* GET /hello/{firstname}/{lastname} - Хоёр параметртэй, нэртэй маршрут */
$router->GET('/hello/{firstname}/{lastname}', [ExampleController::class, 'greetings'])
    ->name('hello');

/* Бүх төрлийн regex filter-тэй маршрут - int, uint, float, string */
$router->GET('/test-all-filters/{singleword}/{firstname}/{lastname}/{int:a}/{uint:b}/{float:number}/{utf8:word}',
    [ExampleController::class, 'test']
)->name('test-filters');

/* POST form test - POST method-тай маршрут */
$router->POST('/hello', [ExampleController::class, 'post']);

/* Unicode - UTF-8 тэмдэгт мөрийн мэдээлэл харуулах маршрут */
$router->GET('/unicode/{utf8:string}', [ExampleController::class, 'unicode'])
    ->name('unicode');

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

    echo 'echo -> ' . $router->generate('echo', ['singleword' => 'Амжилт']) . '<br/>';
    echo 'hello -> ' . $router->generate('hello', [
        'firstname' => 'Narankhuu',
        'lastname' => 'codesaur'
    ]) . '<br/>';
    echo 'sum -> ' . $router->generate('sum', ['a' => 7, 'b' => 13]) . '<br/>';
    echo 'unicode -> ' . $router->generate('unicode', ['string' => 'Монгол']) . '<br/>';
    echo 'float -> ' . $router->generate('float', ['number' => 753.9]) . '<br/>';
    echo 'test-filters -> ' . $router->generate('test-filters', [
        'singleword' => 'demo',
        'firstname' => 'Bold',
        'lastname' => 'Baatar',
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
 *  pattern() - Client-side substitution-д бэлэн URL pattern буцаах
 *
 *  generate()-аас ялгаатай нь параметрийн утга шаардахгүй, зөвхөн filter
 *  prefix-уудыг хасч {param} хэлбэрийн цэвэр placeholder pattern буцаана.
 *  JavaScript-н .replace() эсвэл template хэлбэрээр хэрэглэхэд тохиромжтой.
 * ---------------------------------------------------------------------------*/
$router->GET('/pattern-test', function () use ($router)
{
    echo "<h3>Client-side pattern тест (pattern)</h3>";
    echo "<p><b>pattern()</b> метод нь filter prefix-уудыг хасч цэвэр <code>{param}</code> ";
    echo "хэлбэрийн placeholder буцаадаг тул JavaScript талд URL-ийг динамикаар үүсгэхэд тохиромжтой.</p>";

    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr><th>Route name</th><th>pattern() output</th></tr>";

    $rules = ['echo', 'hello', 'sum', 'unicode', 'float', 'test-filters'];
    foreach ($rules as $rule) {
        echo '<tr>';
        echo '<td>' . \htmlspecialchars($rule) . '</td>';
        echo '<td><code>' . \htmlspecialchars($router->pattern($rule)) . '</code></td>';
        echo '</tr>';
    }
    echo "</table>";

    /* JavaScript хэрэглээний жишээ - PHP template дотор pattern()-ийг
       <?= ... ?> хэлбэрээр JS string-д оруулж, client талд .replace()-ээр
       параметр суулгах жишээ. Render хийгдсэний дараах бодит үр дүнг
       comment хэлбэрээр доор нь харуулна. */
    echo "<h4>JavaScript хэрэглээний жишээ (PHP template + JS)</h4>";
    echo "<pre style='background:#f4f4f4;padding:10px;border:1px solid #ddd'>";
    echo \htmlspecialchars(
        "<script>\n" .
        "    // PHP template дотор pattern()-ийг JS string болгон үүсгэнэ:\n" .
        "    const HELLO_URL = '<?= \$router->pattern('hello') ?>';\n" .
        "    const SUM_URL   = '<?= \$router->pattern('sum') ?>';\n\n" .
        "    // Render хийгдсэний дараа JS код дараах байдалтай болно:\n" .
        "    //   const HELLO_URL = '" . $router->pattern('hello') . "';\n" .
        "    //   const SUM_URL   = '" . $router->pattern('sum') . "';\n\n" .
        "    // JavaScript талд параметр суулгах:\n" .
        "    const url1 = HELLO_URL.replace('{firstname}', 'Temujin').replace('{lastname}', 'Khan');\n" .
        "    const url2 = SUM_URL.replace('{a}', 5).replace('{b}', 7);\n\n" .
        "    fetch(url1); // -> /hello/Temujin/Khan\n" .
        "    fetch(url2); // -> /sum/5/7\n" .
        "</script>"
    );
    echo "</pre>";

    /* Live тест - "Run test" товч дарахад pattern() гаралтыг JS-д .replace()-ээр
       параметртэй болгож, бодит router endpoint-руу fetch хийж үр дүнг харуулна.
       Ингэснээр pattern() методын client-side workflow бүхэлдээ ажиллаж байгааг
       browser дээр шууд харж болно. */
    $base = \rtrim(\dirname($_SERVER['SCRIPT_NAME']), '/');
    $helloPattern = $router->pattern('hello');
    $sumPattern   = $router->pattern('sum');

    echo "<h4>Live тест</h4>";
    echo "<p>Доорх товчийг дарж pattern() + JS .replace() + fetch() урсгалыг бодит "
       . "router endpoint дээр ажиллуулна.</p>";
    echo "<button id='run-pattern-test' style='padding:8px 16px;cursor:pointer;"
       . "background:#0066cc;color:#fff;border:0;border-radius:4px;font-size:14px'>"
       . "&#9658; Run test</button>";
    echo "<pre id='pattern-test-output' style='background:#1e1e1e;color:#dcdcdc;"
       . "padding:10px;border-radius:4px;margin-top:10px;min-height:60px;"
       . "font-family:Consolas,monospace'>Үр дүнг энд харуулна...</pre>";

    echo "<script>\n";
    echo "(function () {\n";
    echo "    const BASE      = " . \json_encode($base) . ";\n";
    echo "    const HELLO_URL = " . \json_encode($helloPattern) . ";\n";
    echo "    const SUM_URL   = " . \json_encode($sumPattern) . ";\n";
    echo "    const out = document.getElementById('pattern-test-output');\n";
    echo "    document.getElementById('run-pattern-test').addEventListener('click', async () => {\n";
    echo "        const url1 = BASE + HELLO_URL.replace('{firstname}', 'Temujin').replace('{lastname}', 'Khan');\n";
    echo "        const url2 = BASE + SUM_URL.replace('{a}', '5').replace('{b}', '7');\n";
    echo "        out.textContent = 'Generated URLs:\\n  ' + url1 + '\\n  ' + url2 + '\\n\\nFetching...';\n";
    echo "        try {\n";
    echo "            const [r1, r2] = await Promise.all([fetch(url1), fetch(url2)]);\n";
    echo "            const [t1, t2] = await Promise.all([r1.text(), r2.text()]);\n";
    echo "            out.textContent =\n";
    echo "                'Generated URLs:\\n' +\n";
    echo "                '  ' + url1 + '  -> ' + r1.status + '\\n' +\n";
    echo "                '  ' + url2 + '  -> ' + r2.status + '\\n\\n' +\n";
    echo "                'Response 1 (' + url1 + '):\\n' + t1 + '\\n\\n' +\n";
    echo "                'Response 2 (' + url2 + '):\\n' + t2;\n";
    echo "        } catch (err) {\n";
    echo "            out.textContent = 'Алдаа: ' + err.message;\n";
    echo "        }\n";
    echo "    });\n";
    echo "})();\n";
    echo "</script>";
})->name('pattern-test');

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
            'firstname' => 'Temujin',
            'lastname' => 'Khan'
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
 *  REQUEST -> MATCH -> DISPATCH
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
