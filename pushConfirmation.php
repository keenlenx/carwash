<?php
require 'DB.php';
try{
	$db=DB::get();
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$db->beginTransaction();
header("Content-Type:application/json");
if (!$request=file_get_contents('php://input'))
{
echo "Invalid input";
exit();
}
        $callbackData=json_decode($request);
        $resultCode=$callbackData->Body->stkCallback->ResultCode;
        $resultDesc=$callbackData->Body->stkCallback->ResultDesc;
        $merchantRequestID=$callbackData->Body->stkCallback->MerchantRequestID;
        $checkoutRequestID=$callbackData->Body->stkCallback->CheckoutRequestID;
        $Amount = $callbackData->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->{'Item'}[0]->{'Value'};
	$transid = $callbackData->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->{'Item'}[1]->{'Value'};
	$TransactionDate = $callbackData->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->{'Item'}[3]->{'Value'};
	$msisdn = $callbackData->{'Body'}->{'stkCallback'}->{'CallbackMetadata'}->{'Item'}[4]->{'Value'};
        $status='complete';
        

if($resultCode==0){
//var_dump($array);
	$update_query=$db->prepare("update transactions set status='1' where checkout_request_id='$checkoutRequestID' ");
	$update_query->execute();
	
	if($update_query){
	    $select_query=$db->prepare("select users.rfa_points,transactions.* from transactions left join users on users.id = transactions.user_id where transactions.checkout_request_id='$checkoutRequestID' ");
	if($select_query->execute(array())){
		while($row = $select_query->fetch()){
		    $user_id=$row['user_id'];
		    $pnts=intval($row['rfa_points'])+10;
		    $update_query=$db->prepare("update users set rfa_points='$pnts' where id='$user_id' ");
	        $update_query->execute();
		}}
	}
	
	
	echo '{"ResultCode":0,"ResultDesc":"Confirmation received successfully"}';
	
 
}
else{
echo '{"ResultCode":1,"ResultDesc":"Transaction failed"}';
}
}
catch(PDOException $e){
	print "Error: " . $e->getMessage() . "<br/>";
	die();
}
?>