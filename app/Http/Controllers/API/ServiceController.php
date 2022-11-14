<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Storage;
use Image;
use DB;
use Illuminate\Http\File;


class ServiceController extends Controller
{
    public function getServices()
    {
        try {
            $services = Service::get();
            return sendResponse(['services' => $services],'Service data fetched');
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }

    public function addService(Request $request){
        DB::beginTransaction();
        try{
            // return $request;
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'price' => 'required',
            ]);
            if ($validator->fails()) {
                return sendError($validator->errors(),403);
            }
            $service= new Service();
            $service->title=$request->title;
            $service->description=$request->description;
            $service->country=$request->country;
            $service->price=$request->price;
            $service->tenure=$request->tenure;
            if($request->hasfile('service_image')){
                $logofile=$this->upload('images/service_image', 'service_image');
                $service->service_image  = $logofile;
            }
            if($service->save()){
                $document_names = json_decode($request->document_names);
                foreach($document_names as $doc_data){
                    $doc = new ServiceDocument();
                    $doc->service_id = $service->id;
                    $doc->name = $doc_data->name;
                    $doc->save();
                }
            }
            DB::commit();
            return sendResponse(['user' => $service],'Service Created', 200);
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
            $delete = Service::where('id',$service_id)->delete();
            if($delete){
                return sendResponse([],'Service Deleted', 200);
            }
            return sendError('Service not delete',500);
        }catch (\Exception $e) {
             return sendError($e->getMessage(),500);
        }
    }
}
