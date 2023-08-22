<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logtranferinsidecooperror')){
		$arrayGroup = array();
		$fetchTranfertError = $conmysql->prepare("SELECT
																				tran.id_transferinsidecoop,
																				tran.member_no,
																				tran.transaction_date,
																				tran.deptaccount_no,
																				tran.amt_transfer,
																				tran.type_request,
																				tran.transfer_flag,
																				tran.destination,
																				tran.response_code,
																				tran.response_message,
																				login.device_name,
																				login.channel
																			FROM
																				logtransferinsidecoop tran
																			INNER JOIN gcuserlogin login ON
																				login.id_userlogin = tran.id_userlogin
																				ORDER BY   tran.transaction_date DESC
																			 ");
		$fetchTranfertError->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogTranferError = $fetchTranfertError->fetch(PDO::FETCH_ASSOC)){
			$arrLogTranfertError = array();
			$arrLogTranfertError["ID_TRANFER"] = $rowLogTranferError["id_transferinsidecoop"];
			$arrLogTranfertError["MEMBER_NO"] = $rowLogTranferError["member_no"];
			$arrLogTranfertError["CHANNEL"] = $rowLogTranferError["channel"];
			$arrLogTranfertError["TRANSACTION_DATE"] =  $lib->convertdate($rowLogTranferError["transaction_date"],'d m Y',true); 
			$arrLogTranfertError["DEVICE_NAME"] = $rowLogTranferError["device_name"];
			$arrLogTranfertError["AMT_TRANSFER"] = $rowLogTranferError["amt_transfer"];
			$arrLogTranfertError["AMT_TRANSFER_FORMAT"] = number_format($rowLogTranferError["amt_transfer"],2);
			$arrLogTranfertError["TYPE_REQUEST"] = $rowLogTranferError["type_request"];
		
			$arrLogTranfertError["TRANSFER_FLAG"] = $rowLogTranferError["transfer_flag"];
			$arrLogTranfertError["DESTINATION"] = $rowLogTranferError["destination"];
			$arrLogTranfertError["DESTINATION_NO_FORMAT"]= $lib->formataccount($rowLogTranferError["destination"],$formatDept);
			$arrLogTranfertError["DEPTACCOUNT_NO"] = $rowLogTranferError["deptaccount_no"];
			$arrLogTranfertError["DEPTACCOUNT_NO_FORMAT"]= $lib->formataccount($rowLogTranferError["deptaccount_no"],$formatDept);
			$arrLogTranfertError["RESPONSE_CODE"] = $rowLogTranferError["response_code"];
			$arrLogTranfertError["RESPONSE_MESSAGE"] = $rowLogTranferError["response_message"];
			
			$arrayGroup[] = $arrLogTranfertError;
		}
		$arrayResult["LOG_TRANFER_ERROR_DATA"] = $arrayGroup;
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