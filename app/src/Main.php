<?php

namespace App;

use App\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Main
{
    public function index(Request $req, Application $app)
    {
        return $app->render('@app/index.twig');
    }

    public function login(Request $req, Application $app)
    {
        return $app->render('@app/login.twig', [
            'error' => $app['security.last_error']($req),
            'last_username' => $app['session']->get('_security.last_username'),
        ]);
    }
}
