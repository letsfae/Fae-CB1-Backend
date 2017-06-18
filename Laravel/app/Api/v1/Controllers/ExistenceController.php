<?php

namespace App\Api\v1\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Routing\Controller;
use Dingo\Api\Routing\Helpers;
use Auth;
use App\Users;
use Validator;

class ExistenceController extends Controller {
    use Helpers;
    
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function email($email) {
        $email = strtolower($email);
        $input = array('email' => $email);
        $validator = Validator::make($input, [
        'email' => 'required|max:50|email',
        ]);
        if($validator->fails())
        {            
            throw new AccessDeniedHttpException('Bad request, Please verify your email format!');
        }
        $user = Users::where('email', '=', $email)->first();
        $existence = True;
        if ($user == null) {
            $existence = False;
        }
        $result = array('existence' => $existence);
        return $this->response->array($result);
    }

    public function userName($user_name) {
        $input = array('user_name' => $user_name);
        $validator = Validator::make($input, [
            'user_name' => 'required|regex:/^[a-zA-Z0-9]*[_\-.]?[a-zA-Z0-9]*$/|min:3|max:20',
        ]);
        if($validator->fails())
        {            
            throw new AccessDeniedHttpException('Bad request, Please verify your user_name format!');
        }
        $result = array('existence' => Users::whereRaw('LOWER(user_name) = ?', [strtolower($this->request->user_name)])->exists());
        return $this->response->array($result);
    }
}