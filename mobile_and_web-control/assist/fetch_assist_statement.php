<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','asscontract_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistStatement')){
		$limit = $func->getConstant('limit_stmassist');
		$arrayResult['LIMIT_DURATION'] = $limit;
		if($lib->checkCompleteArgument(["date_start"],$dataComing)){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		if($lib->checkCompleteArgument(["date_end"],$dataComing)){
			$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
		}else{
			$date_now = date('Y-m-d');
		}
		$fetchAccountReceive = $conoracle->prepare("SELECT cb.BANK_DESC,cbb.BRANCH_NAME,acm.EXPENSE_ACCID,cb.ACCOUNT_FORMAT FROM asscontmaster acm 
													LEFT JOIN cmucfbank cb ON acm.EXPENSE_BANK = cb.BANK_CODE
													LEFT JOIN cmucfbankbranch cbb ON acm.EXPENSE_BANK = cbb.BANK_CODE and acm.EXPENSE_BRANCH = cbb.BRANCH_ID
													WHERE acm.asscontract_no = :asscontract_no and acm.asscont_status = 1");
		$fetchAccountReceive->execute([
			':asscontract_no' => $dataComing["asscontract_no"]
		]);
		$rowAccountRCV = $fetchAccountReceive->fetch(PDO::FETCH_ASSOC);
		$accAssRcv = $lib->formataccount($rowAccountRCV["EXPENSE_ACCID"],$rowAccountRCV["ACCOUNT_FORMAT"]);
		$fetchAssStatement = $conoracle->prepare("SELECT atc.SIGN_FLAG,atc.ITEM_DESC,astm.SLIP_DATE,astm.PAY_BALANCE
													FROM asscontmaster asm LEFT JOIN asscontstatement astm 
													ON asm.ASSCONTRACT_NO = astm.ASSCONTRACT_NO LEFT JOIN assucfassitemcode atc
													ON astm.ITEM_CODE = atc.ITEM_CODE LEFT JOIN CMUCFMONEYTYPE cmt ON astm.MONEYTYPE_CODE = cmt.MONEYTYPE_CODE 
													where asm.asscontract_no = :asscontract_no and asm.asscont_status = 1 and astm.SLIP_DATE
													BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ORDER BY astm.SEQ_NO DESC");
		$fetchAssStatement->execute([
			':asscontract_no' => $dataComing["asscontract_no"],
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		$arrGroupAssStm = array();
		$arrGroupAssStm["ACCOUNT_RECEIVE"] = $accAssRcv;
		$arrGroupAssStm["BANK_NAME"] = $rowAccountRCV["BANK_DESC"];
		$arrGroupAssStm["BANK_BRANCH_NAME"] = $rowAccountRCV["BRANCH_NAME"];
		while($rowAssStm = $fetchAssStatement->fetch(PDO::FETCH_ASSOC)){
			$arrAssStm = array();
			$arrAssStm["ITEM_DESC"] = $rowAssStm["ITEM_DESC"];
			$arrAssStm["OPERATE_DATE"] = $lib->convertdate($rowAssStm["SLIP_DATE"],'D m Y');
			$arrAssStm["RECEIVE_BALANCE"] = number_format($rowAssStm["PAY_BALANCE"],2);
			$arrAssStm["SIGN_FLAG"] = $rowAssStm["SIGN_FLAG"];
			($arrGroupAssStm["STATEMENT"])[] = $arrAssStm;
		}
		$arrayResult["ASSIST_STM"] = $arrGroupAssStm;
		$arrayResult["RESULT"] = TRUE;
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