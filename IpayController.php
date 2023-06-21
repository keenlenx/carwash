<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

$table_name_billers="billers";
$table_name_ipay_create="biller_transactions";
$table_name_ipay_settings="settings";
require 'DB.php';
//to do
///add push notif to database
try{
	$db=DB::get();
	$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

	if (isset($_REQUEST["action"])) {
	$object=$_REQUEST["action"];
	}
	if (isset($_REQUEST["account"])) {
	$account=$_REQUEST["account"];
	}
	if (isset($_REQUEST["biller_name"])) {
	$biller_name=$_REQUEST["biller_name"];
	}
    if (isset($_REQUEST["biller_code"])) {
        $biller_code=$_REQUEST["biller_code"];
        }
	if (isset($_REQUEST["phone"])) {
	$phone=$_REQUEST["phone"];
	}
	if (isset($_REQUEST["amount"])) {
	$amount=$_REQUEST["amount"];
    }
    if (isset($_REQUEST["token"])) {
        $reg_token=$_REQUEST["token"];
        }
           if ($object=="create") {
        //transactionCreate($db,$account,$phone,$amount,$biller_name,$reg_token);
        }
        else if($object=="fetchBillers"){
            queryBillers($db);
        }else if($object=="validateAccount"){
            if(checkBillerStatus($biller_code)){
            accountValidate($account,$biller_code);
            }
        }

     $db->commit();
}
catch(PDOException $e){
	print "Error: " . $e->getMessage() . "<br/>";
	die();
}

function transactionCreate($db,$biller_name,$account,$phone,$amount) {
    $vid = 'tidtech';
    $merchant_reference = uniqid();

    $datastring = "account=".$account."&amount=".$amount."&biller_name=".$biller_name."&merchant_reference=".$merchant_reference."&phone=".$phone."&vid=".$vid;
    $hashkey = "xUp0CR803!0Lg8hpjSzFb";
    $hashid = hash_hmac("sha256", $datastring, $hashkey);
    $post = [
        'account' => $account,
        'amount' => $amount,
        'biller_name'   => $biller_name,
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
     var_dump($result);
  if($result['header_status']==200){
    $table=$GLOBALS['table_name_ipay_create'];
    $ipay_reference=$result['msg']['ipay_reference'];
    $status=$result['msg']['status'];
    $posted_time=$result['msg']['posted_time'];
  $insert_query=$db->prepare("insert into $table (biller_name,account,mpesa_phone,amount,ipay_reference,status,posted_time,fbase_token) values (?,?,?,?,?,?,?,?)");
  $insert_query->bindParam(1, $biller_name);
  $insert_query->bindParam(2, $account);
  $insert_query->bindParam(3, $phone);
  $insert_query->bindParam(4, $amount);
  $insert_query->bindParam(5, $ipay_reference);
  $insert_query->bindParam(6, $status);
  $insert_query->bindParam(7, $posted_time);
  $insert_query->bindParam(8, "reg_token");
  $insert_query->execute();

      $response=[
          'code'=>'1',
          'message'=>'Request received.You will receive a message after processing.',
      ];
      $res = json_encode($response);
  }else{
    $response=[
        'code'=>'0',
        'message'=>'failed',
    ];
    $res = json_encode($response);
  }
  echo "$res";
}

 function accountBalance() {

    $vid="tidtech";

    $datastring = "vid=".$vid;
    $hashkey = "xUp0CR803!0Lg8hpjSzFb";
    $hashid = hash_hmac("sha256", $datastring, $hashkey);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 'https://apis.ipayafrica.com/ipay-billing/billing/account/balance?vid=tidtech&hash='.$hashid);

    $data = curl_exec($ch);
    
    curl_close($ch);
    
  $result= json_decode($data);
  var_dump($result);
}
 function moveFunds() {

    $vid="tidtech";
    $currency="KES";
    $time=time();
    $datastring = "currency=".$currency."&time=".$time."&vid=".$vid;
    $hashkey = "xUp0CR803!0Lg8hpjSzFb";
    $hashid = hash_hmac("sha256", $datastring, $hashkey);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, 'https://apis.ipayafrica.com/payments/v2/billing/fund');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,"currency=$currency&time=$time&vid=$vid&hash=$hashid");

    $data = curl_exec($ch);
    
    curl_close($ch);
    
  $result= json_decode($data);
  var_dump($result);
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
            'code'=>'400',
            'message'=>'Please try later'
        ];
        $res = json_encode($response);
        echo "$res";
        return false;
    }
  }else{
$response=[
        'code'=>'400',
        'message'=>'Please try later'
    ];
    $res = json_encode($response);
    echo "$res";
    return false;
  }
}

 function accountValidate($account,$biller_code) {
    //$account = '54601745380';//54601745380 //14286217741
    //$biller_code = 'kplc_prepaid';
    //$account_type=$biller_name;
    $vid = 'tidtech';

    $datastring = "account=".$account."&account_type=".$biller_code."&vid=".$vid;
    $hashkey = "xUp0CR803!0Lg8hpjSzFb";
    $hashid = hash_hmac("sha256", $datastring, $hashkey);

    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, 'https://apis.ipayafrica.com/ipay-billing/billing/validate/account');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,"account=$account&account_type=$biller_code&vid=$vid&hash=$hashid");
    $data = curl_exec($ch);
    curl_close($ch);
  $result= json_decode($data,true);
 if($result['status']==1){
      $response=[
          'code'=>'1',
          'name'=>strval($result['account_data']['name']),
          'account'=>strval($result['account_data']['account']),
          'balance'=>strval($result['account_data']['amountdue']),
      ];
      $res = json_encode($response);
  }else{
    $response=[
        'code'=>'0',
        'message'=>strval($result['text']),
    ];
    $res = json_encode($response);
  }
  echo "$res";
}

