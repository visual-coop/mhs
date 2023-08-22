<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['password'],$dataComing)){
	$getOldPassword = $conmysql->prepare("SELECT password,temppass,account_status,temppass_is_md5 FROM gcmemberaccount 
											WHERE member_no = :member_no");
	$getOldPassword->execute([':member_no' => $payload["member_no"]]);
	if($getOldPassword->rowCount() > 0){
		$rowAccount = $getOldPassword->fetch(PDO::FETCH_ASSOC);
		if($rowAccount['account_status'] == '-9'){
			if($rowAccount['temppass_is_md5'] == '1'){
				$validpassword = password_verify(md5($dataComing["password"]), $rowAccount['temppass']);
			}else{
				$validpassword = password_verify($dataComing["password"], $rowAccount['temppass']);
			}
		}else{
			$validpassword = password_verify($dataComing["password"], $rowAccount['password']);
		}
		if($validpassword){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0004";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0003";
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