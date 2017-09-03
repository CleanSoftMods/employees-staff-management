<?php namespace CleanSoft\Modules\Core\Users\Http\DataTables;

use CleanSoft\Modules\Core\Http\DataTables\AbstractDataTables;
use CleanSoft\Modules\Core\Users\Models\User;
use CleanSoft\Modules\Core\Users\Repositories\Contracts\UserRepositoryContract;
use CleanSoft\Modules\Core\Users\Repositories\UserRepository;
use Yajra\Datatables\Engines\CollectionEngine;
use Yajra\Datatables\Engines\EloquentEngine;
use Yajra\Datatables\Engines\QueryBuilderEngine;

class UsersListDataTable extends AbstractDataTables
{
    /**
     * @var User
     */
    protected $model;

    /**
     * @var UserRepository
     */
    protected $repository;

    /**
     * @var array|\Illuminate\Http\Request|string
     */
    protected $request;

    public function __construct(UserRepositoryContract $repository)
    {
        $this->model = User::select('id', 'created_at', 'avatar', 'username', 'email', 'status', 'sex', 'deleted_at')
            ->withTrashed();

        $this->request = request();

        $this->repository = $repository;
    }

    public function headings()
    {
        return [
            'avatar' => [
                'title' => trans('webed-users::datatables.heading.avatar'),
                'width' => '1%',
            ],
            'username' => [
                'title' => trans('webed-users::datatables.heading.username'),
                'width' => '10%',
            ],
            'email' => [
                'title' => trans('webed-users::datatables.heading.email'),
                'width' => '15%',
            ],
            'status' => [
                'title' => trans('webed-users::datatables.heading.status'),
                'width' => '5%',
            ],
            'created_at' => [
                'title' => trans('webed-users::datatables.heading.created_at'),
                'width' => '10%',
            ],
            'roles' => [
                'title' => trans('webed-users::datatables.heading.roles'),
                'width' => '15%',
            ],
            'actions' => [
                'title' => trans('webed-core::datatables.heading.actions'),
                'width' => '20%',
            ],
        ];
    }

    public function columns()
    {
        return [
            ['data' => 'id', 'name' => 'id', 'searchable' => false, 'orderable' => false],
            ['data' => 'avatar', 'name' => 'avatar', 'searchable' => false, 'orderable' => false],
            ['data' => 'username', 'name' => 'username'],
            ['data' => 'email', 'name' => 'email'],
            ['data' => 'status', 'name' => 'status'],
            ['data' => 'created_at', 'name' => 'created_at', 'searchable' => false],
            ['data' => 'roles', 'name' => 'roles', 'searchable' => false, 'orderable' => false],
            ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
        ];
    }

