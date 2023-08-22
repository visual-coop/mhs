<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','userlogin')){
		$arrayGroup = array();
		$fetchUserlogin = $conmysql->prepare("SELECT member_no, device_name, login_date, id_token ,channel
												FROM  gcuserlogin WHERE is_login = '1' ORDER BY id_userlogin DESC");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupRootUserlogin["CHANNEL"] = $rowUserlogin["channel"];
			$arrGroupRootUserlogin["DEVICE"] = $rowUserlogin["device_name"];
			$arrGroupRootUserlogin["LOGIN_DATE"] = $lib->convertdate($rowUserlogin["login_date"],'d m Y',true);
			$arrGroupRootUserlogin["ID_TOKEN"] = $rowUserlogin["id_token"];
			$arrayGroup[] = $arrGroupRootUserlogin;
		}
		$arrayResult["USER_LOGIN"] = $arrayGroup;
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