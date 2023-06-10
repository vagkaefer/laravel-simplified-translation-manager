<?php

namespace VagKaefer\LaravelSimplifiedTranslationManager\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use VagKaefer\LaravelSimplifiedTranslationManager\Manager;
use ZipArchive;

class ManagerTest extends TestCase
{

    protected $patchPackage = '/code/packages/laravel-simplified-translation-manager/tests/';
    protected $patchLaravelTest = '/code/vendor/orchestra/testbench-core/laravel/';


    protected function getPackageProviders($app)
    {
        return ['VagKaefer\LaravelSimplifiedTranslationManager\ManagerServiceProvider'];
    }

    protected function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . '/' . $object) == 'dir') {
                        $this->rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }

            reset($objects);
            rmdir($dir);
        }
    }

    public function create_lang_tests_folder()
    {

        $this->rrmdir($this->patchLaravelTest . 'lang/');

        $zip = new ZipArchive;
        if ($zip->open($this->patchPackage . 'lang.zip') === TRUE) {
            $zip->extractTo($this->patchLaravelTest);
            $zip->close();
        } else {
            dd("error to extract lan.zip tests");
        }

        chmod($this->patchLaravelTest . 'lang/', 0777);
    }


    public function setUp(): void
    {
        parent::setUp();

        $this->create_lang_tests_folder();
    }

    /** @test */
    public function multiple_tests()
    {

        // Locale English
        $this->app->setLocale('en');
        $authEnSignIn = __('auth.signin');
        $authEnNew = trans()->hasForLocale('auth.new', 'en');

        // Locale Portuguese
        $this->app->setLocale('pt_BR');
        $authPtBRSignIn = __('auth.signin');
        $authPtBRNew = trans()->hasForLocale('auth.new', 'pt_BR');

        //Checks
        $authPtBRCheck = ($authPtBRSignIn ==  'Acessar o sistema');
        $authEnCheck = ($authEnSignIn ==  'Sign-in');

        $this->assertTrue($authPtBRCheck);
        $this->assertTrue($authEnCheck);

        $this->assertTrue($authEnNew);
        $this->assertFalse($authPtBRNew);

        $this->run_process();

    }

    public function run_process()
    {

        // Run process
        $manager = new Manager();
        $check = $manager->process();
        
    }

    /** @test */
    public function test_ptbr_translated()
    {

        // Locale Portuguese
        $this->app->setLocale('pt_BR');
        $authPtBRSignIn = __('auth.signin');
        $authPtBRNew = __('auth.new');

        //Checks
        $authPtBRCheck = ($authPtBRSignIn ==  'Acessar o sistema'); //not changed - OK
        $authPtBRNew = ($authPtBRNew ==  'New translated here - NT'); //New translation with suffix - OK

        $this->assertTrue($authPtBRCheck);
        $this->assertTrue($authPtBRNew);
    }
}
