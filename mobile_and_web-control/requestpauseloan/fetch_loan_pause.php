<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SuspendingDebt')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayLoanPause = array();
		$getLoanPause = $conoracle->prepare("SELECT A.MORATORIUM_DOCNO, A.LOANCONTRACT_NO,
															(CASE WHEN A.REQUEST_DATE <= TO_DATE('27042020','DDMMYYYY') THEN 1 ELSE 2 END) AS REGIS_ROUND , 
															A.REQUEST_STATUS
															FROM LNREQMORATORIUM A, LNCONTMASTER B 
															WHERE A.LOANCONTRACT_NO = B.LOANCONTRACT_NO AND
															B.CONTRACT_STATUS = 1 AND
															A.REQUEST_STATUS IN (1, -1) AND
															A.COOP_ID = '000000' AND A.MEMBER_NO = :member_no");
		$getLoanPause->execute([':member_no' => $member_no]);
		while($rowLoanPuase = $getLoanPause->fetch(PDO::FETCH_ASSOC)){
			$arrayLoan = array();
			$getInfoLoan = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.principal_balance as LOAN_BALANCE,
											ln.loanapprove_amt as APPROVE_AMT,ln.period_payment,ln.period_payamt as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.loancontract_no = :contract_no");
			$getInfoLoan->execute([':contract_no' => $rowLoanPuase["LOANCONTRACT_NO"]]);
			$rowInfoLoan = $getInfoLoan->fetch(PDO::FETCH_ASSOC);
			$arrayLoan["LOAN_TYPE"] = $rowInfoLoan["LOAN_TYPE"];
			$arrayLoan["LOAN_BALANCE"] = $rowInfoLoan["LOAN_BALANCE"];
			$arrayLoan["APPROVE_AMT"] = $rowInfoLoan["APPROVE_AMT"];
			$arrayLoan["PERIOD_PAYMENT"] = $rowInfoLoan["PERIOD_PAYMENT"];
			$arrayLoan["PERIOD"] = $rowInfoLoan["PERIOD"];
			$arrayLoan["LAST_PERIOD"] = $rowInfoLoan["LAST_PERIOD"];
			$arrayLoan["DOCNO"] = $rowLoanPuase["MORATORIUM_DOCNO"];
			$contract_no = preg_replace('/\//','',$rowLoanPuase["LOANCONTRACT_NO"]);
			if(mb_stripos($contract_no,'.') === FALSE){
				$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
				if(mb_strlen($contract_no) == 10){
					$arrayLoan["LOANCONTRACT_NO"] = $loan_format;
				}else if(mb_strlen($contract_no) == 11){
					$arrayLoan["LOANCONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
				}
			}else{
				$arrayLoan["LOANCONTRACT_NO"] = $contract_no;
			}
			if($rowLoanPuase["REQUEST_STATUS"] == '1'){
				$arrayLoan["STATUS_LOAN"] = "อยู่ในสถานะ พักชำระเงินต้น";
			}else{
				$arrayLoan["STATUS_LOAN"] = "อยู่ในสถานะยกเลิกพักชำระเงินต้น มีผลในเดือน ".($rowLoanPuase["REGIS_ROUND"] == '1' ? "สิงหาคม" : "กันยายน");
			}
			$arrayLoan["REQUEST_STATUS"] = $rowLoanPuase["REQUEST_STATUS"];
			$arrAllAccount[] = $arrayLoan;
		}
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