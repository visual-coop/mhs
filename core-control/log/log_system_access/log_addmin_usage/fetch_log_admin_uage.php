<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logadminusege')){
		$arrayGroup = array();
		$fetchLogAdminUsage = $conmysql->prepare("SELECT
																				admin.id_logadminusage,
																				admin.username,
																				admin.date_usage,
																				admin.use_list,
																				admin.details,
																				login.device_name
																			FROM
																				logadminusage admin
																			INNER JOIN coreuserlogin login ON
																				admin.id_userlogin = login.id_userlogin
																			ORDER BY admin.date_usage DESC");
		$fetchLogAdminUsage->execute();
		while($rowLogAdminUsage = $fetchLogAdminUsage->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogAdminUsage = array();
			$arrGroupLogAdminUsage["ID_LOGAMINUAGE"] = $rowLogAdminUsage["id_logadminusage"];
			$arrGroupLogAdminUsage["USERNAME"] = $rowLogAdminUsage["username"];
			$arrGroupLogAdminUsage["DEVICE_NAME"] = $rowLogAdminUsage["device_name"];
			$arrGroupLogAdminUsage["DATE_USAGE"] =  $lib->convertdate($rowLogAdminUsage["date_usage"],'d m Y',true); 
			$arrGroupLogAdminUsage["USE_LIST"] = $rowLogAdminUsage["use_list"];
			$arrGroupLogAdminUsage["DETAILS"] = $rowLogAdminUsage["details"];
			
			$arrayGroup[] = $arrGroupLogAdminUsage;
		}
		$arrayResult["LOG_ADMIN_USAG"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>