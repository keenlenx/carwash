<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");

$table_name_users="users";
$table_name_service="services";
$table_name_admins="admin";
require 'DB.php';


try{
	$db=DB::get();
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$db->beginTransaction();
	//$_REQUEST = json_decode(file_get_contents('php://input'), true);
	
	if (isset($_REQUEST["action"])) {
	$object=$_REQUEST["action"];
	}
	if (isset($_REQUEST["month"])) {
	$month=$_REQUEST["month"];
	}
	if (isset($_REQUEST["amount"])) {
	$amount=$_REQUEST["amount"];
	}
	if (isset($_REQUEST["password"])) {
	$password=$_REQUEST["password"];
	}
	if (isset($_REQUEST["email"])) {
	$email=$_REQUEST["email"];
	}
		if (isset($_REQUEST["date"])) {
	$date=$_REQUEST["date"];
	}
	
	if (isset($_REQUEST["userId"])) {
	$user_id=$_REQUEST["userId"];
	}
	else{
	$user_id=0;
	}
	if (isset($_REQUEST["enabled"])) {
	$active=$_REQUEST["enabled"];
	}
	if (isset($_REQUEST["notes"])) {
	$notes=$_REQUEST["notes"];
	}
	if (isset($_REQUEST["title"])) {
	$title=$_REQUEST["title"];
	}
	if (isset($_REQUEST["service_id"])) {
	$service_id=$_REQUEST["service_id"];
	}
	if (isset($_REQUEST["calculated"])) {
	$calculated=$_REQUEST["calculated"];
	}
	
	if ($object=="login") {
	login($db,$email,$password);
	}
	if ($object=="newService") {
	newService($db,$notes,$title,$amount,$calculated);
	}
	if($object=="fetchServices"){
		fetchServices($db,$user_id);
	}
	if ($object=="signUp") {
	signUp($db,$email);
	}
	if ($object=="fetchTransactions") {
	fetchTransactions($db,$date);
	}
	if ($object=="fetchBillerTransactions") {
	fetchBillerTransactions($db);
	}
	if($object=="editService"){
	    editService($db,$title,$amount,$service_id,$notes);
	}
	if($object=="activateService"){
	    activationService($db,$active,$service_id);
	}
	if($object=="deleteService"){
	    deleteService($db,$service_id);
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

function signUp($db,$email){
	$status=new Status();
	$table=$GLOBALS['table_name_admins'];
    $data=[];
    $password=uniqid();
    $query=$db->prepare("SELECT * FROM $table WHERE email='$email' ");   
    $query->execute();
    $row = $query->fetch();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $status->code='0';
	$status->message='Invalid Email address';
    }
    else if(!$row){
    $insert_query=$db->prepare("insert into $table (email,password) values (?,?)");
	$insert_query->bindParam(1, $email);
	$insert_query->bindParam(2, $password);
	$insert_query->execute();
	sendEmail($email,$password);
	$status->code='1';
	$status->message='Please check your email for password';
    }else{
    	$status->code='0';
	$status->message='Email already exists';
    }
    $result = json_encode($status);
     echo "$result";
	$db->commit();
}

function newService($db,$notes,$title,$amount,$calculated){
	$status=new Status();
	$response=new Response();
	$table=$GLOBALS['table_name_service'];
    $data=[];
	$banner_name = $_FILES['banner']['name'];
	$banner_tmp =$_FILES['banner']['tmp_name'];
	//var_dump($_SERVER['DOCUMENT_ROOT']);die;
	move_uploaded_file($banner_tmp,$_SERVER['DOCUMENT_ROOT'].'/uploads/'.$banner_name);
    $query=$db->prepare("SELECT * FROM $table WHERE title='$title' ");   
    $query->execute();
    $row = $query->fetch();
    if(!$row){
    $insert_query=$db->prepare("insert into $table (title,image,cost,notes,calculated) values (?,?,?,?,?)");
	$insert_query->bindParam(1, $title);
	$insert_query->bindParam(2, $banner_name);
	$insert_query->bindParam(3, $amount);
	$insert_query->bindParam(4, $notes);
	$insert_query->bindParam(5, $calculated);
	$insert_query->execute();
	$id = $db->lastInsertId();
	$data=[
	    'id'=>$id,
	    'title'   => $title,
        'image' => $banner_name,
        'amount' => $amount,
        'notes' => $notes,
		'enabled' => true,
	];
	$status->code='1';
	$status->message='success';
	$response->status=$status;
	$response->data=$data;
    }
	else{
    	$status->code='0';
	$status->message='service already exists';
	$response->status=$status;
    }
    $result = json_encode($response);
     echo "$result";
	$db->commit();
}

function login($db,$username,$password){
	$status=new Status();
	$response=new Response();
	$data=null;
	$table=$GLOBALS['table_name_admins'];
	$query=$db->prepare("SELECT * FROM $table WHERE username='$username' && password='$password' ");   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($row = $query->fetch()){
			$data=[
		'id' => $row['id'],
        'username'   => $row['username'],
        'password' => $row['password'],
		'email' => $row['email'],
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
        'enabled' => $row['enabled']!=0 ? true : false,
		]);
		}
} 
		
	}
		$result = json_encode($data);
      echo "$result";
	
	$db->commit();
}

