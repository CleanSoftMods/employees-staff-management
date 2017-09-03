<?php namespace CleanSoft\Modules\Core\Users\Providers;

use Illuminate\Support\ServiceProvider;
use CleanSoft\Modules\Core\Users\Models\User;
use CleanSoft\Modules\Core\Users\Repositories\UserRepository;
use CleanSoft\Modules\Core\Users\Repositories\Contracts\UserRepositoryContract;

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
