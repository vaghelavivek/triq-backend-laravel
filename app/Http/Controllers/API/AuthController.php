<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Validator;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);
            if($validator->fails()){
                return sendError('Validation Error.', $validator->errors(),422);       
            }
            if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
                $user = Auth::user();
                $user_data=$user->only([
                                        'id',
                                        'name',
                                        'email',
                                        'role_id',
                                        'phone',
                                        'language',
                                    ]);
                $accesstoken =  $user->createToken('authToken')->accessToken;
                $data['user_data']=$user_data;
                $data['accesstoken']=$accesstoken;
                return sendResponse($data, 'User login successfully.',200);
            } 
            else{ 
                return sendError('Email or password is invalid.', ['error'=>'Unauthorised'],400);
            } 
            }catch(\Throwable $e){
                return sendError('Internal Server Error.',$e->getMessage(),500);
            }
    }
}
