<?php

namespace codesaur\Router\Example;

/* DEV: v1.2021.03.02
 * 
 * This is an example script!
 */

require_once '../vendor/autoload.php';

use Closure;

use codesaur\Router\Route;
use codesaur\Router\Router;

$router = new Router();

class ExampleController
{
    public function index()
    {
        echo 'This is an example script!';
    }

    public function greetings($firstname, $lastname = null)
    {
        $name = $firstname;
        if (!empty($lastname)) {
            $name .= " $lastname";
        }
        echo "Hello $name!";
    }
    
    public function test($firstname, $lastname, $a, $b, $number, $ah)
    {
        var_dump($firstname, $lastname, $a, $b, $number, $ah);
    }
    
    public function post_put()
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
    
    public function float(float $number)
    {
        var_dump($number);
    }
}

$router->get('/', [ExampleController::class]);

$router->get('/сайнуу/{name}', [ExampleController::class, 'greetings']);

$router->any('/hello/{string:firstname}/{lastname}', [ExampleController::class, 'greetings'])->name('hello');

$router->any('/test-all-filters/{firstname}/{lastname}/{int:a}/{uint:b}/{float:number}/{string:word}', [ExampleController::class, 'test'])->name('test-filters');

$router->map(['POST', 'PUT'], '/hello', [ExampleController::class, 'post_put']);

$router->post('/float/{float:number}', [ExampleController::class, 'float'])->name('float');

$router->get('/sum/{int:a}/{uint:b}', function ($a, $b)
{
    $sum = $a + $b;

    var_dump($a, $b, $sum);
    
    echo "$a + $b = $sum";
})->name('sum');

$router->get('/generate', function () use ($router)
{
    echo 'Hello Наранхүү => ' .  $router->generate('hello', array('firstname' => 'Наранхүү', 'lastname' => 'aka codesaur')) . '<br/>';
    echo 'Summary of 14 and -5 => ' .  $router->generate('sum', array('a' => -5, 'b' => 14)) . '<br/>';
    echo 'Float number 753.9 => ' .  $router->generate('float', array('number' => 753.9)) . '<br/>';
    echo 'Test filters => ' .  $router->generate('test-filters', array('firstname' => 'Наранхүү', 'lastname' => 'aka codesaur', 'a' => -10, 'b' => 976, 'number' => 173.5, 'word' => 'This is an example script!'));
});

$script_name = $_SERVER['SCRIPT_NAME'];
$script_name_length = strlen($script_name);

$request_uri = preg_replace('/\/+/', '\\1/', $_SERVER['REQUEST_URI']);
if (($pos = strpos($request_uri, '?')) !== false) {
    $request_uri = substr($request_uri, 0, $pos);
}
$request_uri = rtrim($request_uri, '/');

if (substr($request_uri, 0, $script_name_length) == $script_name)
{
    $request_path = substr($request_uri, $script_name_length);
} else {
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    $script_path_length = strlen($script_path);
    if (substr($request_uri, 0, $script_path_length) == $script_path) {
        $request_path = substr($request_uri, $script_path_length);
    }
}

if (!isset($request_path)) {
    $request_path = $request_uri;
}

$route = $router->match($request_path, $_SERVER['REQUEST_METHOD']);
if (!$route instanceof Route) {
    http_response_code(404);
    $pattern = rawurldecode($request_path);
    die("Unknown route pattern [$pattern]");
}

$callback = $route->getCallback();
if ($callback instanceof Closure) {
    call_user_func_array($callback, $route->getParameters());
} else {
    $controllerClass = $callback[0];
    if (!class_exists($controllerClass)) {
        die("$controllerClass is not available");
    }

    $action = $callback[1] ?? 'index';
    $controller = new $controllerClass();
    if (!method_exists($controller, $action)) {
        die("Action named $action is not part of $controllerClass");
    }

    call_user_func_array(array($controller, $action), $route->getParameters());
}
