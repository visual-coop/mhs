<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logbindaccounterror')){
		$arrayGroup = array();
		$fetchLogBindAccountError = $conmysql->prepare("SELECT bind.id_logbindaccount,
															bind.member_no,
															bind.bind_status,
															bind.attempt_bind_date,
															bind.response_code,	
															bind.response_message,
															bind.data_bind_error,
															bind.query_error,
															bind.query_flag,
															bind.coop_account_no,
															login.device_name,
															login.channel
													FROM logbindaccount bind
													INNER JOIN gcuserlogin login
													ON login.id_userlogin = bind.id_userlogin
													WHERE bind.bind_status !=1 ORDER BY bind.attempt_bind_date DESC");
		$fetchLogBindAccountError->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogBindAccountError = $fetchLogBindAccountError->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogBindAccountError = array();
			$arrGroupLogBindAccountError["ID_LOGBINDACCOUNT"] = $rowLogBindAccountError["id_logbindaccount"];
			$arrGroupLogBindAccountError["MEMBER_NO"] = $rowLogBindAccountError["member_no"];
			$arrGroupLogBindAccountError["CHANNEL"] = $rowLogBindAccountError["channel"];
			$arrGroupLogBindAccountError["BIND_STATUS"] = $rowLogBindAccountError["bind_status"];
			$arrGroupLogBindAccountError["RESPONSE_CODE"] = $rowLogBindAccountError["response_code"];
			$arrGroupLogBindAccountError["DEVICE_NAME"] = $rowLogBindAccountError["device_name"];
			$arrGroupLogBindAccountError["ATTEMPT_BIND_DATE"] =  $lib->convertdate($rowLogBindAccountError["attempt_bind_date"],'d m Y',true); 
			$arrGroupLogBindAccountError["RESPONSE_MESSAGE"] = $rowLogBindAccountError["response_message"];
			$arrGroupLogBindAccountError["COOP_ACCOUNT_NO_FORMAT"]= $lib->formataccount($rowLogBindAccountError["coop_account_no"],$formatDept);
			$arrGroupLogBindAccountError["COOP_ACCOUNT_NO"] = $rowLogBindAccountError["coop_account_no"];
			$arrGroupLogBindAccountError["DATA_BIND_ERROR"] = $rowLogBindAccountError["data_bind_error"];
			$arrGroupLogBindAccountError["QUERY_ERROR"] = $rowLogBindAccountError["query_error"];
			$arrGroupLogBindAccountError["QUERY_FLAG"] = $rowLogBindAccountError["query_flag"];
			
			$arrayGroup[] = $arrGroupLogBindAccountError;
		}
		$arrayResult["BIND_ACCOUNT_LOG"] = $arrayGroup;
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