<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','tel','ref_old_otp'],$dataComing)){
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
		require_once('../../include/exit_footer.php');
		
	}
	$conmysql->beginTransaction();
	$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
	$updateOldOTP = $conmysql->prepare("UPDATE gcotp SET otp_status = '-9' WHERE refno_otp = :ref_old_otp and otp_status = '0'");
	$updateOldOTP->execute([':ref_old_otp' => $dataComing["ref_old_otp"]]);
	$templateMessage = $func->getTemplateSystem("OTPChecker",1);
	$otp_password = $lib->randomText('number',6);
	$reference = $lib->randomText('all',6);
	$duration_expire = $func->getConstant('duration_otp_expire') ? $func->getConstant('duration_otp_expire') : '5';
	$expire_date = date('Y-m-d H:i:s',strtotime('+'.$duration_expire.' minutes'));
	$arrTarget["RANDOM_NUMBER"] = $otp_password;
	$arrTarget["RANDOM_ALL"] = $reference;
	$arrTarget["DATE_EXPIRE"] = $lib->convertdate($expire_date,'D m Y',true);
	$arrMessage = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$arrTarget);
	$arrayComing["TEL"] = $dataComing["tel"];
	$arrayComing["MEMBER_NO"] = $member_no;
	$arrayTel[] = $arrayComing;
	$bulkInsert = array();
	$arrayDest = array();
	if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != "" && mb_strlen($arrayTel[0]["TEL"]) == 10){
		$insertOTP = $conmysql->prepare("INSERT INTO gcotp(refno_otp,otp_password,destination_number,expire_date,otp_text)
											VALUES(:ref_otp,:otp_pass,:destination,:expire_date,:otp_text)");
		if($insertOTP->execute([
			':ref_otp' => $reference,
			':otp_pass' => $otp_password,
			':destination' => $arrayTel[0]["TEL"],
			':expire_date' => $expire_date,
			':otp_text' => $arrMessage["BODY"]
		])){
			$arrayDest["member_no"] = $member_no;
			$arrayDest["tel"] = $arrayTel[0]["TEL"];
			$arrayDest["message"] = $arrMessage["BODY"];
			$arraySendSMS = $lib->sendSMS($arrayDest);
			if($arraySendSMS["RESULT"]){
				$arrayLogSMS = $func->logSMSWasSent(null,$arrMessage["BODY"],$arrayTel,'system');
				$conmysql->commit();
				$arrayResult['REFERENCE_OTP'] = $reference;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$bulkInsert[] = "('".$arrMessage["BODY"]."','".$member_no."',
						'mobile_app',null,null,'ส่ง SMS ไม่ได้เนื่องจาก Service ให้ไปดูโฟลเดอร์ Log','system',null)";
				$func->logSMSWasNotSent($bulkInsert);
				unset($bulkInsert);
				$bulkInsert = array();
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0018";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$conmysql->rollback();
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1011",
				":error_desc" => "ไม่สามารถ Resend OTP ได้"."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถ Resend OTP ได้เพราะ Insert ลง gcotp ไม่ได้"."\n"."Query => ".$deleteHistory->queryString."\n"."Param => ". json_encode([
				':member_no' => $payload["member_no"],
				':his_type' => $dataComing["type_history"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1011";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0017";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
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
	require_once('../../include/exit_footer.php');
	
}
?>