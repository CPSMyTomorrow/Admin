<?php
$headers1 = array(                    
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest',
	'user-agent: Apache-HttpClient/UNA'
);


// DEV 
// $ps_url= "https://psdev01.cps-k12.org:80";
// PROD
$ps_url = "https://powerschool.cps-k12.org";
$auth_url = $ps_url."/oauth/access_token/";

// DEV ACCESS 
// $ps_code = "0acc272c-5e32-4147-acb5-b948f25830b1"; //Client ID
// $ps_secret = "4c4b8b25-f9b9-4349-969e-89881e74733f"; //Client Secret

//PROD ACCESS
$ps_code = "bcf6e5df-4b45-4541-89c8-4991210ec876";
$ps_secret = "1627ce47-8caf-4b67-a951-485d9544eccd";

$ch = curl_init($auth_url);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
curl_setopt($ch, CURLOPT_USERPWD, "$ps_code:$ps_secret");
$response = curl_exec($ch);
// print_r($response);
//echo "</br>";
$json = json_decode($response);
print_r($json);
$access_token = $json->access_token;


$url = "https://powerschool.cps-k12.org/ws/v1/district/school?pagesize=100&page=5";
$headers = array('Content-Type: application/x-www-form-urlencoded',"Authorization: Bearer {$access_token}");
$process = curl_init();
curl_setopt($process, CURLOPT_URL, $url);
curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
curl_setopt($process, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($process, CURLOPT_TIMEOUT, 30);
curl_setopt($process, CURLOPT_HTTPGET, 1);
curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
$response = curl_exec($process);
// print_r($response);
$xmlstring = simplexml_load_string($response);
$json_encode = json_encode($response);


foreach($xmlstring->children() as $key=>$school)
{ 
   $schools[]= array('id' => (string)$school->id, 'school_number' => (string)$school->school_number); 
}       
echo json_encode($schools);

$ch = curl_init();
curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/PowerSchool/SaveSchoolIds');
curl_setopt( $ch,CURLOPT_POST, true );
curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers1);
curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($schools));
$result = curl_exec($ch);
curl_close( $ch );

?>