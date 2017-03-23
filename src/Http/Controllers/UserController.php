<?php namespace WebEd\Base\Users\Http\Controllers;

use WebEd\Base\ACL\Repositories\Contracts\RoleRepositoryContract;
use WebEd\Base\ACL\Repositories\RoleRepository;
use WebEd\Base\Http\Controllers\BaseAdminController;
use WebEd\Base\Users\Http\DataTables\UsersListDataTable;
use WebEd\Base\Users\Http\Requests\CreateUserRequest;
use WebEd\Base\Users\Http\Requests\UpdateUserPasswordRequest;
use WebEd\Base\Users\Http\Requests\UpdateUserRequest;
use WebEd\Base\Users\Repositories\Contracts\UserRepositoryContract;
use WebEd\Base\Users\Repositories\UserRepository;
use Yajra\Datatables\Engines\BaseEngine;

class UserController extends BaseAdminController
{
    protected $module = 'webed-users';

    /**
     * @var \WebEd\Base\Users\Repositories\UserRepository
     */
    protected $repository;

    /**
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepositoryContract $userRepository)
    {
        parent::__construct();

        $this->repository = $userRepository;
        $this->breadcrumbs->addLink(trans('webed-users::base.users'), route('admin::users.index.get'));

        $this->getDashboardMenu($this->module);
    }

    public function getIndex(UsersListDataTable $usersListDataTable)
    {
        $this->setPageTitle(trans('webed-users::base.users'));

        $this->dis['dataTable'] = $usersListDataTable->run();

        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_USERS, 'index.get', $usersListDataTable)->viewAdmin('index');
    }

    /**
     * Get data for DataTable
     * @param UsersListDataTable|BaseEngine $usersListDataTable
     * @return \Illuminate\Http\JsonResponse
     */
    public function postListing(UsersListDataTable $usersListDataTable)
    {
        $data = $usersListDataTable->with($this->groupAction());

        return do_filter(BASE_FILTER_CONTROLLER, $data, WEBED_USERS, 'index.post', $this);
    }

    /**
     * Handle group actions
     * @return array
     */
    protected function groupAction()
    {
        $data = [];
        if ($this->request->get('customActionType', null) == 'group_action') {
            $actionValue = $this->request->get('customActionValue', 'activated');

            if (!$this->repository->hasPermission($this->loggedInUser, ['edit-other-users'])) {
                return [
                    'customActionMessage' => trans('webed-acl::base.do_not_have_permission'),
                    'customActionStatus' => 'danger',
                ];
            }

            $ids = collect($this->request->get('id', []))->filter(function ($value, $index) {
                return (int)$value !== (int)$this->loggedInUser->id;
            })->toArray();

            switch ($actionValue) {
                case 'deleted':
                    if (!$this->repository->hasPermission($this->loggedInUser, ['delete-users'])) {
                        $data['customActionMessage'] = trans('webed-acl::base.do_not_have_permission');
                        $data['customActionStatus'] = 'danger';
                        return $data;
                    }
                    $result = $this->repository->delete($ids);
                    break;
                default:
                    $result = $this->repository->updateMultiple($ids, [
                        'status' => $actionValue,
                    ], true);
                    break;
            }

            $data['customActionMessage'] = $result['messages'];
            $data['customActionStatus'] = $result['error'] ? 'danger' : 'success';
        }
        return $data;
    }

