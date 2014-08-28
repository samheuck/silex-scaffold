<?php
/**
 * @file
 */
require realpath(__DIR__ . '/../vendor/autoload.php');

$app = App\Application::create($debug = true);
$app['routing.options'] = ['cache_dir' => $app['paths']['cache'] . '/routes'];
$app['routing.resource'] = $app['paths']['config'] . '/routes.yml';
$app->run();