    /**
     * @return string
     */
    public function run()
    {
        $this->setAjaxUrl(route('admin::users.index.post'), 'POST');

        $this
            ->addFilter(2, form()->text('username', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => trans('webed-core::datatables.search') . '...',
            ]))
            ->addFilter(3, form()->email('email', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => trans('webed-core::datatables.search') . '...',
            ]))
            ->addFilter(4, form()->select('status', [
                'without_trashed' => trans('webed-core::datatables.select') . '...',
                1 => trans('webed-core::base.status.activated'),
                0 => trans('webed-core::base.status.disabled'),
                'deleted' => trans('webed-core::base.status.deleted'),
            ], '', ['class' => 'form-control form-filter input-sm']));

        $this->withGroupActions([
            '' => trans('webed-core::datatables.select') . '...',
            'deleted' => trans('webed-core::datatables.delete_these_items'),
            'activated' => trans('webed-core::datatables.active_these_items'),
            'disabled' => trans('webed-core::datatables.disable_these_items'),
        ]);

        return $this->view();
    }

    /**
     * @return CollectionEngine|EloquentEngine|QueryBuilderEngine|mixed
     */
    protected function fetchDataForAjax()
    {
        return datatable()->of($this->model)
            ->rawColumns(['actions', 'avatar'])
            ->filterColumn('status', function ($query, $keyword) {
                if ($keyword === 'deleted') {
                    return $query->whereNotNull('deleted_at');
                } else if ($keyword == 'without_trashed') {
                    return $query->whereNull('deleted_at');
                }

                return $query
                    ->whereNull('deleted_at')
                    ->where('status', '=', $keyword);
            })
            ->editColumn('avatar', function ($item) {
                return '<img src="' . get_image($item->avatar) . '" width="50" height="50">';
            })
            ->editColumn('id', function ($item) {
                return form()->customCheckbox([['id[]', $item->id]]);
            })
            ->editColumn('status', function ($item) {
                if ($item->trashed()) {
                    return html()->label(trans('webed-core::base.status.deleted'), 'deleted');
                }

                $status = $item->status ? 'activated' : 'disabled';
                return html()->label(trans('webed-core::base.status.' . $status), $status);
            })
            ->addColumn('roles', function ($item) {
                $result = [];
                $roles = $this->repository->getRoles($item);
                if ($roles) {
                    foreach ($roles as $key => $row) {
                        $result[] = $row->name;
                    }
                }
                return implode(', ', $result);
            })
            ->addColumn('actions', function ($item) {
                /*Edit link*/
                $activeLink = route('admin::users.update-status.post', ['id' => $item->id, 'status' => 1]);
                $disableLink = route('admin::users.update-status.post', ['id' => $item->id, 'status' => 0]);
                $deleteLink = route('admin::users.delete.post', ['id' => $item->id]);
                $forceDelete = route('admin::users.force-delete.post', ['id' => $item->id]);
                $restoreLink = route('admin::users.restore.post', ['id' => $item->id]);

                /*Buttons*/
                $editBtn = link_to(route('admin::users.edit.get', ['id' => $item->id]), trans('webed-core::datatables.edit'), ['class' => 'btn btn-outline green btn-sm']);

                $activeBtn = ($item->status != 1 && !$item->trashed()) ? form()->button(trans('webed-core::datatables.active'), [
                    'title' => trans('webed-core::datatables.active_this_item'),
                    'data-ajax' => $activeLink,
                    'data-method' => 'POST',
                    'data-toggle' => 'confirmation',
                    'class' => 'btn btn-outline blue btn-sm ajax-link',
                ]) : '';
                $disableBtn = ($item->status != 0 && !$item->trashed()) ? form()->button(trans('webed-core::datatables.disable'), [
                    'title' => trans('webed-core::datatables.disable_this_item'),
                    'data-ajax' => $disableLink,
                    'data-method' => 'POST',
                    'data-toggle' => 'confirmation',
                    'class' => 'btn btn-outline yellow-lemon btn-sm ajax-link',
                ]) : '';

                $deleteBtn = (!$item->trashed())
                    ? form()->button(trans('webed-core::datatables.delete'), [
                        'title' => trans('webed-core::datatables.delete_this_item'),
                        'data-ajax' => $deleteLink,
                        'data-method' => 'POST',
                        'data-toggle' => 'confirmation',
                        'class' => 'btn btn-outline red-sunglo btn-sm ajax-link',
                    ])
                    : form()->button(trans('webed-core::datatables.force_delete'), [
                        'title' => trans('webed-core::datatables.force_delete_this_item'),
                        'data-ajax' => $forceDelete,
                        'data-method' => 'POST',
                        'data-toggle' => 'confirmation',
                        'class' => 'btn btn-outline red-sunglo btn-sm ajax-link',
                    ]) . form()->button(trans('webed-core::datatables.restore'), [
                        'title' => trans('webed-core::datatables.restore_this_item'),
                        'data-ajax' => $restoreLink,
                        'data-method' => 'POST',
                        'data-toggle' => 'confirmation',
                        'class' => 'btn btn-outline blue btn-sm ajax-link',
                    ]);

                $activeBtn = ($item->status != 1) ? $activeBtn : '';
                $disableBtn = ($item->status != 0) ? $disableBtn : '';

                return $editBtn . $activeBtn . $disableBtn . $deleteBtn;
            });
    }
}
