<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','unique_id','api_token'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
		
	}
	$updateAccountStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = '-8',counter_wrongpass = 0 WHERE member_no = :member_no");
	if($updateAccountStatus->execute([':member_no' => $dataComing["member_no"]])){
		$struc = [
			':member_no' =>  $dataComing["member_no"],
			':device_name' =>  $dataComing["device_name"],
			':unique_id' =>  $dataComing["unique_id"]
		];
		$log->writeLog("lockaccount",$struc);
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS1001",
			":error_desc" => "ตั้ง Pin ไม่ได้ "."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$message_error = "ไม่สามารถล็อคบัญชีได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateAccountStatus->queryString."\n"."Param => ". json_encode([
			':member_no' => $dataComing["member_no"]
		]);
		$lib->sendLineNotify($message_error);
		$arrayResult['RESPONSE_CODE'] = "WS1001";
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