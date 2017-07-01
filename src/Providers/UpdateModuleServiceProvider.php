<?php namespace WebEd\Base\Users\Providers;

use Illuminate\Support\ServiceProvider;

class UpdateModuleServiceProvider extends ServiceProvider
{
    protected $module = 'WebEd\Base\Users';

    protected $moduleAlias = WEBED_USERS;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        app()->booted(function () {
            $this->booted();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        register_module_update_batches($this->moduleAlias, [
            '3.1.9' => __DIR__ . '/../../update-batches/3.1.9.php',
            '3.1.10' => __DIR__ . '/../../update-batches/3.1.10.php',
        ], 'core');
    }

    protected function booted()
    {
        load_module_update_batches($this->moduleAlias, 'core');
    }
}
