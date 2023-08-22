<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrpDeptAll = array();
		$arrGrpAllDept = array();
		$arrGrpAllLoan = array();
		$arrGrpLoanAll = array();
		$getSumAllDept = $conoracle->prepare("SELECT dt.depttype_desc,dp.deptaccount_no,dp.deptaccount_name,dp.prncbal as BALANCE
											FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
											WHERE dp.member_no = :member_no and dp.deptclose_status <> 1 ORDER BY dp.deptaccount_no ASC ");
		$getSumAllDept->execute([':member_no' => $member_no]);
		while($rowSumAllDept = $getSumAllDept->fetch(PDO::FETCH_ASSOC)){
			$arraysumDept = array();
			$arrGrpDept = array();
			$arrGrpAllDept["SUM_ALL_ACC"] += $rowSumAllDept["BALANCE"];
			$arrGrpAllDept["COUNT_ACC"]++;
			$arraysumDept["BALANCE"] = $rowSumAllDept["BALANCE"];
			$arraysumDept["ACCOUNT_NAME"] = preg_replace('/\"/','',TRIM($rowSumAllDept["DEPTACCOUNT_NAME"]));
			$arraysumDept["SOURCE_NO"] = $lib->formataccount($rowSumAllDept["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arraysumDept["TYPE_DESC"] = $rowSumAllDept["DEPTTYPE_DESC"];
			$arrGrpDept['TYPE_ACCOUNT'] = $rowSumAllDept["DEPTTYPE_DESC"];
			if(array_search($rowSumAllDept["DEPTTYPE_DESC"],array_column($arrGrpDeptAll,'TYPE_ACCOUNT')) === False){
				($arrGrpDept['ACCOUNT'])[] = $arraysumDept;
				$arrGrpDept['SUM_BAL_IN_TYPE'] += $rowSumAllDept["BALANCE"];
				$arrGrpDeptAll[] = $arrGrpDept;
			}else{
				($arrGrpDeptAll[array_search($rowSumAllDept["DEPTTYPE_DESC"],array_column($arrGrpDeptAll,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arraysumDept;
				($arrGrpDeptAll[array_search($rowSumAllDept["DEPTTYPE_DESC"],array_column($arrGrpDeptAll,'TYPE_ACCOUNT'))])["SUM_BAL_IN_TYPE"] += $rowSumAllDept["BALANCE"];
			}
		}
		$getSumAllLoan = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as BALANCE
															FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
															WHERE ln.member_no = :member_no and ln.contract_status > 0");
		$getSumAllLoan->execute([':member_no' => $member_no]);
		while($rowSumAllLoan = $getSumAllLoan->fetch(PDO::FETCH_ASSOC)){
			$arraysumLoan = array();
			$arrGrpLoan = array();
			$arrGrpAllLoan["SUM_ALL_ACC"] += $rowSumAllLoan["BALANCE"];
			$arrGrpAllLoan["COUNT_ACC"]++;
			$arraysumLoan["BALANCE"] = $rowSumAllLoan["BALANCE"];
			$arraysumLoan["SOURCE_NO"] = preg_replace('/\//','',$rowSumAllLoan["LOANCONTRACT_NO"]);
			$arraysumLoan["TYPE_ACCOUNT"] = $rowSumAllLoan["LOAN_TYPE"];
			$arrGrpLoan['TYPE_ACCOUNT'] = $rowSumAllLoan["LOAN_TYPE"];
			if(array_search($rowSumAllLoan["LOAN_TYPE"],array_column($arrGrpLoanAll,'TYPE_ACCOUNT')) === False){
				($arrGrpLoan['ACCOUNT'])[] = $arraysumLoan;
				$arrGrpLoan['SUM_BAL_IN_TYPE'] += $rowSumAllLoan["BALANCE"];
				$arrGrpLoanAll[] = $arrGrpLoan;
			}else{
				($arrGrpLoanAll[array_search($rowSumAllLoan["LOAN_TYPE"],array_column($arrGrpLoanAll,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arraysumLoan;
				($arrGrpLoanAll[array_search($rowSumAllLoan["LOAN_TYPE"],array_column($arrGrpLoanAll,'TYPE_ACCOUNT'))])["SUM_BAL_IN_TYPE"] += $rowSumAllLoan["BALANCE"];
			}
		}
		$getSumAllLoanShare = $conoracle->prepare("SELECT (sharestk_amt * 10) as SUM_SHARE FROM shsharemaster WHERE member_no = :member_no");
		$getSumAllLoanShare->execute([':member_no' => $member_no]);
		$rowSumAllShareBal = $getSumAllLoanShare->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SHARE_BALANCE'] = $rowSumAllShareBal["SUM_SHARE"];
		$arrGrpAllDept['INFO'] = $arrGrpDeptAll;
		$arrGrpAllLoan["INFO"] = $arrGrpLoanAll;
		$arrayResult['COLLECT_DEPT'] = $arrGrpAllDept;
		$arrayResult['COLLECT_LOAN'] = $arrGrpAllLoan;
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