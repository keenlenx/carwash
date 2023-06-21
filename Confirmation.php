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
        $transactionType=$callbackData->TransactionType;
        $transID=$callbackData->TransID;
        $transTime=$callbackData->TransTime;
        $transamount=$callbackData->TransAmount;
        $businessShortCode=$callbackData->BusinessShortCode;
        $transid=$callbackData->BillRefNumber;
        $invoiceNumber=$callbackData->InvoiceNumber;
        $orgAccountBalance=$callbackData->OrgAccountBalance;
        $thirdPartyTransID=$callbackData->ThirdPartyTransID;
        $msisdn=$callbackData->MSISDN;
        $firstName=$callbackData->FirstName;
        $middleName=$callbackData->MiddleName;
        $lastName=$callbackData->LastName;
        $status='complete';

if($resultCode==0){
//var_dump($array);
	$insert_query=$db->prepare("insert into transactions (transaction_id,amount,phone,status) values (?,?,?,?)");
	$insert_query->bindParam(1, $transid);
	$insert_query->bindParam(2, $transamount);
	$insert_query->bindParam(3, $msisdn);
	$insert_query->bindParam(4, $status);
	$insert_query->execute();
	if($insert_query->rowCount()){
	echo '{"ResultCode":0,"ResultDesc":"Confirmation received successfully"}';
	}
 
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