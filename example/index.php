<?php

namespace codesaur\Router\Example;

/* DEV: v1.2021.03.02
 * 
 * This is an example script!
 */

require_once '../vendor/autoload.php';

use codesaur\Router\Callback;
use codesaur\Router\Router;

class ExampleController
{
    public function index()
    {
        echo '<br/>This is an example script!';
    }

    public function greetings(string $firstname, ?string $lastname = null)
    {
        $name = $firstname;
        if (!empty($lastname)) {
            $name .= " $lastname";
        }
        echo "<br/>Hello $name!";
    }
    
    public function echo(string $singleword)
    {
        echo "<br/>Single word => $singleword";
    }
    
    public function test(string $singleword, string $firstname, string $lastname, int $a, int $b, float $number, string $word)
    {
        var_dump($singleword, $firstname, $lastname, $a, $b, $number, $word);
    }
    
    public function post()
    {
        if (empty($_POST['firstname'])) {
            die('Invalid request!');
        }
        
        $name = $_POST['firstname'];
        if (!empty($_POST['lastname'])) {
            $name .= " {$_POST['lastname']}";
        }

        $this->greetings($name);
    }
    
    public function number(float $number)
    {
        var_dump($number);
    }
}

$router = new Router();

$router->GET('/', [ExampleController::class, 'index']);

$router->POST('/сайнуу/{utf8:firstname}', [ExampleController::class, 'greetings']);

$router->GET('/echo/{singleword}', [ExampleController::class, 'echo'])->name('echo');

$router->GET('/hello/{utf8:firstname}/{utf8:lastname}', [ExampleController::class, 'greetings'])->name('hello');

$router->GET('/test-all-filters/{singleword}/{utf8:firstname}/{utf8:lastname}/{int:a}/{uint:b}/{float:number}/{utf8:word}', [ExampleController::class, 'test'])->name('test-filters');

$router->POST('/hello', [ExampleController::class, 'post']);

$router->GET('/numeric/{float:number}', [ExampleController::class, 'number'])->name('float');

$router->GET('/sum/{int:a}/{uint:b}', function (int $a, int $b)
{
    $sum = $a + $b;

    var_dump($a, $b, $sum);
    
    echo "<br/>$a + $b = $sum";
})->name('sum');

$router->GET('/generate', function () use ($router)
{
    echo 'Single word => ' . $router->generate('echo', ['singleword' => 'Congrats']) . '<br/>';
    echo 'Hello Наранхүү => ' . $router->generate('hello', ['firstname' => 'Наранхүү', 'lastname' => 'aka codesaur']) . '<br/>';
    echo 'Summary of 14 and -5 => ' . $router->generate('sum', ['a' => -5, 'b' => 14]) . '<br/>';
    echo 'Float number 753.9 => ' . $router->generate('float', ['number' => 753.9]) . '<br/>';
    echo 'Test filters => ' . $router->generate('test-filters', ['singleword' => 'example', 'firstname' => 'Наранхүү', 'lastname' => 'aka codesaur', 'a' => -10, 'b' => 976, 'number' => 173.5, 'word' => 'Энэ бол жишээ!']);
});

$request_uri = preg_replace('/\/+/', '\\1/', $_SERVER['REQUEST_URI']);
if (($pos = strpos($request_uri, '?')) !== false) {
    $request_uri = substr($request_uri, 0, $pos);
}
$uri_path = rtrim($request_uri, '/');
$sp_lngth = strlen(dirname($_SERVER['SCRIPT_NAME']));
$target_path = $sp_lngth > 1 ? substr($uri_path, $sp_lngth) : $uri_path;
if (empty($target_path)) {
    $target_path = '/';
}

$callback = $router->match($target_path, $_SERVER['REQUEST_METHOD']);
if (!$callback instanceof Callback) {
    http_response_code(404);
    die('Unknown route pattern [' . rawurldecode($target_path) . ']');
}

$callable = $callback->getCallable();
$parameters = $callback->getParameters();
if ($callable instanceof \Closure) {
    call_user_func_array($callable, $parameters);
} else {
    $controllerClass = $callable[0];
    if (!class_exists($controllerClass)) {
        die("$controllerClass is not available");
    }

    $action = $callable[1];
    $controller = new $controllerClass();
    if (!method_exists($controller, $action)) {
        die("Action named $action is not part of $controllerClass");
    }

    var_dump([$controller, $action], $parameters);
    call_user_func_array([$controller, $action], $parameters);
}
