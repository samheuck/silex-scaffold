Silex Scaffold
==============

A good starting point for a [Silex](http://silex.sensiolabs.org/) application.

Built with [Flint](http://flint.readthedocs.org/en/latest/) for better performance.

Requirements
------------

  - php >5.4 (5.5 recommended)
  - composer
  - phpunit
  - xdebug

Setup
-----

  1. ```composer install```
  1. make app/var/cache writable
  1. make app/var/logs writable
  1. configure database credentials (app/config/database.yml)
  1. ```app/cli schema:sync```
  1. serve index.php out of app/webroot

Usage
-----

Add routes in ```app/config/routes.yml``` - see
[Symfony Routing](http://symfony.com/doc/current/book/routing.html) for ref.

Add templates and template namespaces in ```app/config/twig.yml```.

Create *modules* in ```app/src``` that group controllers and supporting class structures by following
PSR-4 conventions. Composer will set up the autoloader for you.

Create your own service providers and inject them into the [Pimple](http://pimple.sensiolabs.org/) container.

Silex is meant to be flexible - use a structure that makes sense for your problem domain.

[Doctrine2 DBAL](http://www.doctrine-project.org/projects/dbal.html) is used for queries.
