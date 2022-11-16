<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserBusiness;
use Validator;
use Auth;
use Illuminate\Support\Facades\Hash;
use DB;

class UserController extends Controller
{
    public function addUser(Request $request){
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|unique:users',
                'phone' => 'required|unique:users',
                'language' => 'required',
                'visible_password' => 'required',
                'role_id' => 'required',
                'country' => 'required',
            ]);
            if($validator->fails()){
                return sendError('validation-error', $validator->errors(),422);       
            }
            $user=new User();
            $user->name=$request->name;
            $user->email=$request->email;
            $user->phone=$request->phone;
            $user->language=$request->language;
            $user->visible_password=$request->visible_password;
            $user->password=Hash::make($request->visible_password);
            $user->role_id=$request->role_id;
            if($user->save()){
                $userb=new UserBusiness();
                $userb->user_id=$user->id;
                $userb->business_name=$request->business_name;
                $userb->business_type=$request->business_type;
                $userb->bank_account=$request->bank_account;
                $userb->address=$request->address;
                $userb->city=$request->city;
                $userb->state =$request->state;
                $userb->zip=$request->zip;
                $userb->country=$request->country;
                $userb->save();
            }
            DB::commit();
        return sendResponse([], 'User Added.',200);
        }catch(\Throwable $e){
            DB::rollback();
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function getUserById($id){
        try{
            $user=User::select('users.*','user_businesses.user_id','user_businesses.business_name','user_businesses.business_type','user_businesses.bank_account','user_businesses.address','user_businesses.city','user_businesses.state','user_businesses.zip','user_businesses.country')
                ->leftJoin('user_businesses', function($join) {
                $join->on('users.id', '=', 'user_businesses.user_id');
                })
                ->where('users.id','<>',Auth::id())
                ->where('users.id','=',$id)
                ->first();
            if($user){
                    return sendResponse(['user'=>$user], 'User Found.',200);
            }else{
                return sendError('User not found','',200);
            }
        }catch(\Throwable $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function getAllUsers(){
        try{
            $users=User::select('users.*','user_businesses.user_id','user_businesses.business_name','user_businesses.business_type','user_businesses.bank_account','user_businesses.address','user_businesses.city','user_businesses.state','user_businesses.zip','user_businesses.country')
                ->leftJoin('user_businesses', function($join) {
                $join->on('users.id', '=', 'user_businesses.user_id');
                })
                ->where('users.id','<>',Auth::id())
                ->get();
            return sendResponse(['users'=>$users], 'User Found.',200);
        }catch(\Throwable $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function updateUser(Request $request){
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'email' => 'required|unique:users,email,'.$request->id,
                'phone' => 'required|unique:users,phone,'.$request->id,
                'language' => 'required',
                'visible_password' => 'required',
                'role_id' => 'required',
                'country' => 'required',
            ]);
            if($validator->fails()){
                return sendError('validation-error', $validator->errors(),422);       
            }
            $user=User::find($request->id);
            if(!$user){
                return sendError('User not found','',200);
            }
            $user->name=$request->name;
            $user->email=$request->email;
            $user->phone=$request->phone;
            $user->language=$request->language;
            $user->visible_password=$request->visible_password;
            $user->password=Hash::make($request->visible_password);
            $user->role_id=$request->role_id;
            if($user->save()){
                $userb=UserBusiness::where('user_id',$user->id)->first();
                if(!$userb){
                    $userb=new UserBusiness();
                    $userb->user_id=$user->id;
                }
                $userb->business_name=$request->business_name;
                $userb->business_type=$request->business_type;
                $userb->bank_account=$request->bank_account;
                $userb->address=$request->address;
                $userb->city=$request->city;
                $userb->state =$request->state;
                $userb->zip=$request->zip;
                $userb->country=$request->country;
                $userb->save();
            }
            DB::commit();
        return sendResponse([], 'User Updated.',200);
        }catch(\Throwable $e){
            DB::rollback();
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
    public function deleteUser(Request $request){
        DB::beginTransaction();
        try{
            $user=User::where('id',$request->id)->where('id','<>',Auth::id())->delete();
            DB::commit();
            return sendResponse([], 'User deleted.',200);        
        }catch(\Throwable $e){
            DB::rollback();
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }

    public function getUsersNamesList(){
        try{
            $users=User::select('id','name')->get();
            return sendResponse(['users'=>$users], 'User Found.',200);
        }catch(\Throwable $e){
            return sendError('Internal Server Error.',$e->getMessage(),500);
        }
    }
}
