<?php
namespace Modules\User\Http\Controllers;

use Validator;
use Activation;
use Sentinel;
// use Mailgun;
use Reminder;
use Centaur\AuthManager;

use Dingo\Api\Routing\Helpers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\User\Model\User;

//membuat JWT
// use JWTAuth;
// use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
	protected $authManager;

	use Helpers;

	/**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct(AuthManager $authManager)
    {
        // $this->middleware('sentinel.guest', ['except' => 'getLogout']);
        $this->authManager = $authManager;
    }


	public function postLogin(Request $request) {
		$rules = [
            'email' => 'email|max:50|exist:users,email',
            'password' => 'required',
            'username' => 'string|max:20|exist:users,username'
        ];

        $meta = [];

        $validator = \Validator::make($request->all(), $rules);

        // Assemble Login Credentials
        if ($request->input('email')!=null) {
            $login = trim(strtolower($request->get('email')));   
        }
        elseif ($request->input('username')!=null) {
            $login = trim(strtolower($request->get('username')));   
        }
        else{
             $meta['message'] = "You must type your email/username";
            $meta['code'] = 401;
            $code = 401;
            return $this->response->array(compact('meta'))->setStatusCode($code);
        }

        $credentials = [
                'login' => trim(strtolower($login)),
                'password' => $request->get('password'),
            ];
        $remember = (bool)$request->get('remember', false);

        // // Attempt the Login
        $result = $this->authManager->authenticate($credentials, $remember);
        
        
        // dd($result);
        // $meta['message'] = $result->message;
        
    	
        if ($result->isSuccessful()) {
        	$meta['message'] = 'Login Success';
            $data = \Sentinel::getUser();
            $code = $result->statusCode;
            return $this->response->array(compact('meta', 'data'))->setStatusCode($code);    
        } else {
            $meta['message'] = 'Incorect Email/Username or Password '.$result->message;
            $meta['code'] = $result->statusCode;
            $code = $result->statusCode;
        } 

        return $this->response->array(compact('meta'))->setStatusCode($code);
        

	}

	public function postRegister(Request $request) {

        // Validate the form data
  

		$rules = [
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
            'username' => 'required|string|max:20|unique:users|regex:/^[a-zA-Z][a-zA-Z0-9-_]+$/',
            'name'=> 'required|string',
            // 'referral_code'=>'string',
            // 'phone' => 'string|required|regex:/^[0-9+]+$/|min:10|max:16',
            // 'role'=>'required|string'
        ];

        

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->passes()) {

            $username = $request->input('username');
            // $referral_code = $request->input('referral_code');
            // $phone = $request->input('phone');
            $role = $request->input('role','subscriber'); 
            //explode name
            $name = $request->input('name');
            $name = explode(' ', $name);
            $first_name = $name[0];
            $last_name = '';
            /*$referral = User::where('code',$referral_code);
            if ($referral->count()>0) {
                $referral = $referral->first();
                $referral_id = $referral->id;
            }
            else
            {
                $referral_id = null;
            }*/

            if(count($name) > 1) {
                    unset($name[0]);
                    $last_name = implode(' ', $name);
                } 

            // $code = substr(strtoupper($username),0,1);
                // $code = $this->generateCode($username);
            // dd($code);
             $meta = [];

            // Assemble registration credentials
            $credentials = [
                'email' => trim(strtolower($request->get('email'))),
                'password' => $request->get('password'),
                'username' => trim(strtolower($username)),
                'first_name' => $first_name,
                'last_name' => $last_name,

            ];

           

            // // Attempt the Register
            $result = $this->authManager->register($credentials,true);
            
            if ($result->isFailure()) {
                //belum ada apa2
                $result->setMessage('Registration Failed. .');
                
            } else {
                // $activation = $result->activation->getCode();
                // $email = $result->user->email;

                $role = \Sentinel::findRoleBySlug($request->role);
                $role->users()->attach($result->user);
                /*Mailgun::send(
                    'centaur.email.welcome',
                    ['code' => $activation, 'email' => $email],
                    function ($message) use ($email) {
                        $message->to($email)
                            ->subject('Your account has been created');
                    }
                );*/
                // Ask the user to check their email for the activation link
                $result->setMessage('Registration complete. .');

                // There is no need to send the payload data to the end user
                $result->clearPayload();

            }
            $meta['message'] = $result->message;
            $meta['code'] = $result->statusCode;   
            $code = $result->statusCode; 

        }
        else{
            $meta['message'] = 'Error';
            $meta['code'] = '401'; 
            $meta['errors'] = $validator->errors();
            $code = '401';
        }

            
        return $this->response->array(compact('meta'))->setStatusCode($code);

	}

    public function getActivate(Request $request)
    {
        $code = $request->get('code');
        // Attempt the registration
        $result = $this->authManager->activate($code);

        if ($result->isFailure()) {
            $meta['message'] = $result->message;
            $meta['code'] = $result->statusCode;
        } else {
            $meta['message'] = $result->message;
            $meta['code'] = $result->statusCode;    
        }

        $code = $result->statusCode;
        
        // There is no need to send the payload data to the end user
        $result->clearPayload();

        return $this->response->array(compact('meta'))->setStatusCode($code);
    }

    public function postForget(Request $request)
    {
        // Validate the form data
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email|max:255'
        ]);

        // Fetch the user in question
        $user = Sentinel::findUserByCredentials(['email' => $request->get('email')]);

        // Only send them an email if they have a valid, inactive account
        if ($user) {
            // Generate a new code
            $reminder = Reminder::create($user);

            // Send the email
            $code = $reminder->code;
            $email = $user->email;
            Mailgun::send(
                'centaur.email.reset',
                ['code' => $code],
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Password Reset Link');
                }
            );

            $meta['message'] = 'Instructions for changing your password will be sent to your email address if it is associated with a valid account';
            $meta['code'] = 200;
            $code = 200;
        } else {
            $meta['message'] = 'User with the current mail cannot be found';
            $meta['code'] = 400;
            $code = 400;
        }

        return $this->response->array(compact('meta'))->setStatusCode($code);
    }

    protected function validatePasswordResetCode($code)
    {
        return \DB::table('reminders')
                ->where('code', $code)
                ->where('completed', false)->count() > 0;
    }

    public function getReset(Request $request, $code)
    {
        // Is this a valid code?
        if (!$this->validatePasswordResetCode($code)) {
            // This route will not be accessed via ajax;
            // no need for a json response
            $meta['message'] = 'Invalid or expired password reset code; please request a new link.';
            $meta['code'] = 400;
            $code = 400;
        } else {
            $meta['message'] = 'Password reset code valid, please fill the new password';
            $meta['code'] = 200;
            $code = 200;
        }

        return $this->response->array(compact('meta'))->setStatusCode($code);
    }

    public function postReset(Request $request, $code)
    {
        // Validate the form data
        $validator = \Validator::make($request->all(),[
            'password' => 'required|confirmed|min:6',
        ]);

        // Attempt the password reset
        $result = $this->authManager->resetPassword($code, $request->get('password'));

        if ($result->isFailure()) {
            $meta['message'] = $result->message;
            $meta['code'] = $result->statusCode;
        } else {
            $meta['message'] = $result->message;
            $meta['code'] = $result->statusCode;
        }
        $code = $result->statusCode;

        // Return the appropriate response
        return $this->response->array(compact('meta'))->setStatusCode($code);
    }

    public function postChangePassword(Request $request)
    {
        // Validate the form data
        $validator = \Validator::make($request->all(),[
            'password' => 'required|confirmed|min:6',
            'user_id' => 'required|exists:users,id'
        ]);

         $user = Sentinel::findById($request->input('user_id'));
        // dd($user);
        // Only send them an email if they have a valid, inactive account
        if ($user) {
            // Generate a new code
            $credentials = [
                'login' => $user->email,
                'password' => $request->input('old_password'),
            ];
            
            // // Attempt the Login
            $result = $this->authManager->authenticate($credentials, false);
            if ($result->isSuccessful()) {
                $reminder = Reminder::create($user);
                $code = $reminder->code;
                $result = $this->authManager->resetPassword($code, $request->input('password'));
                if ($result->isFailure()) {
                    $meta['message'] = $result->message;
                    $meta['code'] = $result->statusCode;
                } else {
                    $meta['message'] = $result->message;
                    $meta['code'] = $result->statusCode;
                }
            } else {
                $meta['message'] = 'password lama anda salah';
                $meta['code'] = $result->statusCode;
            }
            
            $code = $result->statusCode;           

        } else {
            $meta['message'] = 'User with the current mail cannot be found';
            $meta['code'] = 400;
            $code = 400;
        }

        // Attempt the password reset
        

        // Return the appropriate response
        return $this->response->array(compact('meta'))->setStatusCode($code);
    }

    public function generateCode($username)
    {   
        $meta=[];
        $code = substr(strtoupper($username),0,1);
        $generate = User::whereRaw("substring(users.code,1,1) = '$code'")
                            ->orderBy('users.code','desc')
                            ->first()
                            ;
        if ($generate == null) {
            $code = $code.str_pad(1, 4,"0", STR_PAD_LEFT);
        }
        else
        {
            $last_no = substr($generate->code, 1);
            $new_no = $last_no+1;
            $code = $code.str_pad($new_no, 4,"0", STR_PAD_LEFT);
        }

        return $code;
       // return $this->response->array(compact('meta'));
    }

    public function postResend(Request $request)
    {
        // Validate the form data
        $validator = \Validator::make($request->only('email'), [
                        'email' => 'required|email|max:255|exists:users,email'
                    ]);

       
        if ($validator->passes()) {
            // Fetch the user in question
            $user = Sentinel::findUserByCredentials(['email' => $request->get('email')]);

            // Only send them an email if they have a valid, inactive account
            if (!Activation::completed($user)) {
                // Generate a new code
                $activation = Activation::create($user);

                // Send the email
                $code = $activation->getCode();
                $email = $user->email;
                Mailgun::send(
                        'centaur.email.welcome',
                        ['code' => $code, 'email' => $email],
                        function ($message) use ($email) {
                            $message->to($email)
                                ->subject('Resend your activation account');
                        }
                    );
                 $meta['message'] = 'success';
                $meta['code'] = 200;   
                $code = 200; 
                
            }
            else{
                 $meta['message'] = 'error, your activation was completed';
                $meta['code'] = '400';   
                $code = 400; 
            }
        }
        else
        {
            $meta['status'] = false;
            $meta['message'] = "Failed";
            $meta['code'] = 500;
            $code = $meta['code'];
            $meta['error'] = $validator->errors();
        }
        $result['meta']=$meta;
        return $this->response->array($result)->setStatusCode($code);
        // return $this->response->array(compact('meta'))->setStatusCode($code);
    }
}
