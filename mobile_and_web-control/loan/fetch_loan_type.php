<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAllLoan = array();
		$getSumAllContract = $conoracle->prepare("SELECT SUM(principal_balance) as SUM_LOANBALANCE FROM lncontmaster WHERE member_no = :member_no
													and contract_status > 0 and contract_status <> 8");
		$getSumAllContract->execute([':member_no' => $member_no]);
		$rowSumloanbalance = $getSumAllContract->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SUM_LOANBALANCE'] = number_format($rowSumloanbalance["SUM_LOANBALANCE"],2);
		$getContract = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
											ln.loanapprove_amt as APPROVE_AMT,ln.startcont_date,ln.period_payment,ln.period_payamt as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD,
											(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8");
		$getContract->execute([':member_no' => $member_no]);
		while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
			$arrGroupContract = array();
			$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
			$arrContract = array();
			$arrContract["CONTRACT_NO"] = $contract_no;
			$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
			$arrContract["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
			$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'y-n-d');
			$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'D m Y');
			$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowContract["STARTCONT_DATE"],'D m Y');
			$arrContract["PERIOD_PAYMENT"] = number_format($rowContract["PERIOD_PAYMENT"],2);
			$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
			$arrGroupContract['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
			if(array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN')) === False){
				($arrGroupContract['CONTRACT'])[] = $arrContract;
				$arrAllLoan[] = $arrGroupContract;
			}else{
				($arrAllLoan[array_search($rowContract["LOAN_TYPE"],array_column($arrAllLoan,'TYPE_LOAN'))]["CONTRACT"])[] = $arrContract;
			}
		}
		$arrayResult['DETAIL_LOAN'] = $arrAllLoan;
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