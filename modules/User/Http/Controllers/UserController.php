<?php

namespace Modules\User\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;

class UserController extends Controller
{
	use Helpers;
    //

    public function check()
    {

    	$meta['message']='Berhasil diakses';
    	$meta['code'] = 200;
    	$meta['status'] = true;

    	$user = [
    				'id'=>1,
    				'nama' => 'Berry Firmann'
    			];
    	$data = $user;
    	// $code = 200;

    	return $this->response->array(compact('meta','data'));
    }
}
