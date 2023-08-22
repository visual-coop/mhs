<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logeditmobileadmin')){
		$arrayGroup = array();
		$fetchLogAdminUsage = $conmysql->prepare("SELECT
															admin.id_logeditmobileadmin,
															admin.menu_name,
															admin.username,
															admin.date_usage,
															admin.use_list,
															admin.details						
												FROM
															logeditmobileadmin admin
												ORDER BY admin.date_usage DESC");
		$fetchLogAdminUsage->execute();
		while($rowLogAdminUsage = $fetchLogAdminUsage->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLogAdminUsage = array();
			$arrGroupLogAdminUsage["ID_LOGAMINUAGE"] = $rowLogAdminUsage["id_logeditmobileadmin"];
			$arrGroupLogAdminUsage["MENU_NAME"] = $rowLogAdminUsage["menu_name"];
			$arrGroupLogAdminUsage["USERNAME"] = $rowLogAdminUsage["username"];
			$arrGroupLogAdminUsage["DATE_USAGE"] =  $rowLogAdminUsage["date_usage"];
			$arrGroupLogAdminUsage["DATE_USAGE_FORMAT"] =  $lib->convertdate($rowLogAdminUsage["date_usage"],'d m Y',true); 
			$arrGroupLogAdminUsage["USE_LIST"] = $rowLogAdminUsage["use_list"];
			$arrGroupLogAdminUsage["DETAILS"] = $rowLogAdminUsage["details"];
			
			$arrayGroup[] = $arrGroupLogAdminUsage;
		}
		$arrayResult["LOG_EDIT_MOBILEADMIN"] = $arrayGroup;
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