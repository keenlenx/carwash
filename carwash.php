<?php
header('Access-Control-Allow-Origin: *');
//flutter run -d chrome --web-hostname=127.0.0.1 --web-port=8200

$table_name_service="services";
$table_name_billers="billers";
$table_name_biller_transactions="biller_transactions";
$table_name_users="users";

require 'DB.php';

try{
	$db=DB::get();
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$db->beginTransaction();
	//$_REQUEST = json_decode(file_get_contents('php://input'), true);
	//var_dump($_REQUEST);
	
	if (isset($_REQUEST["action"])) {
	$object=$_REQUEST["action"];
	}
	if (isset($_REQUEST["password"])) {
	$password=$_REQUEST["password"];
	}
	if (isset($_REQUEST["email"])) {
	$email=$_REQUEST["email"];
	}
	
	if (isset($_REQUEST["userId"])) {
	$user_id=$_REQUEST["userId"];
	}
	else{
	$user_id=0;
	}
	if (isset($_REQUEST["enabled"])) {
	$active=intval($_REQUEST["enabled"]);
	}
	
	if (isset($_REQUEST["photo"])) {
	$photo=intval($_REQUEST["photo"]);
	}
	if (isset($_REQUEST["name"])) {
	$name=intval($_REQUEST["name"]);
	}
	if (isset($_REQUEST["token"])) {
	$token=$_REQUEST["token"];
	}
	

	if ($object=="login") {
	login($db,$email,$password);
	}
	if ($object=="fetchServices") {
		fetchServices($db,$user_id);
	}
	if ($object=="fetchBillers") {
		fetchBillers($db,$user_id);
	}
	if ($object=="fetchBillerTransactions") {
		fetchBillerTransactions($db,$user_id,$token);
	}
	if ($object=="registerUser") {
	signUp($db,$email,$token,$name,$photo);
	}
	if ($object=="registerDevice") {
	signUpDevice($db,$token);
	}
}
catch(PDOException $e){
	print "Error: " . $e->getMessage() . "<br/>";
	die();
}

class Status 
{
	public $code="";//0 for fail //1 for success
	public $message="";
}

Class Response{
	public $status=null;
	public $data=[];
	public $summary=[];
}
Class home{
	public $status=null;
	public $data=[];
}

function signUp($db,$email,$token,$name,$photo){
	$status=new Status();
	$response=new Response();
	$table=$GLOBALS['table_name_users'];
    $data=[];
    $password=uniqid();
    $query=$db->prepare("SELECT * FROM $table WHERE email='$email'");   
    $query->execute();
    $row = $query->fetch();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status->code='0';
	$status->message='Invalid Email address';
    }
    else if(!$row){
    $insert_query=$db->prepare("insert into $table (email,name,photo,token) values (?,?,?,?)");
	$insert_query->bindParam(1, $email);
	$insert_query->bindParam(2, $name);
	$insert_query->bindParam(3, $photo);
	$insert_query->bindParam(4, $token);
	$insert_query->execute();
	$id = $db->lastInsertId();
	$data=[
	'id'=>$id,
	'email'   => $email,
        'name' => $name,
        'photo' => $photo,
		'rfa_points' => '0',
	];
	$status->code='1';
	$status->message='success';
	$response->status=$status;
	$response->data=$data;
    }else{
    	$data=[
	'id'=>$id,
	'email'   => $email,
        'name' => $name,
        'photo' => $photo,
		'rfa_points' => $row['rfa_points'],
	];
	$status->code='1';
	$status->message='success';
	$response->status=$status;
	$response->data=$data;
    }
    $result = json_encode($response);
     echo "$result";
	$db->commit();
}

