<?php

namespace VagKaefer\LaravelSimplifiedTranslationManager\Console;

use Illuminate\Console\Command;
use VagKaefer\LaravelSimplifiedTranslationManager\Manager;

class ProcessCommand extends Command
{
  protected $signature = 'sim-trans-manager:process';

  protected $description = 'Process and generate the translations from the "en" base';

  public function handle()
  {
    // TODO - Create Facade?
    $manager = new Manager();
    $manager->process();
  }
}
