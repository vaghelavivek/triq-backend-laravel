<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceDocument;
use App\Models\User;
use App\Models\UserBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Storage;
use Image;
use DB;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class ServiceController extends Controller
{
    public function getServices()
    {
        try {
            // $services = Service::where('user_id',Auth::id())->get();
            $services = Service::all();
            return sendResponse(['services' => $services],'Service data fetched');
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }

    public function addService(Request $request){
        DB::beginTransaction();
        try{
            // return json_decode($request->prices);
            $validator = Validator::make($request->all(), [
                'title' => 'required',
            ]);
            if ($validator->fails()) {
                return sendError($validator->errors(),403);
            }
            if($request->has('id')){
                $service_id = (int)$request->id;
                $service= Service::find($service_id);
                if(!$service)
                $service= new Service();
            }else{
                $service= new Service();
            }
            $service->user_id=Auth::id();
            $service->title=$request->title;
            $service->description=$request->description;
            $service->country=$request->country;
            $service->prices= $request->prices;
            if($request->hasfile('service_image')){
                if($service->service_image){
                    $path = 'storage/'.$service->service_image;
                    if(file_exists(public_path($path))){
                        unlink(public_path($path));
                    }
                }
                $logofile=$this->upload('images/service_image', 'service_image');
                $service->service_image  = $logofile;
            }
            if($service->save()){
                $document_names = json_decode($request->document_names);
                $document_ids=Arr::pluck($document_names, 'id');
                if($document_ids && count($document_ids)){
                        ServiceDocument::where('service_id',$service->id)->whereNotIn('id',$document_ids)->delete();
                }
                foreach($document_names as $doc_data){
                    if($doc_data->id){
                        $doc = ServiceDocument::find($doc_data->id);
                        if(!$doc)
                        $doc = new ServiceDocument();
                    }else{
                        $doc = new ServiceDocument();
                    }
                    $doc->service_id = $service->id;
                    $doc->name = $doc_data->name;
                    $doc->save();
                }
            }
            DB::commit();
            return sendResponse(['service' => $service],'Service Created', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return sendError($e->getMessage(),500);
        }
    }

    public function upload($folder = 'images', $key = 'avatar', $validation = 'image|mimes:jpeg,png,jpg,gif,svg|sometimes')
    {
        $uploaded_thumbnail_image = null;
        if (request()->hasFile($key)) {
            $file = request()->file($key);
            $image = Image::make($file->getRealPath());
            $image->resize(1000,1000, function ($constraint) {
                $constraint->aspectRatio();
            });
            $thumbnail_image_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.'.$file->getClientOriginalExtension();
            $image->save(public_path().'/images/'.$thumbnail_image_name);
            $saved_image_uri = $image->dirname.'/'.$image->basename;
            $uploaded_thumbnail_image=Storage::disk('public')->putFile($folder,new File($saved_image_uri), 'public');
            $image->destroy();
            unlink($saved_image_uri);
        }
        return $uploaded_thumbnail_image;
    }

    public function deleteService($service_id){
        try{
            $delete = Service::where('id',$service_id)->where('user_id',Auth::id())->delete();
            if($delete){
                return sendResponse([],'Service Deleted', 200);
            }
            return sendError('Service not delete',500);
        }catch (\Exception $e) {
             return sendError($e->getMessage(),500);
        }
    }

    public function getServiceById($service_id)
    {
        try {
            $service = Service::with('service_document')->find($service_id);
            if($service)
            return sendResponse(['service' => $service],'Service data fetched');
            else
            return sendError('No Service data found', 200);
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }
    public function getServiceByUserId($user_id)
    {
        try {
            $user_business= UserBusiness::where('user_id',$user_id)->first();
            if($user_business){
                $services = Service::where('country',$user_business->country)->get();
                // $services = Service::get();
                if($services)
                return sendResponse(['services' => $services],'Service data fetched');
                else
                return sendError('No Service data found', 200);
            }
            return sendError('User Not has country Data', 200);
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }
}
