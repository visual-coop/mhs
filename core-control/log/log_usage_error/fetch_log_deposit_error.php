<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepositerror')){
		$arrayGroup = array();
		$fetchLogDepositError = $conmysql->prepare("SELECT  tb.id_deptransbankerr,
															tb.member_no,
															tb.transaction_date,
															tb.sigma_key,
															tb.amt_transfer,
															tb.response_code,
															tb.response_message,
															login.device_name,
															login.channel
													FROM logdepttransbankerror tb
													INNER JOIN gcuserlogin login
													ON login.id_userlogin = tb.id_userlogin ORDER BY  tb.transaction_date DESC");
		$fetchLogDepositError->execute();
		while($rowLogDepositError = $fetchLogDepositError->fetch(PDO::FETCH_ASSOC)){
			$arrLogDepositError = array();
			$arrLogDepositError["ID_DPTRANSBANKERR"] = $rowLogDepositError["id_deptransbankerr"];
			$arrLogDepositError["MEMBER_NO"] = $rowLogDepositError["member_no"];
			$arrLogDepositError["CHANNEL"] = $rowLogDepositError["channel"];
			$arrLogDepositError["ATTEMPT_BIND_DATE"] =  $lib->convertdate($rowLogDepositError["transaction_date"],'d m Y',true); 
			$arrLogDepositError["DEVICE_NAME"] = $rowLogDepositError["device_name"];
			$arrLogDepositError["AMT_TRANSFER"] = $rowLogDepositError["amt_transfer"];
			$arrLogDepositError["AMT_TRANSFER_FORMAT"] =number_format($rowLogDepositError["amt_transfer"],2);
			
			$arrLogDepositError["SIGMA_KEY"] = $rowLogDepositError["sigma_key"];
			$arrLogDepositError["RESPONSE_CODE"] = $rowLogDepositError["response_code"];
			$arrLogDepositError["RESPONSE_MESSAGE"] = $rowLogDepositError["response_message"];
			
	
			
			$arrayGroup[] = $arrLogDepositError;
		}
		$arrayResult["LOG_DEPOSIT_ERROR_DATA"] = $arrayGroup;
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