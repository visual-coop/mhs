<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logwithdrawerror')){
		$arrayGroup = array();
		$fetchLogWithdrawError = $conmysql->prepare("SELECT wd.id_withdrawtransbankerr,
														wd.member_no,
														wd.transaction_date,
														wd.amt_transfer,
														wd.penalty_amt,
														wd.penalty_amt,
														wd.fee_amt,
														wd.fee_amt,
														wd.deptaccount_no,
														wd.response_code,
														wd.response_message,
														login.channel,
														login.device_name
													FROM logwithdrawtransbankerror wd
													INNER JOIN gcuserlogin login
													ON login.id_userlogin = wd.id_userlogin
													ORDER BY 	wd.transaction_date DESC ");
		$fetchLogWithdrawError->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogWithdrawError = $fetchLogWithdrawError->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogWithdrawError = array();
			$arrGroupLogWithdrawError["ID_WITHDRAW_TRAN_BANK_ERROR"] = $rowLogWithdrawError["id_withdrawtransbankerr"];
			$arrGroupLogWithdrawError["MEMBER_NO"] = $rowLogWithdrawError["member_no"];
			$arrGroupLogWithdrawError["CHANNEL"] = $rowLogWithdrawError["channel"];
			$arrGroupLogWithdrawError["ATTEMPT_BIND_DATE"] =  $lib->convertdate($rowLogWithdrawError["transaction_date"],'d m Y',true); 
			$arrGroupLogWithdrawError["DEVICE_NAME"] = $rowLogWithdrawError["device_name"];
			$arrGroupLogWithdrawError["AMT_TRANSFER"] = $rowLogWithdrawError["amt_transfer"];
			$arrGroupLogWithdrawError["AMT_TRANSFER_FORMAT"] = number_format($rowLogWithdrawError["amt_transfer"],2);
			$arrGroupLogWithdrawError["PENALTY_AMT"] = $rowLogWithdrawError["penalty_amt"];
			$arrGroupLogWithdrawError["PENALTY_AMT_FORMAT"] =number_format( $rowLogWithdrawError["penalty_amt"],2);
			$arrGroupLogWithdrawError["FEE_AMT"] = $rowLogWithdrawError["fee_amt"];
			$arrGroupLogWithdrawError["FEE_AMT_FORMAT"] =  number_format($rowLogWithdrawError["fee_amt"],2);
			$arrGroupLogWithdrawError["DEPTACCOUNT_NO"] = $rowLogWithdrawError["deptaccount_no"];
			$arrGroupLogWithdrawError["DEPTACCOUNT_NO_FORMAT"]= $lib->formataccount($rowLogWithdrawError["deptaccount_no"],$formatDept);
			$arrGroupLogWithdrawError["RESPONSE_CODE"] = $rowLogWithdrawError["response_code"];
			$arrGroupLogWithdrawError["RESPONSE_MESSAGE"] = $rowLogWithdrawError["response_message"];
			
	
			
			$arrayGroup[] = $arrGroupLogWithdrawError;
		}
		$arrayResult["LOG_WITHDRAW_ERROR_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>