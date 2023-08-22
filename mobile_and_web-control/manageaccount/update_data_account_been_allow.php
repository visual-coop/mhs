<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','allow_status'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		if($dataComing["allow_status"] == '-9'){
			$checkBindBank = $conmysql->prepare("SELECT id_bindaccount FROM gcbindaccount WHERE member_no = :member_no and deptaccount_no_coop = :dept_acc and bindaccount_status = '1'");
			$checkBindBank->execute([
				':member_no' => $dataComing["member_no"],
				':dept_acc' => $dataComing["deptaccount_no"]
			]);
			if($checkBindBank->rowCount() > 0){
				$arrayResult['RESPONSE_CODE'] = "WS0052";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}
		$updateAccountBeenAllow = $conmysql->prepare("UPDATE gcuserallowacctransaction SET is_use = :allow_status WHERE deptaccount_no = :deptaccount_no AND is_use <> '-9'");
		if($updateAccountBeenAllow->execute([
			':allow_status' => $dataComing["allow_status"],
			':deptaccount_no' => $dataComing["deptaccount_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1024",
				":error_desc" => "ไม่สามารถเปลี่ยนสถานะบัญชีได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถเปลี่ยนสถานะบัญชีได้เพราะ Update ลง gcuserallowacctransaction ไม่ได้"."\n"."Query => ".$updateAccountBeenAllow->queryString."\n"."Param => ". json_encode([
				':allow_status' => $dataComing["allow_status"],
				':deptaccount_no' => $dataComing["deptaccount_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1024";
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