<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDocument;
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

    public function addOrder(Request $request){
        DB::beginTransaction();
        try{
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'service_id' => 'required',
                'amount' => 'required',
            ]);
            if ($validator->fails()) {
                return sendError($validator->errors(),403);
            }
            if($request->has('id')){
                $order_id = (int)$request->id;
                $order= Order::find($order_id);
                if(!$order)
                $order= new Order();
            }else{
                $order= new Order();
            }
            $order->user_id= $request->user_id ? (int)$request->user_id : Auth::id();
            $order->service_id= (int)$request->service_id;
            $order->amount= (float)$request->amount;
            $order->tax= (float)$request->tax;
            $order->final_amount= (float)$request->final_amount;
            $order->payment_status= (float)$request->payment_status;
            $order->service_status= (float)$request->service_status;
            if($order->save()){
                $order_documents = json_decode($request->order_documents);
                return $order_documents;
                foreach($order_documents as $doc_data){
                    if($doc_data->id){
                        $doc = OrderDocument::find($doc_data->id);
                        if(!$doc)
                        $doc = new OrderDocument();
                    }else{
                        $doc = new OrderDocument();
                    }
                    $doc->order_id = $doc_data->order_id;
                    $doc->service_documents_id = $doc_data->service_documents_id;
                    if($request->hasfile('uploaded_file')){
                        if($doc->uploaded_file){
                            $path = 'storage/'.$doc->uploaded_file;
                            if(file_exists(public_path($path))){
                                unlink(public_path($path));
                            }
                        }
                        $file_data=$this->upload('images/uploaded_file', 'uploaded_file');
                        $doc->uploaded_file  = $file_data;
                    }
                    $doc->save();
                }
            }
            DB::commit();
            return sendResponse(['order' => $order],'Service Created', 200);
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
