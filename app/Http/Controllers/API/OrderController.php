<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServiceDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Storage;
use Image;
use DB;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Auth;


class OrderController extends Controller
{
    public function getOrders()
    {
        try {
            $orders = Order::where('user_id',Auth::id())->get();
            return sendResponse(['orders' => $orders],'Service data fetched');
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }

    public function addService(Request $request){
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'price' => 'required',
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
            $service->price=$request->price;
            $service->tenure=$request->tenure;
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
}
