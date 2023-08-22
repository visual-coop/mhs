<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logapplicationuse')){
		$arrayGroup = array();
		$fetchApplicationUseLog = $conmysql->prepare("SELECT
																					l.id_loguseapp,
																					l.member_no,
																					l.id_userlogin,
																					l.access_date,
																					l.ip_address,
																					g.device_name,
																					g.login_date,
																					g.logout_date,
																					g.is_login
																				FROM
																					loguseapplication l
																				INNER JOIN gcuserlogin g ON
																					g.id_userlogin = l.id_userlogin
																				WHERE l.access_date BETWEEN NOW() - INTERVAL 3 MONTH and NOW()
																					ORDER BY g.login_date DESC");
		$fetchApplicationUseLog->execute();
		while($rowAppUseLog = $fetchApplicationUseLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupApplicationUseLog = array();
			$arrGroupApplicationUseLog["ID_USERLOGIN"] = $rowAppUseLog["id_userlogin"];
			$arrGroupApplicationUseLog["MEMBER_NO"] = $rowAppUseLog["member_no"];
			$arrGroupApplicationUseLog["DEVICE_NAME"] = $rowAppUseLog["device_name"];
			$arrGroupApplicationUseLog["ACCESS_DATE"] =  $lib->convertdate($rowAppUseLog["access_date"],'d m Y',true); 
			$arrGroupApplicationUseLog["IS_LOGIN"] = $rowAppUseLog["is_login"];
			$arrGroupApplicationUseLog["IP_ADDRESS"] = $rowAppUseLog["ip_address"];
			
			$arrayGroup[] = $arrGroupApplicationUseLog;
		}
		$arrayResult["APP_USE_LOG_DATA"] = $arrayGroup;
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