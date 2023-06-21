<?php
require 'DB.php';
    $db=DB::get();
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$db->beginTransaction();

    $sqla1="SELECT * FROM  ipay_stkpush where  status!= '9' AND status!= '2' ";
   	$query=$db->prepare($sqla1);   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($value = $query->fetch()){
	        $ipay_reference = $value['ref_ipay'];
            $fbcode = $value['fb_code'];
            $user_id = $value['user_id'];
            $phone = $value['msisdn'];
            $amount = $value['amount'];
            $biller_code = $value['biller_code'];
            $account = $value['account']; 
            if($biller_code=="safaricom" || $biller_code=="airtel" || $biller_code=="telkom" || $biller_code=="jtl"){
                if (substr($account, 0, 1) == "0") {
                 $account = "254" . substr($account, -9);
             }
         }
            $status = $value['status']; 
            $id = $value['id'];
            
             if($status=='1'){
                $vid = 'tidtech';
                $datastring = "reference=".$ipay_reference."&vid=".$vid;
                $hashkey = "xUp0CR803!0Lg8hpjSzFb";
                $hashid = hash_hmac("sha256", $datastring, $hashkey);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://apis.ipayafrica.com/ipay-billing/transaction/check/status');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS,"reference=$ipay_reference&vid=$vid&hash=$hashid");
                $data = curl_exec($ch);
                curl_close($ch);
                $result= json_decode($data,true);
                if($result['header_status']==200){
                if($result['msg']['status']==1 && $result['msg']['text']['transaction_status']=='SUCCESS'){
                    $stat=$result['msg']['status'];
                    $ref=$result['msg']['ipay_reference'];
                    if ($biller_code=="safaricom" || $biller_code=="airtel" || $biller_code=="telkom" || $biller_code=="jtl"){
                        $title = "Your " .$biller_code." Airtime Purchase was sucessfull ";
                        $sendsms = "Congrats! You just purchased ". $biller_code." Airtime worth ". $amount." ";
                        $update5 = "UPDATE `ipay_stkpush` SET `status` = '2' WHERE `ref_ipay` = '$ipay_reference' ";
                   }
                    else if ($biller_code=="kplc_prepaid" ){
                  if(isset($result['msg']['text']['details']['token'])){
                       $token=$result['msg']['text']['details']['token']; 
                  $units=$result['msg']['text']['details']['units']; 
                  $biller_reference=$result['msg']['text']['biller_reference'];
                $sendsms = "Congrats! You just purchased ". $units." Units worth ". $amount." Token number: ".$token;
                $title = "Your KPLC Purchase was sucessfull ";
                $update5 = "UPDATE `ipay_stkpush` SET `status` = '2',`token` = '$token', `biller_reference` = '$biller_reference',`units` = '$units' WHERE `ref_ipay` = '$ipay_reference' ";
                  }
                   }
                    else if ($biller_code=="kplc_postpaid" ){
                        $title = "Your KPLC Purchase was sucessfull ";
                 $biller_reference=$result['msg']['text']['biller_reference'];
                        $sendsms = "Congrats! You Payment for ". $biller_code." worth ". $amount." was successfull Ref ".$biller_reference."";
                        $update5 = "UPDATE `ipay_stkpush` SET `status` = '2',`token` = '$biller_reference' ,`biller_reference` = '$biller_reference' WHERE `ref_ipay` = '$ipay_reference' ";
                        $date = time();
                    }else if ($biller_code=="gotv" || $biller_code=="dstv" || $biller_code=="zuku" || $biller_code=="startimes" ){
                        $title = "Your . $biller_code. Purchase was sucessfull ";
                 $biller_reference=$result['msg']['text']['biller_reference'];
                        $sendsms = "Congrats! You Payment for ". $biller_code." worth ". $amount." was successfull Ref ".$biller_reference."";
                        $update5 = "UPDATE `ipay_stkpush` SET `status` = '2', `biller_reference` = '$biller_reference' WHERE `ref_ipay` = '$ipay_reference' ";
                        $date = time();
                    }
                
                    //to do Water logic

			    $stmt = $db->prepare($update5);
	            $stmt->execute();
                sendPushNotification($fbcode,$title,$sendsms);
                }
              }
            
            }if($status=='8'){
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

    $update5 = "UPDATE `ipay_stkpush` SET `status` = '1',`ref_ipay` = '$ipay_reference' WHERE `id` = '$id' ";
			$stmt = $db->prepare($update5);
	            $stmt->execute();
  }
            }
		}
}
	}
	
	$db->commit();
    
      
    function sendPushNotification($fbcode,$title,$sendsms)
	{
			$fcm_key = 'AAAAWPZyQpc:APA91bFaQ-4PckUnSP6stE5BFZ0dnXVgAwLg9yNRzTlwFzkA9zfiXIjzJ7zKrBD1dzZMRPYFcEZyi-93n8u-aUo7uo0Rvu9kuMxKmUUF88ovejnXHw7D38yl6rOp1CAvWyrbRrcJKfJq';

			$url = 'https://fcm.googleapis.com/fcm/send';
			$message = [
                'message' => $sendsms,
				'action' => 'bill_payment',
			];
			$notification = [
				"title" => $title,
				"body" => $sendsms,
			];
			$fields = array(
				"to" => $fbcode,
				'data'              => $message,
				'notification'              => $notification,
				'priority'          => 'high',
			);
			$fields = json_encode($fields);
			$headers = array(
				'Authorization: key=' . $fcm_key,
				'Content-Type: application/json'
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			$result = curl_exec($ch);

			curl_close($ch);
	}
             ?>