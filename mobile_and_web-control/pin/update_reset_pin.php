<?php
require_once('../autoload.php');

$updateResetPin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = null WHERE member_no = :member_no");
if($updateResetPin->execute([
	':member_no' => $payload["member_no"]
])){
	if($func->logoutAll(null,$payload["member_no"],'-10')){
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS1016",
			":error_desc" => "รีเซ็ต Pin ไม่ได้เพราะไม่สามารถบังคับอุปกรณ์อื่นออกจากระบบได้ "."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$message_error = "ไม่สามารถรีเซ็ต PIN ได้เพราะไม่สามารถบังคับอุปกรณ์อื่นออกจากระบบได้"."\n"."Data => ".json_encode($dataComing)."\n"."Payload => ".json_encode($payload);
		$lib->sendLineNotify($message_error);
		$arrayResult['RESPONSE_CODE'] = "WS1016";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS1016",
		":error_desc" => "รีเซ็ต Pin ไม่ได้ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไม่สามารถรีเซ็ต PIN ได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateResetPin->queryString."\n"."Param => ". json_encode([
		':member_no' => $payload["member_no"]
	]);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS1016";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	require_once('../../include/exit_footer.php');
	
}
?>