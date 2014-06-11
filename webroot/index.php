<?php
/**
 * @file
 */
require realpath(__DIR__ . '/../vendor/autoload.php');

$app = App\Application::create();

// Get routes.
$yaml = new Symfony\Component\Yaml\Parser();
$resources = $yaml->parse(file_get_contents($app['path']['base'] . '/routes.yml'));

// Controllers.
foreach ($resources as $name => $routes) {
    foreach ($routes as $route) {
        $controller = $app->match($route['uri'], $route['controller'])
            ->method($route['requestMethod']);

        // For named routes.
        if (isset($route['name'])) {
            $controller->bind($route['name']);
        }
    }
}

if (isset($testingMode)) {
    return $app;
} else {
    $app->run();
}
