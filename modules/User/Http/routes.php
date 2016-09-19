<?php

/*
|--------------------------------------------------------------------------
| Module Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for the module.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
$api = app('Dingo\Api\Routing\Router');

$api->version('v1', function ($api) {
    $api->get('check', 'Modules\User\Http\Controllers\UserController@check');
    $api->post('/login', '\Modules\User\Http\Controllers\AuthController@postLogin');
	$api->post('/register', '\Modules\User\Http\Controllers\AuthController@postRegister');
});