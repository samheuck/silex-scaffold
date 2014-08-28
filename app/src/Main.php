<?php

namespace App;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Main
{
    public function index(Request $request, Application $app)
    {
        return $app->render('@app/index.twig');
    }
}
