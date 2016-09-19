<?php

namespace Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cartalyst\Sentinel\Users\EloquentUser as Sentinel;

class User extends Sentinel
{
    
    protected $loginNames = ['username','email'];
    protected $fillable = [
        'email',
        'code',
        'password',
        'username',
        'last_name',
        'first_name',
        'permissions',
    ];

    use SoftDeletes;

    protected $hidden = ['password','first_name','last_name', 'permissions', 'last_login', 'created_at', 'updated_at', 'pivot','deleted_at'];
    // protected $appends = ['full_name','role'];
    
    /**
     * Return a key value array, containing any custom claims to be added to the JWT
     *
     * @return array
     */
    /*public function roles()
    {
        return $this->belongsToMany(static::$rolesModel, 'role_user', 'user_id', 'role_id')->withTimestamps();
    }*/

}
