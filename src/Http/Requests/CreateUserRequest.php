<?php namespace CleanSoft\Modules\Core\Users\Http\Requests;

use CleanSoft\Modules\Core\ACL\Repositories\Contracts\RoleRepositoryContract;
use CleanSoft\Modules\Core\ACL\Repositories\RoleRepository;
use CleanSoft\Modules\Core\Http\Requests\Request;
use CleanSoft\Modules\Core\Users\Models\User;
use CleanSoft\Modules\Core\Users\Repositories\Contracts\UserRepositoryContract;
use CleanSoft\Modules\Core\Users\Repositories\UserRepository;

class CreateUserRequest extends Request
{
    public function rules()
    {
        return [
            'username' => 'required|between:3,100|string|unique:' . webed_db_prefix() . 'users|alpha_dash',
            'email' => 'required|between:5,255|email|unique:' . webed_db_prefix() . 'users',
            'password' => 'required|max:60|min:5|string',
            'status' => 'required',
            'display_name' => 'string|between:1,150|nullable',
            'first_name' => 'string|between:1,100|required',
            'last_name' => 'string|between:1,100|nullable',
            'avatar' => 'string|between:1,250|nullable',
            'phone' => 'string|max:20|nullable',
            'mobile_phone' => 'string|max:20|nullable',
            'sex' => 'string|required|in:male,female,other',
            'birthday' => 'date_multi_format:Y-m-d H:i:s,Y-m-d|nullable',
            'description' => 'string|max:1000|nullable',
        ];
    }
}
