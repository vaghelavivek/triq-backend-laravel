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
}