    /**
     * Update page status
     * @param $id
     * @param $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function postUpdateStatus($id, $status)
    {
        $data = [
            'status' => $status
        ];

        if ($this->loggedInUser->id == $id) {
            $result = response_with_messages(trans('webed-users::base.cannot_update_status_yourself'), true, \Constants::ERROR_CODE);
        } else {
            $result = $this->repository->updateUser($id, $data);
        }
        return response()->json($result, $result['response_code']);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getCreate()
    {
        $this->setPageTitle(trans('webed-users::base.create_user'));
        $this->breadcrumbs->addLink(trans('webed-users::base.create_user'));

        $this->dis['isLoggedInUser'] = false;
        $this->dis['isSuperAdmin'] = $this->loggedInUser->isSuperAdmin();

        $this->dis['object'] = $this->repository->getModel();

        $this->assets
            ->addStylesheets('bootstrap-datepicker')
            ->addJavascripts('bootstrap-datepicker')
            ->addJavascriptsDirectly('admin/modules/users/user-profiles/user-profiles.js')
            ->addStylesheetsDirectly('admin/modules/users/user-profiles/user-profiles.css');

        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_USERS, 'create.get')->viewAdmin('create');
    }

    public function postCreate(CreateUserRequest $request)
    {
        $data = $request->except([
            '_token', '_continue_edit', '_tab', 'roles',
        ]);

        if ($request->exists('birthday') && !$request->get('birthday')) {
            $data['birthday'] = null;
        }

        $data['created_by'] = $this->loggedInUser->id;
        $data['updated_by'] = $this->loggedInUser->id;

        $result = $this->repository->createUser($data);

        $msgType = $result['error'] ? 'danger' : 'success';

        flash_messages()
            ->addMessages($result['messages'], $msgType)
            ->showMessagesOnSession();

        if ($result['error']) {
            return redirect()->back()->withInput();
        }

        do_action(BASE_ACTION_AFTER_CREATE, WEBED_USERS, $result);

        if ($request->has('_continue_edit')) {
            return redirect()->to(route('admin::users.edit.get', ['id' => $result['data']->id]));
        }

        return redirect()->to(route('admin::users.index.get'));
    }

    /**
     * @param RoleRepository $roleRepository
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getEdit(RoleRepositoryContract $roleRepository, $id)
    {
        $this->dis['isLoggedInUser'] = (int)$this->loggedInUser->id === (int)$id ? true : false;
        $this->dis['isSuperAdmin'] = $this->loggedInUser->isSuperAdmin();

        if ((int)$this->loggedInUser->id !== (int)$id) {
            if (!$this->repository->hasPermission($this->loggedInUser, ['edit-other-users'])) {
                abort(\Constants::FORBIDDEN_CODE);
            }
        }

        $item = $this->repository->find($id);

        if (!$item) {
            flash_messages()
                ->addMessages(trans('webed-users::base.user_not_found'), 'danger')
                ->showMessagesOnSession();

            return redirect()->back();
        }

        $this->setPageTitle(trans('webed-users::base.edit_user'), '#' . $id);
        $this->breadcrumbs->addLink(trans('webed-users::base.edit_user'));

        $this->dis['object'] = $item;

        if (!$this->dis['isLoggedInUser'] && ($this->dis['isSuperAdmin'] || $this->loggedInUser->hasPermission(['assign-roles']))) {
            $roles = $roleRepository->get();

            $checkedRoles = $this->repository->getRelatedRoleIds($item);

            $resolvedRoles = [];
            foreach ($roles as $role) {
                $resolvedRoles[] = [
                    'roles[]', $role->id, $role->name, (in_array($role->id, $checkedRoles))
                ];
            }
            $this->dis['roles'] = $resolvedRoles;
        }

        $this->assets
            ->addStylesheets('bootstrap-datepicker')
            ->addJavascripts('bootstrap-datepicker')
            ->addJavascriptsDirectly('admin/modules/users/user-profiles/user-profiles.js')
            ->addStylesheetsDirectly('admin/modules/users/user-profiles/user-profiles.css');

        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_USERS, 'edit.get', $id)->viewAdmin('edit');
    }

    public function postEdit(UpdateUserRequest $request, $id)
    {
        $user = $this->repository->find($id);

        if (!$user) {
            flash_messages()
                ->addMessages(trans('webed-users::base.user_not_found'), 'danger')
                ->showMessagesOnSession();

            return redirect()->back();
        }

        if ((int)$this->loggedInUser->id !== (int)$id) {
            if (!$this->loggedInUser->hasPermission('edit-other-users')) {
                abort(\Constants::FORBIDDEN_CODE);
            }
        }
        if ($this->request->exists('roles')) {
            if (!$this->loggedInUser->hasPermission('assign-roles')) {
                abort(\Constants::FORBIDDEN_CODE);
            }
        }

        $data = $this->request->except([
            '_token', '_continue_edit', '_tab', 'username', 'email', 'roles'
        ]);

        if ($request->requestHasRoles()) {
            $data['roles'] = $request->getResolvedRoles();
        } else {
            if ($this->request->get('_tab') === 'roles') {
                $data['roles'] = [];
            }
        }
        if ($this->request->exists('birthday') && !$this->request->get('birthday')) {
            $data['birthday'] = null;
        }

        /**
         * Prevent current users edit their roles
         */
        $isLoggedInUser = (int)$this->loggedInUser->id === (int)$id ? true : false;
        if ($isLoggedInUser) {
            if ($this->request->exists('roles')) {
                unset($data['roles']);
            }
        }

        $data['updated_by'] = $this->loggedInUser->id;

        return $this->updateUser($user, $data);
    }

    public function postUpdatePassword(UpdateUserPasswordRequest $request, $id)
    {
        $user = $this->repository->find($id);

        return $this->updateUser($user, [
            'password' => $request->get('password'),
        ]);
    }

    protected function updateUser($user, $data)
    {
        if (!$user) {
            flash_messages()
                ->addMessages(trans('webed-users::base.user_not_found'), 'danger')
                ->showMessagesOnSession();

            return redirect()->back();
        }

        $result = $this->repository->updateUser($user, $data);

        $msgType = $result['error'] ? 'danger' : 'success';

        flash_messages()
            ->addMessages($result['messages'], $msgType)
            ->showMessagesOnSession();

        if ($result['error']) {
            return redirect()->back();
        }

        do_action(BASE_ACTION_AFTER_UPDATE, WEBED_USERS, $user->id, $result);

        if ($this->request->has('_continue_edit')) {
            return redirect()->back();
        }

        return redirect()->to(route('admin::users.index.get'));
    }

    public function deleteDelete($id)
    {
        if ($this->loggedInUser->id == $id) {
            $result = response_with_messages(trans('webed-users::base.cannot_delete_yourself'), true, \Constants::ERROR_CODE);
        } else {
            $result = $this->repository->delete($id);
        }
        do_action(BASE_ACTION_AFTER_DELETE, WEBED_USERS, $id, $result);

        return response()->json($result, $result['response_code']);
    }

    public function deleteForceDelete($id)
    {
        if ($this->loggedInUser->id == $id) {
            $result = response_with_messages(trans('webed-users::base.cannot_delete_yourself'), true, \Constants::ERROR_CODE);
        } else {
            $result = $this->repository->forceDelete($id);
        }
        do_action(BASE_ACTION_AFTER_FORCE_DELETE, WEBED_USERS, $id, $result);
        return response()->json($result, $result['response_code']);
    }

    public function postRestore($id)
    {
        $result = $this->repository->restore($id);
        do_action(BASE_ACTION_AFTER_RESTORE, WEBED_USERS, $id, $result);
        return response()->json($result, $result['response_code']);
    }
}
