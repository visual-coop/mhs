<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','email'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getConstInfo = $conmysql->prepare("SELECT save_tablecore FROM gcconstantchangeinfo WHERE const_code = 'email'");
		$getConstInfo->execute();
		$rowConst = $getConstInfo->fetch(PDO::FETCH_ASSOC);
		if($rowConst["save_tablecore"] == '1'){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$updateEmail = $conmysql->prepare("UPDATE gcmemberaccount SET email = :email WHERE member_no = :member_no");
			if($updateEmail->execute([
				':email' => $dataComing["email"],
				':member_no' => $payload["member_no"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1010",
					":error_desc" => "แก้ไขอีเมลไม่ได้เพราะ update ลงตาราง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateEmail->queryString."\n"."Param => ". json_encode([
						':email' => $dataComing["email"],
						':member_no' => $payload["member_no"]
					]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "แก้ไขอีเมลไม่ได้เพราะ update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateEmail->queryString."\n"."Param => ". json_encode([
					':email' => $dataComing["email"],
					':member_no' => $payload["member_no"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1010";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
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