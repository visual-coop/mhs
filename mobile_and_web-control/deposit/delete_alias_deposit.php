<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','type_alias','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		if($dataComing["type_alias"] == 'alias_img'){
			$DeleteAliasDept = $conmysql->prepare("UPDATE gcdeptalias SET path_alias_img = null WHERE deptaccount_no = :deptaccount_no");
		}else if($dataComing["type_alias"] == 'alias_name'){
			$DeleteAliasDept = $conmysql->prepare("UPDATE gcdeptalias SET alias_name = null WHERE deptaccount_no = :deptaccount_no");
		}else{
			$DeleteAliasDept = $conmysql->prepare("UPDATE gcdeptalias SET path_alias_img = null,alias_name = null WHERE deptaccount_no = :deptaccount_no");
		}
		if($DeleteAliasDept->execute([
			':deptaccount_no' => $account_no,
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1028",
				":error_desc" => "อัพเดทลงฐานข้อมูลไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ลบชื่อเล่นบัญชีไม่ได้เพราะ Update ลงตาราง gcdeptalias ไม่ได้ "."\n"."Query => ".$DeleteAliasDept->queryString."\n"."Param => ".json_encode([
				':deptaccount_no' => $account_no
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1028";
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