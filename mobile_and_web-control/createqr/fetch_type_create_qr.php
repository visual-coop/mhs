<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GenerateQR')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpTrans = array();
		$arrGrpAcc = array();
		$arrGrpCont = array();
		$getTypeTransQR = $conmysql->prepare("SELECT trans_code_qr,trans_desc_qr,operation_desc_th,operation_desc_en FROM gcconttypetransqrcode WHERE is_use = '1'");
		$getTypeTransQR->execute();
		while($rowTypeQR = $getTypeTransQR->fetch(PDO::FETCH_ASSOC)){
			$arrTypeQR = array();
			$arrTypeQR["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
			$arrTypeQR["TRANS_DESC"] = $rowTypeQR["trans_desc_qr"];
			$arrTypeQR["OPERATE_DESC"] = $rowTypeQR["operation_desc_".$lang_locale];
			$arrayGrpTrans[] = $arrTypeQR;
			if($rowTypeQR["trans_code_qr"] == '01'){
				$formatDept = $func->getConstant('dep_format');
				$hiddenFormat = $func->getConstant('hidden_dep');
				$checkCanReceive = $conmysql->prepare("SELECT dept_type_code FROM gcconstantaccountdept WHERE allow_deposit_outside = '1'");
				$checkCanReceive->execute();
				$arrDepttypeAllow = array();
				while($rowCanReceive = $checkCanReceive->fetch(PDO::FETCH_ASSOC)){
					$arrDepttypeAllow[] = $rowCanReceive["dept_type_code"];
				}
				$getAccountinTrans = $conoracle->prepare("SELECT DEPTACCOUNT_NO,DEPTACCOUNT_NAME,PRNCBAL FROM dpdeptmaster 
															WHERE member_no = :member_no and deptclose_status <> 1 and depttype_code IN('".implode("','",$arrDepttypeAllow)."')");
				$getAccountinTrans->execute([':member_no' => $member_no]);
				while($rowAccTrans = $getAccountinTrans->fetch(PDO::FETCH_ASSOC)){
					$arrAccTrans = array();
					$arrAccTrans["ACCOUNT_NO"] = $lib->formataccount($rowAccTrans["DEPTACCOUNT_NO"],$formatDept);
					$arrAccTrans["ACCOUNT_NO_HIDE"] = $lib->formataccount_hidden($arrAccTrans["ACCOUNT_NO"],$hiddenFormat);
					$arrAccTrans["ACCOUNT_NAME"] = TRIM($rowAccTrans["DEPTACCOUNT_NAME"]);
					$arrAccTrans["PRIN_BAL"] = $rowAccTrans["PRNCBAL"];
					$arrAccTrans["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
					$arrGrpAcc[] = $arrAccTrans;
				}
			}else if($rowTypeQR["trans_code_qr"] == '02'){
				$checkCanGen = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_qrpayment = '1'");
				$checkCanGen->execute();
				$arrLoantypeAllow = array();
				while($rowCanGen = $checkCanGen->fetch(PDO::FETCH_ASSOC)){
					$arrLoantypeAllow[] = $rowCanGen["loantype_code"];
				}
				$getContract = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
											ln.loanapprove_amt as APPROVE_AMT,ln.startcont_date,ln.period_payment,ln.period_payamt as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD,ln.LOANTYPE_CODE,
											(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8 and ln.LOANTYPE_CODE IN('".implode("','",$arrLoantypeAllow)."')");
				$getContract->execute([':member_no' => $member_no]);
				while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
					$arrContract = array();
					$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
					$interest = $cal_loan->calculateInterest($contract_no);
					$arrContract["ACCOUNT_NO"] = $contract_no;
					$arrContract["ACCOUNT_NO_HIDE"] = $contract_no;
					$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
					$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
					$arrContract['LOAN_TYPE'] = $rowContract["LOAN_TYPE"];
					$arrContract['INT_BALANCE'] = number_format($interest,2);
					$arrContract["TRANS_CODE"] = $rowTypeQR["trans_code_qr"];
					$arrGrpAcc[] = $arrContract;
				}
			}
		}
		$arrayResult["TYPE_TRANS"] = $arrayGrpTrans;
		$arrayResult["CHOOSE_ACCOUNT"] = $arrGrpAcc;
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
