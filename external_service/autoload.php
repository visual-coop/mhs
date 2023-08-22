<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/external_error.log');
header("Access-Control-Allow-Methods: POST");

require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/validate_input.php');

use Utility\library;
use ReallySimpleJWT\{Token,Parse,Jwt,Validate,Encode};
use ReallySimpleJWT\Exception\ValidateException;
use Connection\connection;

$con = new connection();
$jwt_token = new Token();
$lib = new library();
$conmysql = $con->connecttomysql();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	header("Access-Control-Allow-Origin: *");
	header("Access-Control-Allow-Credentials: true");
	header("X-Frame-Options: sameorigin");
	header("X-XSS-Protection: 1; mode=block");
	header('Content-Type: application/json;charset=utf-8');
	header("X-Content-Type-Options: nosniff");
	header("Content-Security-Policy: default-src https: data: 'unsafe-inline' 'unsafe-eval'");
		
	$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
	$config = json_decode($jsonConfig,true);
	if(isset($dataComing)){
		if(isset($dataComing["verify_token"])){
			$jwt = new Jwt($dataComing["verify_token"], $config["VERIFY_KEY_EXTERNAL"]);
			$parse_token = new Parse($jwt, new Validate(), new Encode());
			try{
				$parsed_token = $parse_token->validate()
								->validateExpiration()
								->parse();
				$payload = $parsed_token->getPayload();
			}catch (ValidateException $e) {
				$errorCode = $e->getCode();
				if($errorCode === 3){
					$arrayResult['RESPONSE_CODE'] = "WS9001";
					$arrayResult['RESPONSE_MESSAGE'] = "Signature is invalid";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(401);
					echo json_encode($arrayResult);
					exit();
				}else if($errorCode === 4){
					$arrayResult['RESPONSE_CODE'] = "WS9002";
					$arrayResult['RESPONSE_MESSAGE'] = "Verify token was expired";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(401);
					echo json_encode($arrayResult);
					exit();
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS9003";
					$arrayResult['RESPONSE_MESSAGE'] = "Verify token is invalid";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(401);
					echo json_encode($arrayResult);
					exit();
				}
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS9004";
			$arrayResult['RESPONSE_MESSAGE'] = "Payload not complete";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
				echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS9005";
		$arrayResult['RESPONSE_MESSAGE'] = "Payload undefined";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(400);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	http_response_code(405);
	exit();
}
?>