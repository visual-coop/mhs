<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','reqloan_doc'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestTrack')){
		$getIsCancel = $conmysql->prepare("SELECT req_status FROM gcreqloan WHERE reqloan_doc  = :reqloan_doc");
		$getIsCancel->execute([':reqloan_doc' => $dataComing["reqloan_doc"]]);
		$rowCancel = $getIsCancel->fetch(PDO::FETCH_ASSOC);
		if($rowCancel["req_status"] == '8'){
			$cancelReq = $conmysql->prepare("UPDATE gcreqloan SET req_status = '9' WHERE reqloan_doc  = :reqloan_doc");
			if($cancelReq->execute([':reqloan_doc' => $dataComing["reqloan_doc"]])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1037",
					":error_desc" => "ยกเลิกใบคำขอกู้ไม่ได้เพราะไม่สามารถ Update ลง gcreqloan ได้"."\n"."Query => ".$cancelReq->queryString."\n"."Param => ". json_encode([':reqloan_doc' => $dataComing["reqloan_doc"]]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ยกเลิกใบคำขอกู้ไม่ได้เพราะไม่สามารถ Update ลง gcreqloan ได้"."\n"."Query => ".$cancelReq->queryString."\n"."Param => ". json_encode([':reqloan_doc' => $dataComing["reqloan_doc"]]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1037";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0083";
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