<?php

$Json_Request = file_get_contents("php://input");
$jsonData = json_decode($Json_Request,TRUE);
$dataComing = array();
$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$listOfRSADecrypt = ["password", "pin"];
$gensoftSCPrivatekey = openssl_pkey_get_private(file_get_contents(__DIR__.'/../config/cert/gensoft-server-to-client_privatekey.pem'));
$gensoftCSPublickey = openssl_pkey_get_public(file_get_contents(__DIR__.'/../config/cert/gensoft-client-to-server_pubkey.pem'));
$forceNewSecurity = false;
$isValidateRequestToken = false;

if(isset($jsonData) && is_array($jsonData)){
	
	if (isset($headers["Request_token"])) {
		$forceNewSecurity = true;
		$isValidateReqToken = openssl_verify($Json_Request, base64_decode($headers["Request_token"]), $gensoftCSPublickey, OPENSSL_ALGO_SHA512);
		if ($isValidateReqToken == 1) {
			$isValidateRequestToken = true;
		}
	}

	foreach($jsonData as $key => $data){
		if(!is_array($data)){
			if ($forceNewSecurity == true) {
				if (in_array($key, $listOfRSADecrypt) && is_string($data) && (strlen($data) > 64)) {
					$decrypted = $data;
					openssl_private_decrypt(base64_decode($data), $decrypted, $gensoftSCPrivatekey);
					$data = $decrypted;
				}
			}
			
			if(strpos($key,'_spc_') === false && strpos($key,'_emoji_') === false && strpos($key,'_root_') === false && $key != 'remark'){
				$dataComing[$key] = preg_replace('/[^\p{Thai}A-Za-z0-9 \/\-@_${}(),#*<>=:+!?.]/u','', strip_tags($data));
			}else if(strpos($key,'_emoji_') === false && strpos($key,'_root_') === false && $key != 'remark'){
				$dataComing[$key] = preg_replace("/[^\p{Thai}A-Za-z0-9 \/\-@_%(),'#|<>=:+!?.]/u",'', $data);
			}else if(strpos($key,'_root_') === false || $key == 'remark'){
				$dataComing[$key] = strip_tags($data);
			}else{
				$dataComing[$key] = $data;
			}
		}else{
			$dataComing[$key] = array_map(function($text){
				return preg_replace('/[^\p{Thai}A-Za-z0-9 \/\-@_${}(),#<>=:+!?.]/u','', $text);
			},$data);
		}
	}
}
?>