function fetchTransactions($db,$date){
	$data=[];
	$idata=[];
	$query=$db->prepare("SELECT * FROM transactions WHERE date='$date' && status!='0' ");   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($row = $query->fetch()){
			array_push($data, [
		'id' => $row['id'],
		'transaction_id' => $row['transaction_id'],
        'amount'   => $row['amount'],
		'timestamp' => date('h:i.A', strtotime($row['timestamp'])),
		'date' => $row['date'],
        'phone' => $row['phone'],
        'status' => $row['status'],
		]);
		}
			
	$iquery=$db->prepare("SELECT * FROM service_income WHERE date='$date' ");   
   	if($iquery->execute(array())){
   		if($iquery->rowCount()) {
      while($row = $iquery->fetch()){
			array_push($idata, [
		'id' => $row['id'],
		'transaction_id' => $row['transaction_id'],
        'amount'   => $row['amount'],
		'timestamp' => $row['timestamp'],
		'date' => $row['date'],
        'service_id' => $row['service_id'],
		]);
		}
	$status->code='1';
	$status->message='success';
	$response->status=$status;
	$response->data=$data;
}
	}
	$res=array(
	    'code'=>'1',
	    'message'=>'success',
	    'transactions'=>$data,
	    's_income'=>$idata
	    );
		
} else {
$res=array(
	    'code'=>'0',
	    'message'=>'failed',
	    'transactions'=>[],
	    's_income'=>[]
	    );

}
		
	}
	
		$result = json_encode($res);
      echo "$result";
	
	$db->commit();
}

function fetchBillerTransactions($db){
	$data=[];
	$query=$db->prepare("SELECT * FROM ipay_stkpush LIMIT 50");   
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



function editService($db,$title,$amount,$service_id,$notes){
	$status=new Status();
	$response=new Response();
	$table=$GLOBALS['table_name_service'];
	$logo = $_FILES['banner']['name'];
	if($logo!=null){
		$file_tmp =$_FILES['banner']['tmp_name'];
	move_uploaded_file($file_tmp,$_SERVER['DOCUMENT_ROOT'].'/uploads/'.$logo);
	$sql = "UPDATE $table SET title='$title',cost='$amount',image='$logo' WHERE id='$service_id' ";
	}else{
		$sql = "UPDATE $table SET title='$title',cost='$amount' WHERE id='$service_id' ";
	}
	$stmt = $db->prepare($sql);
	$stmt->execute();
	if($stmt->rowCount()){
	    $data=[
	    'id'=>$service_id,
	    'title'   => $title,
        'image' => 'https://carwash.romeofoxalpha.co.ke/uploads/'.$logo,
        'amount' => $amount,
        'notes' => $notes,
	];
	$status->code='1';
	$status->message='update success';
	$response->status=$status;
	$response->data=$data;
	}
	else{
	$status->code='0';
	$status->message='update failed';
	$response->status=$status;
	}
	$result = json_encode($response);
      echo "$result";
	  $db->commit();
}
function deleteService($db,$service_id){
	$status=new Status();
	$table=$GLOBALS['table_name_service'];
	$sql = "DELETE FROM $table WHERE id='$service_id' ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	
	$status->code='1';
	$status->message='delete success';

	$result = json_encode($status);
      echo "$result";
	  $db->commit();
}
function activationService($db,$enabled,$service_id){
	$status=new Status();
	$response=new Response();
	$table=$GLOBALS['table_name_service'];
	$sql = "UPDATE $table SET enabled='$enabled' WHERE id='$service_id' ";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	if($stmt->rowCount()){
	$status->code='1';
	$status->message='update success';
	$response->status=$status;
	}
	else{
	$status->code='0';
	$status->message='update failed';
	$response->status=$status;
	}
	$result = json_encode($response);
      echo "$result";
	  $db->commit();
}

function forgotPassword($db,$username){
	$status=new Status();
	$response=new Response();
	$data=null;
	$table=$GLOBALS['table_name_admins'];
	$query=$db->prepare("SELECT * FROM $table WHERE username='$username' ");   
   	if($query->execute(array())){
   		if($query->rowCount()) {
      while($row = $query->fetch()){
		$email= $row['email'];
		$password= $row['password'];
		}
sendEmail($email,$password);
	$status->code='1';
	$status->message='email sent';
	$response->status=$status;
	$response->data=$data;

} else {
     $status->code='0';
	$status->message='user not found';
	$response->status=$status;
	$response->data=$data;
}
	}
	
	$result = json_encode($response);
     echo "$result";
	$db->commit();
}
function sendEmail($email,$password){
/*$mail = new PHPMailer();
//Enable SMTP debugging.
$mail->SMTPDebug = 0;                               
//Set PHPMailer to use SMTP.
$mail->isSMTP();            
//Set SMTP host name                          
$mail->Host = "smtp.gmail.com";
//Set this to true if SMTP host requires authentication to send email
$mail->SMTPAuth = true;                          
//Provide username and password     
$mail->Username = "chekikeja@gmail.com";                 
$mail->Password = "king6727";                           
//If SMTP requires TLS encryption then set it
//$mail->SMTPSecure = "tls";      
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;                     
//Set TCP port to connect to
$mail->Port = 587;                                   

$mail->From = "chekikeja@gmail.com";
$mail->FromName = "chekikeja@gmail.com";

$mail->addAddress($email);

$mail->Subject = "WELCOME TO CHEKI KEJA";
$message="Your login password is: ".$password;
$mail->Body =  $message;
try {
    $mail->send();
   // echo "Message has been sent successfully";
} catch (Exception $e) {
   // echo "Mailer Error: " . $mail->ErrorInfo;
}
*/
}

?>