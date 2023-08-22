<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/core_error.log');
error_reporting(E_ERROR);

header("Access-Control-Allow-Headers: Origin, Content-Type ,X-Requested-With, Accept, Authorization ");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");
header('Content-Type: application/json;charset=utf-8');
header('Cache-Control: max-age=86400');
header("X-Frame-Options: sameorigin");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src https: data: 'unsafe-inline' 'unsafe-eval'");

foreach ($_SERVER as $header_key => $header_value){
	if($header_key == "HTTP_AUTHORIZATION" ){
		$headers["Authorization"] = $header_value;
	}
}
if( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ) {
   ob_start("ob_gzhandler");
}else{
   ob_start();
}
// Require files
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../autoloadConnection.php');
require_once(__DIR__.'/../include/validate_input.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../include/control_log.php');

// Call functions
use Utility\library;
use Component\functions;
use ControlLog\insertLog;
use PHPMailer\PHPMailer\{PHPMailer,Exception};
use ReallySimpleJWT\{Token,Parse,Jwt,Validate,Encode};
use ReallySimpleJWT\Exception\ValidateException;

$mailFunction = new PHPMailer(false);
$lib = new library();
$jwt_token = new Token();
$func = new functions();
$log = new insertLog();
$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);

if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
	$payload = array();
	// Complete Argument
	if(isset($headers["Authorization"]) && strlen($headers["Authorization"]) > 15){
		$author_token = $headers["Authorization"];
		if(substr($author_token,0,6) === 'Bearer'){
			$access_token = substr($author_token,7);
				
			$jwt = new Jwt($access_token, $config["SECRET_KEY_CORE"]);

			$parse_token = new Parse($jwt, new Validate(), new Encode());
			try{
				$parsed_token = $parse_token->validate()
					->validateExpiration()
					->parse();
				$payload = $parsed_token->getPayload();
				if(!$lib->checkCompleteArgument(['section_system','username','exp'],$payload)){
					$arrayResult['RESULT'] = FALSE;
					http_response_code(400);
					require_once(__DIR__.'/../include/exit_footer.php');
				}
			}catch (ValidateException $e) {
				$errorCode = $e->getCode();
				if($errorCode === 3){
					$arrayResult['RESULT'] = FALSE;
					http_response_code(401);
					require_once(__DIR__.'/../include/exit_footer.php');
				}else if($errorCode === 4){
					$arrayResult['RESULT'] = FALSE;
					http_response_code(401);
					require_once(__DIR__.'/../include/exit_footer.php');
				}else{
					$arrayResult['RESULT'] = FALSE;
					http_response_code(401);
					require_once(__DIR__.'/../include/exit_footer.php');
				}
			}
		}else{
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
			require_once(__DIR__.'/../include/exit_footer.php');
		}
	}
}else{
	$arrayResult['RESULT'] = TRUE;
	http_response_code(203);
	require_once(__DIR__.'/../include/exit_footer.php');
}
?>