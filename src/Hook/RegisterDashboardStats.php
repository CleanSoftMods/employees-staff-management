<?php namespace CleanSoft\Modules\Core\Users\Hook;

use CleanSoft\Modules\Core\Users\Repositories\Contracts\UserRepositoryContract;
use CleanSoft\Modules\Core\Users\Repositories\UserRepository;

class RegisterDashboardStats
{
    /**
     * @var UserRepository
     */
    protected $repository;

    public function __construct(UserRepositoryContract $repository)
    {
        $this->repository = $repository;
    }

    public function handle()
    {
        echo view('webed-users::admin.dashboard-stats.stat-box', [
            'count' => $this->repository->count(),
        ]);
    }
}
