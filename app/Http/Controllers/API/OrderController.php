<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderTransaction;
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
use App\PayTm\PaytmChecksum;

class OrderController extends Controller
{
    public function getOrders()
    {
        try {
            if(Auth::user()->role_id ==3)
                $orders = Order::with('user','service')->where('user_id',Auth::id())->get();
            else
                $orders = Order::with('user', 'service')->get();
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
            $order_documents = [];
            if($order->order_documents){
                foreach ($order->order_documents as $key=> $order_doc){
                    $service_doc = ServiceDocument::find($order_doc->service_documents_id);
                    $order_documents[$key]['order_doc'] = $order_doc;
                    $order_documents[$key]['order_doc']['service_doc'] = $service_doc;
                }
            }
            $order->documents_data = $order_documents;
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
            $comment->parent_id = Auth::id();
            $comment->order_id = $request->order_id;
            $comment->notes = $request->notes;
            $comment->is_send_email = (boolean)$request->is_send_email;
            $comment->is_personal_note = (boolean)$request->is_personal_note;
            $comment->add_to_profile = (boolean)$request->add_to_profile;
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

    public function _saveOrdertransction(Request $request){
        $tran= new OrderTransaction();
        $tran->order_id = $request->order_id;
        $tran->save();
    }
    public function addUserOrder(Request $request){
            DB::beginTransaction();
            try {
                $validator = Validator::make($request->all(), [
                    'service_id' => 'required',
                    'tenure' => 'required',
                    'final_amount' => 'required',
                    'paytm_order_id' => 'required',
                ]);
                if ($validator->fails()) {
                    return sendError($validator->errors(), 403);
                }
                    $paytmParams = array();
                    $paytmParams["body"] = array(
                        "mid" => env('PAYTM_MID'),
                        "orderId" => $request->paytm_order_id,
                    );
                    $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES),env('PAYTM_KEY'));
                    $paytmParams["head"] = array(
                        "signature"	=> $checksum
                    );
                    $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
                    $url = "https://securegw-stage.paytm.in/v3/order/status";
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));  
                    $response = curl_exec($ch);
                    $res=json_decode($response);
                    $isTraValid=false;
                    if(isset($res->body) && isset($res->body->resultInfo) && isset($res->body->resultInfo->resultStatus)){
                        if($res->body->resultInfo->resultStatus == 'TXN_SUCCESS'){
                                $isTraValid=true;
                        }
                    }
                    if(!$isTraValid){
                        return sendError('Transaction is invalid',200);   
                    }
                    $order = new Order();
                    $order->user_id = Auth::id();
                    $order->service_id = (int)$request->service_id;
                    $order->tenure = $request->tenure;
                    $order->final_amount = (float)$request->final_amount;
                    $order->payment_status = $request->payment_status;
                    $order->service_status = $request->service_status;
                    if($order->save()){
                        $tran= new OrderTransaction();
                        $tran->order_id = $order->id;
                        $tran->transaction_id = $request->transaction_id;
                        $tran->paytm_order_id = $request->paytm_order_id;
                        $tran->bank_transaction_id = $request->bank_transaction_id;
                        $tran->amount = $request->payment_amount;
                        $tran->currency = $request->currency;
                        $tran->payment_type = $request->payment_type;
                        $tran->status = 'SALE';
                        $tran->payment_type = 'PAYTM';
                        $tran_details=[];
                        $tran_details['bank_name']=$request->bank_name;
                        $tran_details['gateway_name']=$request->gateway_name;
                        $tran_details['payment_mode']=$request->payment_mode;
                        $tran->transaction_details=json_encode($tran_details);
                        $tran->save();
                    }
                    DB::commit();
                return sendResponse(['order' =>$order], 'Order Placed', 200);
            } catch (\Exception $e) {
                DB::rollback();
                return sendError($e->getMessage(), 500);
            }
    }

    public function updateOrder(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);
            if ($validator->fails()) {
                return sendError($validator->errors(), 403);
            }
            $order_id = (int)$request->id;
            $order = Order::find($order_id);
            if ($order){
                foreach ($request->except(['id']) as $key => $value) {
                    if ($value && $value != 'null') {
                        $order_doc = $explode = explode('_', $key);
                        $service_documents_id = isset($explode[2]) ? $explode[2] : null;

                        $doc = OrderDocument::where('order_id', $order->id)->where('service_documents_id', $service_documents_id)->first();
                        if (!$doc) {
                            $doc = new OrderDocument();
                            $doc->order_id = $order->id;
                            $doc->service_documents_id = $service_documents_id;
                            $file_data = $this->upload('uploaded_file', $key);
                            $doc->uploaded_file  = $file_data;
                            $doc->save();
                        } else {
                            if ($doc->uploaded_file) {
                                $path = 'storage/' . $doc->uploaded_file;
                                if (file_exists(public_path($path))) {
                                    unlink(public_path($path));
                                }
                            }
                            $file_data = $this->upload('uploaded_file', $key);
                            $doc->uploaded_file  = $file_data;
                            $doc->update();
                        }
                    }
                }
                DB::commit();
                return sendResponse(['order' => $order], 'Order Updated', 200);
            }else{
                return sendError('Order not found',[], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return sendError($e->getMessage(), 500);
        }
    }

    public function getProfileAttachment()
    {
        try {
            $attachments = OrderUpdate::where('parent_id', Auth::id())->where('add_to_profile',1)->get();
            return sendResponse(['attachments' => $attachments], 'Attachments data fetched');
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }
}
