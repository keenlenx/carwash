
<?php
	$url = 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl';
	$access_token = getAccessToken();
	$shortCode = '730018';//change this
	$confirmationUrl = 'https://carwash.romeofoxalpha.co.ke/Confirmation.php';//change this
	$validationUrl = 'https://carwash.romeofoxalpha.co.ke/Validation.php';//change this
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer '.$access_token));
	$curl_post_data = array(
	  'ShortCode' => $shortCode,
	  'ResponseType' => 'Confirmed',
	  'ConfirmationURL' => $confirmationUrl,
	  'ValidationURL' => $validationUrl
	);
	$data_string = json_encode($curl_post_data);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
	$curl_response = curl_exec($curl);
	print_r($curl_response);
	echo $curl_response;

    function getAccessToken(){
        //$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $url='https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
      $credentials = base64_encode('OEoNPRBeNnb0Mtqti9nza7fCqnPYq3nV:Omu1Sik9o6mQasAh');//change this
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials)); //setting a custom header
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $curl_response = curl_exec($curl);
            //echo $curl_response;
            return json_decode($curl_response)->access_token;
        }
?>