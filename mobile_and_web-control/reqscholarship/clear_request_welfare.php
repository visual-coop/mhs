<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','childcard_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$conoracle->beginTransaction();
		$clearUploadListDet = $conoracle->prepare("UPDATE asnreqschshiponlinedet set upload_status = 8, filename = '' WHERE scholarship_year = 
																(EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :childcard_id");
		if($clearUploadListDet->execute([
			':member_no' => $member_no,
			':childcard_id' => $dataComing["childcard_id"]
		])){
			$clearUploadList = $conoracle->prepare("UPDATE asnreqschshiponline set request_status = 8, resetlog_date = sysdate 
															WHERE scholarship_year = (EXTRACT(year from sysdate) +543) 
															and member_no = :member_no and childcard_id = :childcard_id");
			if($clearUploadList->execute([
				':member_no' => $member_no,
				':childcard_id' => $dataComing["childcard_id"]
			])){
				$conoracle->commit();
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$conoracle->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1033",
					":error_desc" => "ไม่สามารถ update ลง asnreqschshiponline ได้ "."\n".$clearUploadList->queryString."\n".json_encode([
						':member_no' => $member_no,
						':childcard_id' => $dataComing["childcard_id"]
					]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถ update ลง asnreqschshiponline ได้ "."\n".$clearUploadList->queryString."\n".json_encode([
					':member_no' => $member_no,
					':childcard_id' => $dataComing["childcard_id"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1033";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(403);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conoracle->rollback();
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1033",
				":error_desc" => "ไม่สามารถ update ลง asnreqschshiponlinedet ได้ "."\n".$clearUploadListDet->queryString."\n".json_encode([
					':member_no' => $member_no,
					':childcard_id' => $dataComing["childcard_id"]
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถ update ลง asnreqschshiponlinedet ได้ "."\n".$clearUploadListDet->queryString."\n".json_encode([
				':member_no' => $member_no,
				':childcard_id' => $dataComing["childcard_id"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1033";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			echo json_encode($arrayResult);
			exit();
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