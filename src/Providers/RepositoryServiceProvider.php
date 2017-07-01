<?php namespace WebEd\Base\Users\Providers;

use Illuminate\Support\ServiceProvider;
use WebEd\Base\Users\Models\User;
use WebEd\Base\Users\Repositories\UserRepository;
use WebEd\Base\Users\Repositories\Contracts\UserRepositoryContract;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserRepositoryContract::class, function () {
            return new UserRepository(new User);
        });
    }
}
