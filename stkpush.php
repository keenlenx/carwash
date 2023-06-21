<?php
require 'DB.php';
header('Access-Control-Allow-Origin: *');
//$_POST = json_decode(file_get_contents('php://input'), true);
//receives from get request
if(isset($_REQUEST['amount'])){
$amount = $_REQUEST['amount'];
}
if(isset($_REQUEST['phone'])){
$mpesaNo = $_REQUEST['phone'];
}
if(isset($_REQUEST['user_id'])){
$user_id = $_REQUEST['user_id'];
}
if(isset($_REQUEST['redeemed_points'])){
$redeemed_points = $_REQUEST['redeemed_points'];
}else{
    $redeemed_points='0';
}
if (isset($_POST["services"])) {
	$services=$_POST["services"];
	}
$date=date("Y-m-d");
//$lnm_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$lnm_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$token=getAccessToken();
     $BusinessShortCode="7040938";//this is store number...use paybill number for paybills
     $LipaNaMpesaPasskey="dc03547bdb2d1a076a329f172944efab6fe246ff8921dace6ea0a3e9d874a139";
     $TransactionType="CustomerBuyGoodsOnline";//use CustomerPayBillOnline
     $Amount=$amount;
     $PartyA=$mpesaNo;
     $PartyB="9031835"; //this is till number...use paybill number
     $PhoneNumber=$mpesaNo;
     $CallBackURL="http://carwash.romeofoxalpha.co.ke/pushConfirmation.php";
     $AccountReference="please work";
     $TransactionDesc="desc";
     $Remarks="remarks";
        $timestamp='20'.date("ymdhis");
        $password=base64_encode($BusinessShortCode.$LipaNaMpesaPasskey.$timestamp);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $lnm_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$token));

        $curl_post_data = array(
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $TransactionType,
            'Amount' => $Amount,
            'PartyA' => $PartyA,
            'PartyB' => $PartyB,
            'PhoneNumber' => $PhoneNumber,
            'CallBackURL' => $CallBackURL,
            'AccountReference' => $AccountReference,
            'TransactionDesc' => $TransactionType
        );
        $data_string = json_encode($curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $curl_response=curl_exec($curl);
        $res=json_decode($curl_response);
        
        if($res->ResponseCode=='0'){
        $db=DB::get();
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$db->beginTransaction();
        $insert_query=$db->prepare("insert into transactions (user_id,checkout_request_id,amount,phone,redeemed_points,date) values (?,?,?,?,?,?)");
	$insert_query->bindParam(1, $user_id);
	$insert_query->bindParam(2, $res->CheckoutRequestID);
	$insert_query->bindParam(3, $amount);
	$insert_query->bindParam(4, $mpesaNo);
	$insert_query->bindParam(5, $redeemed_points);
	$insert_query->bindParam(6, $date);
	$insert_query->execute();
	$trans_id = $db->lastInsertId();
	addServiceIncome($db,$services,$trans_id,$date);
        }
        
        echo $curl_response;
		
function getAccessToken(){
    //$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $url='https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

$curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
  $credentials = base64_encode('OEoNPRBeNnb0Mtqti9nza7fCqnPYq3nV:Omu1Sik9o6mQasAh');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials)); //setting a custom header
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $curl_response = curl_exec($curl);
        //echo $curl_response;
        return json_decode($curl_response)->access_token;
    }

function addServiceIncome($db,$services,$trans_id,$date){
    $s_count=count($services);
    for($i=0;$i<$s_count;$i++){
		$s_query=$db->prepare("insert into service_income (service_id,transaction_id,amount,date) values (?,?,?,?)");
		$s_query->bindParam(1, $services[$i]->id);
		$s_query->bindParam(2, $trans_id);
		$s_query->bindParam(3, $services[$i]->amount);
		$s_query->bindParam(4, $date);
		$s_query->execute();		
	}
}
    
?>










