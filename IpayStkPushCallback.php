<?php
require 'DB.php';
    $db=DB::get();
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$db->beginTransaction();
	
//if (!$data) {
  if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: text/plain'); // merged from WP #9093
      die('XML-RPC server accepts POST requests only.');
  }
  global $HTTP_RAW_POST_DATA;
  if (empty($HTTP_RAW_POST_DATA)) {
      // workaround for a bug in PHP 5.2.2 - http://bugs.php.net/bug.php?id=41293
      $data = file_get_contents('php://input');
  } else {
      $data =& $HTTP_RAW_POST_DATA;
  }
//}
      $request = json_decode($data);
      $callbackData= $request;
      $CheckoutRequestID = $request->{'Body'}->{'stkCallback'}->{'CheckoutRequestID'};
      $ResultDesc = $request->{'Body'}->{'stkCallback'}->{'ResultDesc'};
      $ResultCode = $request->{'Body'}->{'stkCallback'}->{'ResultCode'};
      $curtime  = date('Y-m-d H:i:s');
 
   $sqla1="SELECT * FROM  ipay_stkpush where  stk_code= '$CheckoutRequestID' ORDER BY id DESC LIMIT 2";
   	$query=$db->prepare($sqla1);   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($row = $query->fetch()){
	        $id = $row['id'];
           $fbcode = $row['fb_code'];
           $user_id = $row['user_id'];
           $phone = $row['msisdn'];
           $biller_code = $row['biller_code'];
           $account = $row['account'];
           if($biller_code=="safaricom" || $biller_code=="airtel" || $biller_code=="telkom" || $biller_code=="jtl"){
           if (substr($account, 0, 1) == "0") {
            $account = "254" . substr($account, -9);
        }
    }
		}
}
	}
		
             $ResponseCode=  $ResultCode;
            
             if( $ResponseCode == "1032")
             {
             $sendsms = 'oh.. :( You canceled the payment. Click this link to pay anytime https://chezakamawewe.com?r='.$cart_id;
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             }elseif( $ResponseCode == "1031")
             {
             $sendsms = 'oh.. :( M-Pesa May have timeout. Click this link to pay anytime https://chezakamawewe.com?r='.$cart_id;
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             }   elseif( $ResponseCode == "1001")
             {
             $sendsms = 'network issues.. Click this link to pay anytime https://chezakamawewe.com?r='.$cart_id;
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             }
             elseif( $ResponseCode == "2001")
             { 
             $sendsms = 'Please check your M-pesa PIN Click this link to pay anytime https://chezakamawewe.com?r='.$cart_id;
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             }
             elseif( $ResponseCode == "1037")
             { 
             $sendsms = 'Ouch! Something went wrong Click this link to pay anytime https://chezakamawewe.com?r='.$cart_id;
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             }
             elseif( $ResponseCode == "1")
             { 
             $sendsms = 'Ouch! You may have insufficient M-Pesa for this transaction Deposit to M-Pesa and Click this link to pay anytime https://chezakamawewe.com?r='.$cart_id;
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             }
             elseif( $ResponseCode == "2")
             { 
             $sendsms = 'Hehe.. ;) that amount is too low minimum transaction amount is Ksh 1';
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             } 
             elseif( $ResponseCode == "3")
             {
             
             $sendsms = 'Woo.. :S The amount you wanted to send is greater than the maximum M-Pesa transaction amount try smaller amount less than 35K';
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             } 
             elseif( $ResponseCode == "4")
             {
             $sendsms = 'Woo.. :S You have Exceed the daily transfer limit try other payment method ';
			 $title = "Transaction Failed ";
			 sendPushNotification($fbcode,$title,$sendsms);
			 die();
             } 
             elseif( $ResponseCode == "0")
             {
			 $CheckoutRequestID = $request->{'Body'}->{'stkCallback'}->{'CheckoutRequestID'};
              $CallbackMetadata = $request->{'Body'}->{'stkCallback'}->{'CallbackMetadata'};
              $amount = $request->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->Item[0]->Value;
              $mpesaReceiptNumber = $request->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->Item[1]->Value;
           //   $balance = $request->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->Item[2]->Value;
              $TransactionDate = $request->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->Item[3]->Value;
              $phoneNumber = $request->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->Item[4]->Value;

        // create transaction 
        transactionCreate($db,$biller_code,$account,$phone,$amount,$fbcode,$CheckoutRequestID,$mpesaReceiptNumber);
   $db->commit();          
         }
		 
		 
		 
		 
function sendPushNotification($fbcode,$title,$body){
    $fcm_key='AAAAWPZyQpc:APA91bFaQ-4PckUnSP6stE5BFZ0dnXVgAwLg9yNRzTlwFzkA9zfiXIjzJ7zKrBD1dzZMRPYFcEZyi-93n8u-aUo7uo0Rvu9kuMxKmUUF88ovejnXHw7D38yl6rOp1CAvWyrbRrcJKfJq';
    $url = 'https://fcm.googleapis.com/fcm/send';
	
    $message = [
        "inbox"=>array(
            'id'             => 890,
        'title' =>     $title,
        'message'             => $body,
        'date' =>  date("jS F Y")
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
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_POST, true );
    curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
    $result = curl_exec ( $ch );
    curl_close ( $ch );
   return $result;
}

function transactionCreate($db,$biller_code,$account,$phone,$amount,$fbcode,$stk_code,$transaction_id) {
    //$biller_code = 'kplc_prepaid';
    //$account = '54601745380';
    //$phone = '0700314700';
    //$amount = '200';
    $vid = 'tidtech';
    $merchant_reference = uniqid();

    $datastring = "account=".$account."&amount=".$amount."&biller_name=".$biller_code."&merchant_reference=".$merchant_reference."&phone=".$phone."&vid=".$vid;
    $hashkey = "xUp0CR803!0Lg8hpjSzFb";
    $hashid = hash_hmac("sha256", $datastring, $hashkey);

    $post = [
        'account' => $account,
        'amount' => $amount,
        'biller_name'   => $biller_code,
        'merchant_reference'   => $merchant_reference,
        'phone'   => $phone,
        'vid'   => $vid,
        'hash'   => $hashid,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://apis.ipayafrica.com/ipay-billing/transaction/create');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
     $result= json_decode($data,true);
  if($result['header_status']==200){
    $ipay_reference=$result['msg']['ipay_reference'];
    $status=$result['msg']['status'];
    $posted_time=$result['msg']['posted_time'];					

    $update5 = "UPDATE `ipay_stkpush` SET `status` = '1',`ref_ipay` ='$ipay_reference',`transaction_id` = '$transaction_id' WHERE `stk_code` = '$stk_code' ";
	$stmt = $db->prepare($update5);
	$stmt->execute();
  }else{
    $update5 = "UPDATE `ipay_stkpush` SET `status` = '8',`transaction_id` = '$transaction_id' WHERE `stk_code` = '$stk_code' ";
	$stmt = $db->prepare($update5);
	$stmt->execute();
  }
  
}
  ?>