function signUpDevice($db,$token){
	$table=$GLOBALS['table_name_users'];
    $data=[];
    $password=uniqid();
    $query=$db->prepare("SELECT * FROM $table WHERE token='$token'");   
    $query->execute();
    $row = $query->fetch();
    if(!$row){
    $insert_query=$db->prepare("insert into $table (token) values (?)");
	$insert_query->bindParam(1, $token);
	$insert_query->execute();
	$id = $db->lastInsertId();
	$data=[
	'id'=>$id,
	'rfa_points' => '0',
	'email'   => 'email',
        'name' => 'name',
        'photo' => 'photo',
	];
    }else{
    	$data=[
	'id'=>$row['id'],
	'email'   => $row['email'],
        'name' => $row['name'],
        'photo' => $row['photo'],
		'rfa_points' => $row['rfa_points'],
	];
    }
    $result = json_encode($data);
     echo "$result";
	$db->commit();
}

function login($db,$phone,$password){
	$status=new Status();
	$response=new Response();
	$data=null;
	$table=$GLOBALS['table_name_users'];
	$query=$db->prepare("SELECT * FROM $table WHERE phone='$phone' && password='$password' ");   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($row = $query->fetch()){
			$data=[
		'id' => $row['id'],
        'phone'   => $row['phone'],
        'password' => $row['password'],
		'email' => $row['email'],
		'loyalty_points' => $row['loyalty_points'],
		];
		}

	$status->code='1';
	$status->message='login successful';
	$response->status=$status;
	$response->data=$data;

} else {
     $status->code='0';
	$status->message='login failed';
	$response->status=$status;
	$response->data=$data;
}
	}
	
	$result = json_encode($response);
     echo "$result";
	$db->commit();
}

function fetchServices($db,$user_id){
	$status=new Status();
	$response=new Response();
	$data=[];
	$table=$GLOBALS['table_name_service'];
	$query=$db->prepare("SELECT * FROM $table ");   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($row = $query->fetch()){
			array_push($data, [
		'id' => $row['id'],
		'title' => $row['title'],
        'image'   => 'https://carwash.romeofoxalpha.co.ke/uploads/'.$row['image'],
		'amount' => $row['cost'],
		'notes' => $row['notes'],
		'calculated' => $row['calculated']!=0 ? true : false,
        'enabled' => $row['enabled']!=0 ? true : false,
		]);
		}
}
		
	}
		$result = json_encode($data);
      echo "$result";
	
	$db->commit();
}

function fetchBillers($db,$user_id){
	$status=new Status();
	$response=new Response();
	$data=[];
	$table=$GLOBALS['table_name_billers'];
	$query=$db->prepare("SELECT * FROM $table ");   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($row = $query->fetch()){
			array_push($data, [
		'id' => $row['id'],
		'icon' => 'https://carwash.romeofoxalpha.co.ke/uploads/'.$row['icon'],
		'biller_name' => $row['biller_name'],
        'biller_code'   => $row['biller_code'],
		'biller_category' => $row['biller_category'],
		'minimum_balance' => $row['minimum_balance'],
		'secondary_account' => $row['secondary_account'],
        'enabled' => $row['enabled']!=0 ? true : false,
		]);
		}
	$status->code='1';
	$status->message='success';
	$response->status=$status;
	$response->data=$data;
} else {
$status->code='0';
	$status->message='failed';
	$response->status=$status;
	$response->data=$data;

}
		
	}
		$result = json_encode($response);
      echo "$result";
	
	$db->commit();
}


function fetchBillerTransactions($db,$user_id,$token){
	$data=[];
	$query=$db->prepare("SELECT * FROM ipay_stkpush WHERE user_id='$user_id' || fb_code='$token' LIMIT 30");   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($value = $query->fetch()){
			array_push($data, [
		'id' => $value['id'],
					'msisdn' => $value['msisdn'],
					'amount' => $value['amount'],
					'biller_code' => $value['biller_code'],
					'status' => $value['status'],
					'transaction_id' => $value['transaction_id'],
					'account' => $value['account'],
					'units' => $value['units'],
					'token' => $value['token'],
					'date' => date('d M D', strtotime($value['date_created'])),
					'time' => date('h:i.A', strtotime($value['date_created'])),
		]);
		}
}
		
	}
		$result = json_encode($data);
      echo "$result";
	
	$db->commit();
}


?>