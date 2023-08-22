<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logerrorusage')){
		$arrayGroup = array();
		$fetchLogError = $conmysql->prepare("SELECT error_menu,
															error_code,
															error_desc,
															error_date,
															error_device
													FROM logerrorusageapplication ORDER BY  error_date DESC");
		$fetchLogError->execute();
		while($rowLogError = $fetchLogError->fetch(PDO::FETCH_ASSOC)){
			$arrLogError = array();
			$arrLogError["ERROR_MENU"] = $rowLogError["error_menu"];
			$arrLogError["ERROR_CODE"] = $rowLogError["error_code"];
			$arrLogError["ERROR_DESC"] = $rowLogError["error_desc"];
			$arrLogError["ERROR_DATE"] =  $lib->convertdate($rowLogError["error_date"],'d m Y',true); 
			$arrLogError["ERROR_DEVICE"] = $rowLogError["error_device"];
			
			$arrayGroup[] = $arrLogError;
		}
		$arrayResult["LOG_ERROR_DATA"] = $arrayGroup;
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