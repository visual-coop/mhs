<?php
date_default_timezone_set("Asia/Bangkok");
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/external_error.log');

header("Access-Control-Allow-Methods: POST");
header('Content-Type: application/json;charset=utf-8');

if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET') {
	http_response_code(500);
	exit;
}

foreach ($_SERVER as $header_key => $header_value){
	if($header_key == "HTTP_LANG_LOCALE") {
		$headers["Lang_locale"] = $header_value;
	}
}
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/validate_input.php');
require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/authorized.php');
require_once(__DIR__.'/../include/control_log.php');

use Utility\library;
use Connection\connection;
use Authorized\Authorization;
use ControlLog\insertLog;
use ReallySimpleJWT\{Token,Parse,Jwt,Validate,Encode};
use ReallySimpleJWT\Exception\ValidateException;

$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);

$lib = new library();
$con = new connection();
$auth = new Authorization();
$log = new insertLog();
$jwt_token = new Token();

$conmysql = $con->connecttomysql();
$conoldmysql = $con->connecttooldmysql();
$conoracle = $con->connecttooracle();
$lang_locale = $headers["Lang_locale"] ?? "th";

if($lib->checkCompleteArgument(['member_no','logon_no','api_token','unique_id'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$member_no = $dataComing["member_no"];
	$checkUserLogonStatus = $conoldmysql->prepare("SELECT status FROM mdbuserlogon WHERE member_no = :member_no and log_no = :logon_no");
	$checkUserLogonStatus->execute([
		':member_no' => $member_no,
		':logon_no' => $dataComing["logon_no"]
	]);
	
	if($checkUserLogonStatus->rowCount() > 0){
		$logon_result = $checkUserLogonStatus->fetch(PDO::FETCH_ASSOC);
		if($logon_result['status'] == '1') {
			$checkResign = $conoracle->prepare("SELECT resign_status FROM mbmembmaster WHERE member_no = :member_no");
			$checkResign->execute([':member_no' => $member_no]);
			$rowResign = $checkResign->fetch(PDO::FETCH_ASSOC);
			if($rowResign["RESIGN_STATUS"] == '1'){
				$updateStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = '-6' WHERE member_no = :member_no");
				$updateStatus->execute([':member_no' => $member_no]);
				$arrayResult['RESPONSE_CODE'] = "WS0051";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$refresh_token = $lib->generate_token();
			try{
				$conmysql->beginTransaction();
				$getMemberLogged = $conmysql->prepare("SELECT id_token FROM gcuserlogin WHERE member_no = :member_no and channel = :channel and is_login = '1'");
				$getMemberLogged->execute([
					':member_no' => $member_no,
					':channel' => $arrPayload["PAYLOAD"]["channel"]
				]);
				if($getMemberLogged->rowCount() > 0){
					$arrayIdToken = array();
					while($rowIdToken = $getMemberLogged->fetch(PDO::FETCH_ASSOC)){
						$arrayIdToken[] = $rowIdToken["id_token"];
					}
					$updateLoggedOneDevice = $conmysql->prepare("UPDATE gctoken gt,gcuserlogin gu SET gt.rt_is_revoke = '-6',
																gt.at_is_revoke = '-6',gt.rt_expire_date = NOW(),gt.at_expire_date = NOW(),
																gu.is_login = '-5',gu.logout_date = NOW()
																WHERE gt.id_token IN(".implode(',',$arrayIdToken).") and gu.id_token IN(".implode(',',$arrayIdToken).")");
					$updateLoggedOneDevice->execute();
				}
				$insertToken = $conmysql->prepare("INSERT INTO gctoken(refresh_token,unique_id,channel,device_name,ip_address) 
													VALUES(:refresh_token,:unique_id,:channel,:device_name,:ip_address)");
				if($insertToken->execute([
					':refresh_token' => $refresh_token,
					':unique_id' => $dataComing["unique_id"],
					':channel' => $arrPayload["PAYLOAD"]["channel"],
					':device_name' => $arrPayload["PAYLOAD"]["device_name"],
					':ip_address' => $arrPayload["PAYLOAD"]["ip_address"]
				])){
					$id_token = $conmysql->lastInsertId();
					if(isset($dataComing["firsttime"])){
						$firstapp = 0;
					}else{
						$firstapp = 1;
					}
					$insertLogin = $conmysql->prepare("INSERT INTO gcuserlogin(member_no,device_name,channel,unique_id,status_firstapp,id_token) 
												VALUES(:member_no,:device_name,:channel,:unique_id,:firstapp,:id_token)");
					if($insertLogin->execute([
						':member_no' => $member_no,
						':device_name' => $arrPayload["PAYLOAD"]["device_name"],
						':channel' => $arrPayload["PAYLOAD"]["channel"],
						':unique_id' => $dataComing["unique_id"],
						':firstapp' => $firstapp,
						':id_token' => $id_token
					])){
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $conmysql->lastInsertId();
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['id_token'] = $id_token;
						$arrPayloadNew['member_no'] = $member_no;
						$arrPayloadNew['exp'] = time() + 900;
						$arrPayloadNew['refresh_amount'] = 0;
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
							$updateFCMToken = $conmysql->prepare("UPDATE gcmemberaccount SET fcm_token = :fcm_token WHERE member_no = :member_no");
							$updateFCMToken->execute([
								':fcm_token' => $dataComing["fcm_token"] ?? null,
								':member_no' => $member_no
							]);
						}
						$updateAccessToken = $conmysql->prepare("UPDATE gctoken SET access_token = :access_token WHERE id_token = :id_token");
						if($updateAccessToken->execute([
							':access_token' => $access_token,
							':id_token' => $id_token
						])){
							$conmysql->commit();
							$arrayResult['REFRESH_TOKEN'] = $refresh_token;
							$arrayResult['ACCESS_TOKEN'] = $access_token;
							$arrayResult['RESULT'] = TRUE;
							echo json_encode($arrayResult);
						}else{
							$conmysql->rollback();
							$filename = basename(__FILE__, '.php');
							$logStruc = [
								":error_menu" => $filename,
								":error_code" => "WS1001",
								":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
								":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
							];
							$log->writeLog('errorusage',$logStruc);
							$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Update ลง gctoken"."\n"."Query => ".$updateAccessToken->queryString."\n"."Data => ".json_encode([
								':access_token' => $access_token,
								':id_token' => $id_token
							]);
							$lib->sendLineNotify($message_error);
							$arrayResult['RESPONSE_CODE'] = "WS1001";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$conmysql->rollback();
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS1001",
							":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
							":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Insert ลง gcuserlogin"."\n"."Query => ".$insertLogin->queryString."\n"."Data => ".json_encode([
							':member_no' => $member_no,
							':device_name' => $arrPayload["PAYLOAD"]["device_name"],
							':channel' => $arrPayload["PAYLOAD"]["channel"],
							':unique_id' => $dataComing["unique_id"],
							':firstapp' => $firstapp,
							':id_token' => $id_token
						]);
						$lib->sendLineNotify($message_error);
						$arrayResult['RESPONSE_CODE'] = "WS1001";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$conmysql->rollback();
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1001",
						":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
						":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Insert ลง gctoken"."\n"."Query => ".$insertToken->queryString."\n"."Data => ".json_encode([
						':refresh_token' => $refresh_token,
						':unique_id' => $dataComing["unique_id"],
						':channel' => $arrPayload["PAYLOAD"]["channel"],
						':device_name' => $arrPayload["PAYLOAD"]["device_name"],
						':ip_address' => $arrPayload["PAYLOAD"]["ip_address"]
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1001";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}catch (PDOExecption $e) {
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1001",
					":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".$e->getMessage(),
					":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถเข้าสู่ระบบได้"."\n"."Error => ".$e->getMessage()."\n"."Data => ".json_encode($dataComing);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1001";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else {
			$arrayResult['RESPONSE_CODE'] = "WS0009";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(401);
			echo json_encode($arrayResult);
			exit();
		}
	}else {
		$arrayResult['RESPONSE_CODE'] = "WS0014";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>