function billers($db) {
    $table=$GLOBALS['table_name_billers'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://apis.ipayafrica.com/ipay-billing/billing/list?vid=tidtech');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    if ($data === false) {
        throw new Exception(curl_error($ch), curl_errno($ch));
    }else{
  $result= json_decode($data,true);
  $array=$result['data'];
  for($i=0;$i<count($array);$i++){
      $b_name=$array[$i]['biller_name'];
      $b_code=$array[$i]['biller_code'];
      $b_cat=$array[$i]['biller_category'];
      $b_bal=$array[$i]['minimum_balance'];
      $b_acc=$array[$i]['secondary_account'];
    $insert_query=$db->prepare("insert into $table (biller_name,biller_code,biller_category,minimum_balance,secondary_account) values (?,?,?,?,?)");
    $insert_query->bindParam(1, $b_name);
    $insert_query->bindParam(2, $b_code);
    $insert_query->bindParam(3, $b_cat);
    $insert_query->bindParam(4, $b_bal);
    $insert_query->bindParam(5, $b_acc);
    $insert_query->execute();
  }
  echo("success");
}
 curl_close($ch); 
 
}

function queryBillers($db) {
    $table=$GLOBALS['table_name_billers'];
	$data=[];
	$query=$db->prepare("SELECT * FROM $table");   
   	if($query->execute(array())){
      while($row = $query->fetch()){
			array_push($data,[
		'id' => $row['id'],
		'biller_name' => $row['biller_name'],
        'biller_category' => $row['biller_category'],
        'minimum_balance' => $row['minimum_balance'],
        'secondary_account' => $row['secondary_account'],
        'biller_code' => $row['biller_code'],
        'enabled' => $row['enabled']==1 ? true :false,
		]);
		}
		
	}
		$result = json_encode($data);
      echo "$result"; 
}

function checkStatus($db){
         $table=$GLOBALS['table_name_ipay_create'];
        $data=[];
        $query=$db->prepare("SELECT * FROM $table WHERE status=1 ");   
           if($query->execute(array())){
          while($row = $query->fetch()){
                array_push($data,[
            'id' => $row['id'],
            'ipay_reference' => $row['ipay_reference'],
            ]);
            }
        }
        if(count($data)!=0){
            foreach ($data as $value) {
                $ipay_reference=$value['ipay_reference'];
                $vid = 'tidtech';
                $datastring = "ipay_reference=".$ipay_reference."&vid=".$vid;
    $hashkey = "xUp0CR803!0Lg8hpjSzFb";
    $hashid = hash_hmac("sha256", $datastring, $hashkey);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://apis.ipayafrica.com/ipay-billing/transaction/check/status');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,"ipay_reference=$ipay_reference&vid=$vid&hash=$hashid");
    $data = curl_exec($ch);
    curl_close($ch);
  $result= json_decode($data,true);
  $stat=$result['msg']['status'];
  $ref=$result['msg']['ipay_reference'];
  $sql = "UPDATE $table SET status=? WHERE ipay_reference=?";
  $db->prepare($sql)->execute([$stat, $ref]); 

              }
        }
}

function sendPushNotification($db,$token){
    $fcm_key= "AAAAWPZyQpc:APA91bFaQ-4PckUnSP6stE5BFZ0dnXVgAwLg9yNRzTlwFzkA9zfiXIjzJ7zKrBD1dzZMRPYFcEZyi-93n8u-aUo7uo0Rvu9kuMxKmUUF88ovejnXHw7D38yl6rOp1CAvWyrbRrcJKfJq";
    
/*     $table=$GLOBALS['table_name_ipay_settings'];
    $query=$db->prepare("SELECT fcm_server_key FROM $table WHERE id=100 ");   
    $query->execute();
    $fcm_key = $query->fetchColumn();  */           
    $url = 'https://fcm.googleapis.com/fcm/send';
    $message = [
        "inbox"=>array(
            'id'             => 890,
        'title' =>  'KPLC ELECTRICITY TOKENS',
        'message'             => '6776 67677 6628 3377 6667',
        'date' =>  1234
        ),
        'action'             => 'sales',
    ];
    $fields = array (
        "to"=> "dBGAJ7Hda-A:APA91bHOyjx7VAoH1Ohcshjv_JE20lRFnbRuVGPO5TNKGCBjpSjBa2Udw36UG9vKy14XzR5dD9A-lpjInXs-dJPmdhp_sgYtV0ZHym42-1z8_H2dO0PauHLNVam0YpttsWburY028suc",
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
}
?>