<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','limit_amt','deptaccount_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$updateLimitTrans = $conmysql->prepare("UPDATE gcuserallowacctransaction SET limit_transaction_amt = :limit_amt 
												WHERE member_no = :member_no and deptaccount_no = :deptaccount_no");
		if($updateLimitTrans->execute([
			':limit_amt' => $dataComing["limit_amt"],
			':member_no' => $payload["member_no"],
			':deptaccount_no' => $dataComing["deptaccount_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrExecute = [
				':limit_amt' => $dataComing["limit_amt"],
				':member_no' => $payload["member_no"],
				':deptaccount_no' => $dataComing["deptaccount_no"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updateLimitTrans;
			$arrError["ERROR_CODE"] = 'WS1026';
			$lib->addLogtoTxt($arrError,'changelimit_error');
			$arrayResult['RESPONSE_CODE'] = "WS1026";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>