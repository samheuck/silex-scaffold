<?php

namespace App\Console;

use App\Application as App;
use App\Console\Command;
use Symfony\Component\Console\Application as BaseCLI;

class CLI extends BaseCLI
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct('CLI', '0.0.1');
    }

    public function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new Command\SyncDb($this->app);
        return $commands;
    }
}
