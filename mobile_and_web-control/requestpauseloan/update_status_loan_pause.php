<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loan_pause'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SuspendingDebt')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$conoracle->beginTransaction();
		foreach($dataComing["loan_pause"] as $doc_no){
			$updateLoanPuase = $conoracle->prepare("UPDATE LNREQMORATORIUM SET REQUEST_STATUS = -1, CANCEL_ID = :cancel_id, CANCEL_DATE = SYSDATE
																WHERE MORATORIUM_DOCNO = :docno");
			if($updateLoanPuase->execute([
				':cancel_id' => $member_no,
				':docno' => $doc_no
			])){
			}else{
				$conoracle->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1030",
					":error_desc" => "เปลี่ยนแปลงสถานะการพักชำระหนี้ไม่ได้ "."\n".json_encode($dataComing),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "เปลี่ยนแปลงสถานะการพักชำระหนี้ไม่ได้เพราะ Update ลง LNREQMORATORIUM ไม่ได้"."\n"."Query => ".$updateLoanPuase->queryString."\n"."Param => ". json_encode([
					':cancel_id' => $member_no,
					':docno' => $doc_no
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1030";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}
		$conoracle->commit();
		$arrayResult['LOAN_PAUSE'] = $arrAllAccount;
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