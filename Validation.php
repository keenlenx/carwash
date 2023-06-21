<?php
try
{
    //Set the response content type to application/json
    header("Content-Type:application/json");
    $resp = '{"ResultCode":0,"ResultDesc":"Validation passed successfully"}';

} catch (Exception $ex){

    $resp = '{"ResultCode": 1, "ResultDesc":"Validation failure due to internal service error"}';
}
 
    echo $resp;
?>