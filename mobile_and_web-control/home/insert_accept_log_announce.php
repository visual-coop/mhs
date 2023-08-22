<?php
$skip_autoload = true;
require_once('../autoload.php');

if($lib->checkCompleteArgument(['id_announce'],$dataComing)){
	if($dataComing["priority"] == 'ask'){
		$insertResponseAnn = $conmysql->prepare("INSERT INTO logacceptannounce(member_no,id_announce,status_accept,id_userlogin)
																		VALUES(:member_no,:id_announce,:status_accept,:id_userlogin)");
		if($insertResponseAnn->execute([
			':member_no' => $payload["member_no"],
			':id_announce' => $dataComing["id_announce"],
			':status_accept' => $dataComing["status_accept"],
			':id_userlogin' => $payload["id_userlogin"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1006",
				":error_desc" => "ยืนยันประกาศไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ยืนยันประกาศไม่ได้เพราะ Insert ลง logacceptannounce ไม่ได้"."\n"."Query => ".$insertResponseAnn->queryString."\n"."Param => ". json_encode([
				':member_no' => $payload["member_no"],
				':id_announce' => $dataComing["id_announce"],
				':id_userlogin' => $payload["id_userlogin"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$insertResponseAnn = $conmysql->prepare("INSERT INTO logacceptannounce(member_no,id_announce,id_userlogin)
																		VALUES(:member_no,:id_announce,:id_userlogin)");
		if($insertResponseAnn->execute([
			':member_no' => $payload["member_no"],
			':id_announce' => $dataComing["id_announce"],
			':id_userlogin' => $payload["id_userlogin"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1006",
				":error_desc" => "ยืนยันประกาศไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ยืนยันประกาศไม่ได้เพราะ Insert ลง logacceptannounce ไม่ได้"."\n"."Query => ".$insertResponseAnn->queryString."\n"."Param => ". json_encode([
				':member_no' => $payload["member_no"],
				':id_announce' => $dataComing["id_announce"],
				':id_userlogin' => $payload["id_userlogin"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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