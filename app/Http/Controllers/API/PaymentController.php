<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\PayTm\PaytmChecksum;

class PaymentController extends Controller
{
    public function createCheckSum(){
        $paytmParams = array();
        $random = Str::random(10);
        $paytmParams["MID"] = env('PAYTM_MID');
        $paytmParams["ORDERID"] = $random;
        $body = '{"requestType":"Payment","mid":'.env('PAYTM_MID').',"websiteName":"WEBSTAGING","orderId":"'.$random.'","txnAmount":{"value":"200.00","currency":"INR"},"userInfo":{"custId":"CUST_001"},"callbackUrl":""}';
        $paytmChecksum = PaytmChecksum::generateSignature($body, 'v8Ng6yCyvmLI69o3');
        return sendResponse(['paytmChecksum' => $paytmChecksum,'order_id'=>$random], 'success.');
    }
}
