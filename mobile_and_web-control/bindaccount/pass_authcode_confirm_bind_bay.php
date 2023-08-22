<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','auth_code','sigma_key','coop_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$conmysql->beginTransaction();
		$updateBindAcc = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = '1',bind_date = NOW() WHERE sigma_key = :sigma_key");
		if($updateBindAcc->execute([':sigma_key' => $dataComing["sigma_key"]])){
			$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
			$arrPayloadverify = array();
			$arrPayloadverify['sigma_key'] = $dataComing["sigma_key"];
			$arrPayloadverify['auth_code'] = $dataComing["auth_code"];
			$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
			$arrPayloadverify['exp'] = time() + 300;
			$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData = array();
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/bay/authTokenAccount',$arrSendData);
			if(!$responseAPI["RESULT"]){
				$arrayResult['RESPONSE_CODE'] = "WS0022";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':bind_status' => '-9',
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $responseAPI["RESPONSE_MESSAGE"],
					':coop_account_no' => $coop_account_no,
					':query_flag' => '1'
				];
				$log->writeLog('bindaccount',$arrayStruc);
				$message_error = "ผูกบัญชีไม่ได้เพราะต่อ Service ไปที่ ".$config["URL_API_COOPDIRECT"]."/bay/authTokenAccount ไม่ได้ ตอนเวลา ".date('Y-m-d H:i:s');
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponse = json_decode($responseAPI);
			if($arrResponse->RESULT){
				$updateMask = $conmysql->prepare("UPDATE gcbindaccount SET deptaccount_no_bank = :mask_acc WHERE sigma_key = :sigma_key");
				if($updateMask->execute([
					':mask_acc' => $arrResponse->MASK_BANK_ACCOUNT,
					':sigma_key' => $dataComing["sigma_key"]
				])){
				}else{
					$message_error = "อัปเดตเลขบัญชีธนาคารไม่ได้ของ Sigma_key : ".$dataComing["sigma_key"]." เลขบัญชี : ".$arrResponse->MASK_BANK_ACCOUNT." ไม่ได้ ตอนเวลา ".date('Y-m-d H:i:s');
					$lib->sendLineNotify($message_error);
				}
				$conmysql->commit();
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':bind_status' => '1',
					':coop_account_no' => $coop_account_no
				];
				$log->writeLog('bindaccount',$arrayStruc);
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0039";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':bind_status' => '-9',
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $arrResponse->RESPONSE_MESSAGE,
					':coop_account_no' => $coop_account_no,
					':query_flag' => '1'
				];
				$log->writeLog('bindaccount',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0039";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':bind_status' => '-9',
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $arrResponse->RESPONSE_MESSAGE,
				':coop_account_no' => $coop_account_no,
				':query_flag' => '1'
			];
			$log->writeLog('bindaccount',$arrayStruc);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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