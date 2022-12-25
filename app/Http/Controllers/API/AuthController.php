<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Validator;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Firebase\Auth\Token\Exception\InvalidToken;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\ResetPasswordRequest;


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
                return sendError('Email or password is invalid.', ['error'=>'Unauthorised'],200);
            } 
            }catch(\Throwable $e){
                return sendError('Internal Server Error.',$e->getMessage(),500);
            }
    }
    public function getUserByPhone(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
            ]);
            if($validator->fails()){
                return sendError('Validation Error.', $validator->errors(),422);       
            }
            $user=User::where('phone',$request->phone)->first();
            if($user){
                return sendResponse(1, 'User found.',200);
            }
            else{ 
                return sendError('Not found.', ['error'=>'Not found'],200);
            } 
        }catch(\Throwable $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function loginFirebase(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'token' => 'required',
            ]);
            if($validator->fails()){
                return sendError('Validation Error.', $validator->errors(),422);       
            }
            $auth = app('firebase.auth');
            $idTokenString = $request->input('token');
            $verifiedIdToken = $auth->verifyIdToken($idTokenString);
            try { 
                $verifiedIdToken = $auth->verifyIdToken($idTokenString);

            } catch (\InvalidArgumentException $e) {
                return sendError('Unauthorized - Can\'t parse the token:', ['error'=>'Unauthorised'],200);
            } catch (InvalidToken $e) { 
                return sendError('Unauthorized - Token is invalide', ['error'=> $e->getMessage()],200);
            }
            $clams=$verifiedIdToken->Claims();
            $uid = $clams->get('sub');
            $user=User::where('firebase_uid',$uid)->where('phone',$request->phone)->first();
            if(!$user){
                $user=User::where('phone',$request->phone)->first();
                if($user){
                    $user->firebase_uid=$uid;
                    $user->update();
                }
            }
            if($user){
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
                    return sendResponse($data, 'Token generated.',200);
            }else{ 
                return sendError('invalid details.', ['error'=>'Unauthorised'],200);
            } 
        }catch(\Exception $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function getUserByEmail(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'phone' => 'required',
            ]);
            if($validator->fails()){
                return sendError('Validation Error.', $validator->errors(),422);       
            }
            $user=User::where('email',$request->email)->orWhere('phone',$request->phone)->first();
            if($user){
                if($user->email == $request->email && $user->phone == $request->phone){
                    return sendError('The email has already been taken <br> The phone has already been taken.', ['error'=>'user found'],200);
                }else if($user->email == $request->email){
                    return sendError('The email has already been taken.', ['error'=>'user found.'],200);
                }else if($user->phone == $request->phone){
                    return sendError('The phone has already been taken.', ['error'=>'user found.'],200);
                }
            }else{ 
                return sendResponse(1, 'User not found.',200);
            } 
        }catch(\Throwable $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function registerUser(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'token' => 'required',
                'email' => 'required|unique:users',
                'phone' => 'required|unique:users',
                'password' => 'required',
                'name' => 'required',
            ]);
            if($validator->fails()){
                return sendError('Validation Error.', $validator->errors(),422);       
            }
            $auth = app('firebase.auth');
            $idTokenString = $request->input('token');
            $verifiedIdToken = $auth->verifyIdToken($idTokenString);
            try { 
                $verifiedIdToken = $auth->verifyIdToken($idTokenString);

            } catch (\InvalidArgumentException $e) {
                return sendError('Unauthorized - Can\'t parse the token:', ['error'=>'Unauthorised'],200);
            } catch (InvalidToken $e) { 
                return sendError('Unauthorized - Token is invalide', ['error'=> $e->getMessage()],200);
            }
            $clams=$verifiedIdToken->Claims();
            $uid = $clams->get('sub');
            $user=new User();
            $user->name=$request->name;
            $user->email=$request->email;
            $user->phone=$request->phone;
            $user->visible_password=$request->password;
            $user->password=Hash::make($request->password);
            $user->role_id=3;
            $user->firebase_uid=$uid;
            $user->save();
            return sendResponse(true, 'user saved.',200);
        }catch(\Exception $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function SendEmailLink(Request $request){
        try{
            $rules = ['email' => 'required|email'];
            $validator = Validator::make($request->all() , $rules);
            if ($validator->fails())
            {
                return sendError('Validation Error.', $validator->errors(),422);
            }
            $user=User::where('email',$request->email)->first();
            if(!$user){
                return sendError('Email not Found.', ['error'=>'Email not Found'],200);
            }
            $credentials=$request->all();
            $send=Password::sendResetLink($credentials);
            if($send){
               return sendResponse(true, 'Reset password link sent on your email.',200);
            }else{
                return sendError('Something went wrong.', ['error'=>'Something went wrong'],200);
            }
        }catch(\Exception $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function reset(ResetPasswordRequest $request) {
        try{
        $reset_password_status = Password::reset($request->validated(), function ($user, $password) {
            $user->password = bcrypt($password);
            $user->visible_password = $password;
            $user->update();
        });
        if ($reset_password_status == Password::INVALID_TOKEN) {
              return sendError('Reset Token is Invalid.', ['error'=>'Reset Token is Invalid'],200);
        }
        return sendResponse(true, 'Password has been changed successfully.',200);

        }catch(\Exception $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function resetMobile(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'token' => 'required',
                'password'=>'required'
            ]);
            if($validator->fails()){
                return sendError('Validation Error.', $validator->errors(),422);       
            }
            $auth = app('firebase.auth');
            $idTokenString = $request->input('token');
            $verifiedIdToken = $auth->verifyIdToken($idTokenString);
            try { 
                $verifiedIdToken = $auth->verifyIdToken($idTokenString);

            } catch (\InvalidArgumentException $e) {
                return sendError('Unauthorized - Can\'t parse the token:', ['error'=>'Unauthorised'],200);
            } catch (InvalidToken $e) { 
                return sendError('Unauthorized - Token is invalide', ['error'=> $e->getMessage()],200);
            }
            $clams=$verifiedIdToken->Claims();
            $uid = $clams->get('sub');
            $user=User::where('firebase_uid',$uid)->where('phone',$request->phone)->first();
            if(!$user){
                $user=User::where('phone',$request->phone)->first();
            }
            if($user){
                $user->firebase_uid=$uid;
                $user->password = bcrypt($request->password);
                $user->visible_password = $request->password;
                $user->update();
                return sendResponse(true, 'Password has been changed successfully.',200);
            }

          return sendError('invalid details.', ['error'=>'Unauthorised'],200);
        }catch(\Exception $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }

}
