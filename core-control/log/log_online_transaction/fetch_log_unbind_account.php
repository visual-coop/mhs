<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logunbindaccount')){
		$arrayGroup = array();
		$fetchLogBindAccount = $conmysql->prepare("SELECT
																				unbin.id_logunbindaccount,
																				unbin.member_no,
																				unbin.id_userlogin,
																				unbin.unbind_status,
																				unbin.attempt_unbind_date,
																				unbin.response_code,
																				unbin.response_message,
																				unbin.id_bindaccount,
																				unbin.data_unbind_error,
																				unbin.query_error,
																				unbin.query_flag,
																				login.channel,
																				login.device_name
																			FROM
																				logunbindaccount unbin
																			INNER JOIN gcuserlogin login ON
																				unbin.id_userlogin = login.id_userlogin  
																			ORDER BY unbin.attempt_unbind_date DESC");
		$fetchLogBindAccount->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogBindAccount = $fetchLogBindAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogBindAccount = array();
			$fetchBinAccountCoopNo = $conmysql->prepare("SELECT deptaccount_no_coop,deptaccount_no_bank FROM gcbindaccount WHERE id_bindaccount = '$rowLogBindAccount[id_bindaccount]' ");
			$fetchBinAccountCoopNo -> execute();
			$coop_no=$fetchBinAccountCoopNo-> fetch(PDO::FETCH_ASSOC);
			
			$arrGroupLogBindAccount["ID_LOGUNBINDACCOUNT"] = $rowLogBindAccount["id_logunbindaccount"];
			$arrGroupLogBindAccount["MEMBER_NO"] = $rowLogBindAccount["member_no"];
			$arrGroupLogBindAccount["UNBIND_STATUS"] = $rowLogBindAccount["unbind_status"];
			$arrGroupLogBindAccount["RESPONSE_CODE"] = $rowLogBindAccount["response_code"];
			$arrGroupLogBindAccount["ATTEMPT_UNBIND_DATE"] =  $lib->convertdate($rowLogBindAccount["attempt_unbind_date"],'d m Y',true); 
			$arrGroupLogBindAccount["RESPONSE_MESSAGE"] = $rowLogBindAccount["response_message"];
			$arrGroupLogBindAccount["DEVICE_NAME"] = $rowLogBindAccount["device_name"];
			$arrGroupLogBindAccount["CHANNEL"] = $rowLogBindAccount["channel"];
			$arrGroupLogBindAccount["ID_BIND_ACCOUNT"] = $rowLogBindAccount["id_bindaccount"];
			$arrGroupLogBindAccount["COOP_ACCOUNT_NO"] = $coop_no["deptaccount_no_coop"];
			$arrGroupLogBindAccount["BANK_ACCOUNT_NO"] = $coop_no["deptaccount_no_bank"];
			$arrGroupLogBindAccount["COOP_ACCOUNT_NO_FORMAT"]= $lib->formataccount($coop_no["deptaccount_no_coop"],$formatDept);
			$arrGroupLogBindAccount["BANK_ACCOUNT_NO_FORMAT"]= $lib->formataccount($coop_no["deptaccount_no_bank"],$formatDept);
  		    $arrGroupLogBindAccount["DATA_UNBIND_ERROR"] = $rowLogBindAccount["data_unbind_error"];
			$arrGroupLogBindAccount["QUERY_ERROR"] = $rowLogBindAccount["query_error"];
			$arrGroupLogBindAccount["QUERY_FLAG"] = $rowLogBindAccount["query_flag"];
			
			$arrayGroup[] = $arrGroupLogBindAccount;
		}
		$arrayResult["UNBIND_ACCOUNT_LOG"] = $arrayGroup;
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