<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayConst = array();
		$arrayDataGrp = array();
		$getConstChangeInfo = $conmysql->prepare("SELECT const_code,is_change FROM gcconstantchangeinfo");
		$getConstChangeInfo->execute();
		while($rowConst = $getConstChangeInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayConst[$rowConst["const_code"]] = $rowConst["is_change"];
		}
		if($arrayConst["email"] == '1'){
			$getEmail = $conmysql->prepare("SELECT email FROM gcmemberaccount WHERE member_no = :member_no");
			$getEmail->execute([':member_no' => $payload["member_no"]]);
			$rowEmail = $getEmail->fetch(PDO::FETCH_ASSOC);
			$arrayDataGrp["EMAIL"] = $rowEmail["email"];
		}
		if($arrayConst["tel"] == '1'){
			$getPhone = $conmysql->prepare("SELECT phone_number FROM gcmemberaccount WHERE member_no = :member_no");
			$getPhone->execute([':member_no' => $payload["member_no"]]);
			$rowPhone = $getPhone->fetch(PDO::FETCH_ASSOC);
			$arrayDataGrp["PHONE_NUMBER"] = $rowPhone["phone_number"];
		}
		$arrayResult['DATA'] = $arrayDataGrp;
		$arrayResult['EMAIL_CAN_CHANGE'] = $arrayConst["email"] == '1' ? TRUE : FALSE;
		$arrayResult['ADDRESS_CAN_CHANGE'] = $arrayConst["address"] == '1' ? TRUE : FALSE;
		$arrayResult['TEL_CAN_CHANGE'] = $arrayConst["tel"] == '1' ? TRUE : FALSE;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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