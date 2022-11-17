<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderUpdate;
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
            $orders = Order::get();
            return sendResponse(['orders' => $orders], 'Order data fetched');
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }

    public function addOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            // return $request->all();
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'service_id' => 'required',
                'final_amount' => 'required',
            ]);
            if ($validator->fails()) {
                return sendError($validator->errors(), 403);
            }
            if ($request->has('id')) {
                $order_id = (int)$request->id;
                $order = Order::find($order_id);
                if (!$order)
                    $order = new Order();
            } else {
                $order = new Order();
            }
            $order->user_id = $request->user_id ? (int)$request->user_id : Auth::id();
            $order->service_id = (int)$request->service_id;
            $order->tenure = $request->tenure;
            $order->final_amount = (float)$request->final_amount;
            $order->final_amount = (float)$request->final_amount;
            $order->payment_status = $request->payment_status;
            $order->service_status = $request->service_status;
            if ($order->save()) {
                foreach ($request->except(['id', 'user_id', 'service_id', 'tenure', 'final_amount', 'payment_status', 'service_status']) as $key => $value) {
                    if ($value && $value != 'null') {
                        $order_doc = $explode = explode('_', $key);
                        $service_documents_id = isset($explode[2]) ? $explode[2] : null;

                        $doc = OrderDocument::where('order_id',$order->id)->where('service_documents_id',$service_documents_id)->first();
                        if(!$doc){
                            $doc = new OrderDocument();
                            $doc->order_id = $order->id;    
                            $doc->service_documents_id = $service_documents_id;
                            $file_data = $this->upload('uploaded_file', $key);
                            $doc->uploaded_file  = $file_data;
                            $doc->save();
                        }else{
                            if($doc->uploaded_file){
                                $path = 'storage/'.$doc->uploaded_file;
                                if(file_exists(public_path($path))){
                                    unlink(public_path($path));
                                }
                            }
                            $file_data = $this->upload('uploaded_file', $key);
                            $doc->uploaded_file  = $file_data;
                            $doc->update();
                        }
                       
                       
                    }
                }
            }
            DB::commit();
            return sendResponse(['order' => $order], 'Order Created', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return sendError($e->getMessage(), 500);
        }
    }

    public function upload($folder = 'images', $key = 'avatar', $validation = '')
    {
        $file = null;
        if (request()->hasFile($key)) {
            $file = Storage::disk('public')->putFile($folder, request()->file($key), 'public');
        }
        return $file;
    }

    public function deleteOrder($order_id)
    {
        try {
            $delete = Order::where('id', $order_id)->delete();
            if ($delete) {
                OrderDocument::where('order_id', $order_id)->delete();
                OrderUpdate::where('order_id', $order_id)->delete();
                return sendResponse([], 'Order Deleted', 200);
            }
            return sendError('Order not delete', 500);
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }

    public function getOrderById($order_id)
    {
        try {
            $order = Order::with('order_documents', 'order_updates')->find($order_id);
            if ($order)
                return sendResponse(['order' => $order], 'Order data fetched');
            else
                return sendError('No Order data found', 200);
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }

    public function getOrderDocumentByServiceId(Request $request)
    {
        try {
            $order_id = $request->order_id;
            $order_document = OrderDocument::where('order_id',$order_id)->get();
            if ($order_document)
                return sendResponse(['order_document' => $order_document], 'Order data fetched');
            else
                return sendError('No Order data found', 200);
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }

    public function addOrderComment(Request $request)
    {
        DB::beginTransaction();
        try {
            // return $request->all();
            $validator = Validator::make($request->all(), [
                'notes' => 'required',
            ]);
            if ($validator->fails()) {
                return sendError($validator->errors(), 403);
            }
          
            $comment = new OrderUpdate();
            $comment->user_id = Auth::id();
            $comment->notes = $request->notes;
            $comment->is_send_email = (boolean)$request->is_send_email;
            $comment->is_personal_note = (boolean)$request->is_personal_note;
            $attachment = $this->upload('attachment','attachment');
            $comment->attachment  = $attachment;
            $comment->save();
            DB::commit();
            $comment->user = Auth::user();
            return sendResponse(['comment' => $comment], 'Commet Added', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return sendError($e->getMessage(), 500);
        }
    }
}
