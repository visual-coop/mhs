<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conmysql->prepare("SELECT username FROM coreuser WHERE user_status = '1' and username NOT IN('dev@mode','salemode','system')");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrGroupCoreSubMenu = array();
			$arrGroupCoreSubMenu["USERNAME"] = $rowCoreSubMenu["username"];
			$arrayGroup[] = $arrGroupCoreSubMenu;
		}
		$arrayResult["CORE_USER"] = $arrayGroup;
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