<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','password'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingChangePassword')){
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$conmysql->beginTransaction();
		$changePassword = $conmysql->prepare("UPDATE gcmemberaccount SET password = :password,temppass = null,account_status = '1',temppass_is_md5 = '0'
												WHERE member_no = :member_no");
		if($changePassword->execute([
			':password' => $password,
			':member_no' => $payload["member_no"]
		])){
			if($func->logoutAll($payload["id_token"],$payload["member_no"],'-9')){
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1012",
					":error_desc" => "ไม่สามารถเปลี่ยนรหัสผ่านได้เพราะไม่สามารถบังคับอุปกรณ์อื่นออกจากระบบได้ "."\n".json_encode($dataComing),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถเปลี่ยนรหัสผ่านได้เพราะไม่สามารถบังคับอุปกรณ์อื่นออกจากระบบได้"."\n"."Data => ".json_encode($dataComing)."\n"."Payload".json_encode($payload);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1012";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$conmysql->rollback();
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1012",
				":error_desc" => "ไม่สามารถเปลี่ยนรหัสผ่านได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถเปลี่ยนรหัสผ่านได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$changePassword->queryString."\n"."Param => ". json_encode([
				':password' => $password,
				':member_no' => $payload["member_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1012";
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