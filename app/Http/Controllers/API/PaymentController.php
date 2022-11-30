<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\PayTm\PaytmChecksum;
use Validator;

class PaymentController extends Controller
{
    public function createCheckSum(){
        $paytmParams = array();

        /* add parameters in Array */
        $random = 'ORDERID_'.Str::random(10);
        $mid=env('PAYTM_MID');
        // $body = '{"mid":"XZnNHz73846712938565","orderId":"'.$random.'"}';
        $body = '{"requestType":"Payment","mid":"'.$mid.'","websiteName":"WEBSTAGING","orderId":"'.$random.'","txnAmount":{"value":"200.00","currency":"INR"},"userInfo":{"custId":"CUST_001","email":"khusal@gmail.com"},"callbackUrl":""}';
        $paytmChecksum = PaytmChecksum::generateSignature($body,'v8Ng6yCyvmLI69o3');
        return sendResponse(['paytmChecksum' => $paytmChecksum,'order_id'=>$random], 'success.');
    }
    public function createTransectionToken(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required',
                'redirect_url'=>'required'
            ]);
            if ($validator->fails()) {
                return sendError($validator->errors(),403);
            }
            $paytmParams = array();
            $random = 'ORDERID_'.Str::random(10);
            $paytmParams = array();
            $paytmParams["body"] = array(
                "requestType"   => "Payment",
                "mid"           => env('PAYTM_MID'),
                "websiteName"   => "WEBSTAGING",
                "orderId"       => $random,
                "callbackUrl"   => "",
                "txnAmount"     => array(
                    "value"     => $request->amount,
                    "currency"  => "INR",
                ),
                "userInfo"      => array(
                    "custId"    => "CUST_".auth()->user()->id,
                    "email"     => auth()->user()->email,
                ),
            );
            $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"]),env('PAYTM_KEY'));
    
            $paytmParams["head"] = array(
                "signature"    => $checksum
            );
    
            $post_data = json_encode($paytmParams);
            $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=".env('PAYTM_MID')."&orderId=".$random;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 
            $response = curl_exec($ch);
            $res=json_decode($response);
            if(isset($res->body) && isset($res->body->resultInfo) && isset($res->body->txnToken)){
                $final_result=$res->body->resultInfo;
                if(($final_result->resultCode == '0000' || $final_result->resultCode == '0002' ) && $final_result->resultStatus == 'S'){
                    $sendRes=[];
                    $sendRes['token']=$res->body->txnToken;
                    $sendRes['order_id']=$random;
                    $sendRes['custId']="CUST_".auth()->user()->id;
                    $sendRes['email']=auth()->user()->email;
                    return sendResponse(['transaction' => $sendRes],'success.');
                }
            }else if(isset($res->body) && isset($res->body->resultInfo)){
                if($final_result->resultCode == '1006' && $final_result->resultStatus == 'F'){
                    return sendError('Your Session has expired',200);
                }else if($final_result->resultCode == '2007' && $final_result->resultStatus == 'F'){
                        return sendError('Txn amount is invalid', 200);
                }else if($final_result->resultCode == '196' && $final_result->resultStatus == 'F'){
                        return sendError('Payment failed as amount entered exceeds the allowed limit. Please enter a lower amount and try again or reach out to the merchant for further assistance.',200);
                }
            }
        } catch (\Exception $e) {
            return sendError($e->getMessage(), 500);
        }
    }
}
