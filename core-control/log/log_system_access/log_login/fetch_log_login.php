<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','loglogin')){
		$arrayGroup = array();
		$fetchfetchLoginLog = $conmysql->prepare("SELECT
																				g.id_userlogin,
																				g.member_no,
																				g.device_name,
																				g.channel,
																				g.login_date,
																				g.logout_date,
																				g.is_login,
																				g.unique_id,
																				g.status_firstapp,
																				k.ip_address
																			FROM
																				gcuserlogin g
																			INNER JOIN gctoken k ON
																				k.id_token = g.id_token
																			ORDER BY g.login_date DESC");
		$fetchfetchLoginLog->execute();
		while($rowLoginLog = $fetchfetchLoginLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupLoginLog = array();
			$arrGroupLoginLog["ID_USERLOGIN"] = $rowLoginLog["id_userlogin"];
			$arrGroupLoginLog["MEMBER_NO"] = $rowLoginLog["member_no"];
			$arrGroupLoginLog["DEVICE_NAME"] = $rowLoginLog["device_name"];
			$arrGroupLoginLog["CHANNEL"] = $rowLoginLog["channel"];
			$arrGroupLoginLog["LOGIN_DATE"] =  $lib->convertdate($rowLoginLog["login_date"],'d m Y',true); 
			$arrGroupLoginLog["LOGOUT_DATE"] =  isset($rowLoginLog["logout_date"]) ? $lib->convertdate($rowLoginLog["logout_date"],'d m Y',true) : null;
			$arrGroupLoginLog["IS_LOGIN"] = $rowLoginLog["is_login"];
			$arrGroupLoginLog["IP_ADDRESS"] = $rowLoginLog["ip_address"];
			
			$arrayGroup[] = $arrGroupLoginLog;
		}
		$arrayResult["LOGINLOG_DATA"] = $arrayGroup;
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