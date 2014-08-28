<?php

namespace App\Console\Command;

use App\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    protected $app;

    public function __construct(Application $app, $name = null)
    {
        $this->app = $app;
        parent::__construct($name);
    }
}
