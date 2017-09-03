<?php namespace CleanSoft\Modules\Core\Users\Repositories;

use Illuminate\Support\Collection;
use CleanSoft\Modules\Core\Repositories\Eloquent\Traits\EloquentUseSoftDeletes;
use CleanSoft\Modules\Core\Repositories\Eloquent\EloquentBaseRepository;
use CleanSoft\Modules\Core\Users\Models\Contracts\UserModelContract;
use CleanSoft\Modules\Core\Users\Models\User;
use CleanSoft\Modules\Core\Users\Repositories\Contracts\UserRepositoryContract;

class UserRepository extends EloquentBaseRepository implements UserRepositoryContract
{
    use EloquentUseSoftDeletes;

    /**
     * @param User|int $user
     * @param array $data
     */
    public function syncRoles($user, array $data)
    {
        if (!$user instanceof User) {
            $user = $this->find($user);
        }
        try {
            $user->roles()->sync($data);
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * @param User|int $user
     * @return Collection
     */
    public function getRoles($user)
    {
        if (!$user instanceof User) {
            $user = $this->find($user);
        }
        if ($user) {
            return $user->roles()->get();
        }
        return collect([]);
    }

    /**
     * @param User $user
     * @return array
     */
    public function getRelatedRoleIds($user)
    {
        if ($user) {
            return $user->roles()->allRelatedIds()->toArray();
        }
        return [];
    }

    /**
     * @param array $data
     * @param array|null $roles
     * @return int|null|\CleanSoft\Modules\Core\Models\EloquentBase
     */
    public function createUser(array $data, $roles = null)
    {
        $user = $this->create($data);
        if ($user && is_array($roles)) {
            $this->syncRoles($user, $roles);
        }
        return $user;
    }

    /**
     * @param int|User $id
     * @param array $data
     * @param array|null $roles
     * @return int|null|\CleanSoft\Modules\Core\Models\EloquentBase
     */
    public function updateUser($id, array $data, $roles = null)
    {
        $resultEditObject = $this->update($id, $data);

        if (!$resultEditObject) {
            return $resultEditObject;
        }

        if (is_array($roles)) {
            $this->syncRoles($id, $roles);
        }

        return $resultEditObject;
    }

    /**
     * @param User|int $user
     * @return bool
     */
    public function isSuperAdmin($user)
    {
        if (!$user instanceof User) {
            $user = $this->find($user);
        }

        if (!$user || !$user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * @param User|int $user
     * @param array $permissions
     * @return bool
     */
    public function hasPermission($user, array $permissions)
    {
        if (!$user instanceof User) {
            $user = $this->find($user);
        }

        if (!$user || !$user->hasPermission($permissions)) {
            return false;
        }

        return true;
    }

    /**
     * @param User|int $user
     * @param array $roles
     * @return bool
     */
    public function hasRole($user, array $roles)
    {
        if (!$user instanceof User) {
            $user = $this->find($user);
        }

        if (!$user || !$user->hasRole($roles)) {
            return false;
        }

        return true;
    }
}
