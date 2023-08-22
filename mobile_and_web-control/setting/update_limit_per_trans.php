<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','limit_amt','limit_name'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingLimitTrans')){
		$updateLimitTrans = $conmysql->prepare("UPDATE gcmemberaccount SET ".preg_replace('/[^a-zA-Z_]/','',$dataComing['limit_name'])." = :limit_amt 
												WHERE member_no = :member_no");
		if($updateLimitTrans->execute([
			':limit_amt' => $dataComing["limit_amt"],
			':member_no' => $payload["member_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1026",
				":error_desc" => "ไม่สามารถอัพเดทวงเงินในการทำรายการได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถอัพเดทวงเงินในการทำรายการได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateLimitTrans->queryString."\n"."Param => ". json_encode([
				':limit_amt' => $dataComing["limit_amt"],
				':member_no' => $payload["member_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1026";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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