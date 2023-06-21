<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Access-Control-Allow-Origin: *');

require 'DB.php';


        $amount =	isset($_REQUEST["amount"]) ? $_REQUEST["amount"] : "0" ;
        $mpesa_no =	isset($_REQUEST["mpesa_no"]) ? $_REQUEST["mpesa_no"] : "0" ;
        $biller_code =	isset($_REQUEST["biller_code"]) ? $_REQUEST["biller_code"] : "0" ;
        $fbcode =	isset($_REQUEST["fbcode"]) ? $_REQUEST["fbcode"] : "0" ;
        $account =	isset($_REQUEST["account"]) ? $_REQUEST["account"] : "0" ;
		$user_id =	isset($_REQUEST["user_id"]) ? $_REQUEST["user_id"] : "0" ;
		
		if (substr($mpesa_no, 0, 1) == "0") {
            	$mpesa_no = "254" . substr($mpesa_no, -9);
        	}

            if(!checkBillerStatus($biller_code)){
                die;
            }

		$TransactionDesc = $biller_code;
		$url1 = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url1);
		$credentials = base64_encode('OEoNPRBeNnb0Mtqti9nza7fCqnPYq3nV:Omu1Sik9o6mQasAh');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials)); //setting a custom header
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$curl_response = curl_exec($curl);
		//echo $curl_response;
		$curl_response = explode('"access_token": ',$curl_response);
		$curl_response1 = explode('"expires_in":',$curl_response[1]);
		$trimmed  = rtrim($curl_response1[0],',');
		$trimmed1 = trim($trimmed, '"');
		$curl_response2 = explode('"',$trimmed1);
		$curl_response3 = explode('"',$curl_response2[0]);
		$token = $curl_response3[0];

		$MERCHANT_ID =    '730018';
		$PASSKEY = 'f0596972f0c26e731dac99cb07ef9c4f25f46368eb1b5de2f25ed5fe5bef09f1';
		$TIMESTAMP = date("YmdHis",time());
		 //openssl_public_encrypt($plaintext, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
		 $password = base64_encode($MERCHANT_ID . $PASSKEY . $TIMESTAMP);

		 
		$url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$token.'')); //setting custom header


		$curl_post_data = array(
		  //Fill in the request parameters with valid values
		  'BusinessShortCode' => $MERCHANT_ID,
		  'Password' => $password,
		  'Timestamp' => $TIMESTAMP,
		  'TransactionType' => 'CustomerBuyGoodsOnline',
		  'Amount' => $amount,
		  'PartyA' => $mpesa_no,
		  'PartyB' => '732631',
		  'PhoneNumber' => $mpesa_no,
		  'CallBackURL' => 'https://carwash.romeofoxalpha.co.ke/IpayStkPushCallback.php',
		  'AccountReference' => $TransactionDesc,
		  'TransactionDesc' => $TransactionDesc
		);

		$data_string = json_encode($curl_post_data);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
 
 	$response = curl_exec($curl);
$title = "Enter your M-Pesa PIN";
$body = "Hi, Enter your M-Pesa PIN to complete the transaction Thanks";
 sendPushNotification($fbcode,$title,$body);
 $response_array = json_decode( $response  );
 
 $db=DB::get();
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$db->beginTransaction();
 
if(isset($response_array->CheckoutRequestID) && $response_array->ResponseCode == 0){
    $status='0';
   $insert="INSERT INTO `ipay_stkpush` ( `user_id`,`msisdn`, `amount`, `biller_code`,  `fb_code`, `status`, `stk_code`,`account`)
VALUES (?,?,?,?,?,?,?,?)";
$insert_query=$db->prepare($insert);
	$insert_query->bindParam(1, $user_id);
	$insert_query->bindParam(2, $mpesa_no);
	$insert_query->bindParam(3, $amount);
	$insert_query->bindParam(4, $biller_code);
	$insert_query->bindParam(5, $fbcode);
	$insert_query->bindParam(6, $status);
	$insert_query->bindParam(7, $response_array->CheckoutRequestID);
	$insert_query->bindParam(8, $account);
	$insert_query->execute();
	$trans_id = $db->lastInsertId();
			echo json_encode(array("code" => "1" , "message" => "Request accepted for processing, check your phone to enter M-PESA pin"));	

		}
		else{
            $status='9';
   $insert="INSERT INTO `ipay_stkpush` ( `user_id`,`msisdn`, `amount`, `biller_code`, `fb_code`, `status`, `stk_code`,`account`)
VALUES (?,?,?,?,?,?,?,?)";
	$insert_query=$db->prepare($insert);
	$insert_query->bindParam(1, $user_id);
	$insert_query->bindParam(2, $mpesa_no);
	$insert_query->bindParam(3, $amount);
	$insert_query->bindParam(4, $biller_code);
	$insert_query->bindParam(5, $fbcode);
	$insert_query->bindParam(6, $status);
	$insert_query->bindParam(7, $response_array->CheckoutRequestID);
	$insert_query->bindParam(8, $account);
	$insert_query->execute();
	$trans_id = $db->lastInsertId();
			echo json_encode(array("code" => "0", "message" => "Payment request failed, please try again"));	
		}
$db->commit();		
		
		
		
function sendPushNotification($fbcode,$title,$body){

    $fcm_key='AAAAWPZyQpc:APA91bFaQ-4PckUnSP6stE5BFZ0dnXVgAwLg9yNRzTlwFzkA9zfiXIjzJ7zKrBD1dzZMRPYFcEZyi-93n8u-aUo7uo0Rvu9kuMxKmUUF88ovejnXHw7D38yl6rOp1CAvWyrbRrcJKfJq';
    $url = 'https://fcm.googleapis.com/fcm/send';
    $message = [
        "inbox"=>array(
            'id'             => 890,
        'title' =>     $title,
        'message'             => $body,
        'date' =>  date('D, d M Y H:i:s')
        ),
        'action'             => 'inbox',
    ];
    $fields = array (
        "to"=> $fbcode,
        'data'              => $message,
        'priority'          => 'high',            
    );
    $fields = json_encode ( $fields );
    $headers = array (
        'Authorization: key=' . $fcm_key,
        'Content-Type: application/json'
    );

    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_POST, true );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields );
    $result = curl_exec($ch );
 //   echo "ewewew";
   
    curl_close ( $ch );
}

function checkBillerStatus($biller_code) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://apis.ipayafrica.com/ipay-billing/billing/biller/status?vid=mrent&biller_code='.$biller_code);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
  $result= json_decode($data,true);
  if($result['header_status']==200){
    if($result['data']['status']=='ONLINE'){
        return true;
    }else{
        $response=[
            'code'=>'0',
            'message'=>'Please try later'
        ];
        $res = json_encode($response);
        echo "$res";
        return false;
    }
  }else{
$response=[
        'code'=>'0',
        'message'=>'Please try later'
    ];
    $res = json_encode($response);
    echo "$res";
    return false;
  }
}
?>
