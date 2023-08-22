<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managemenu')){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT id_coremenu, coremenu_name
												FROM coremenu
												WHERE coremenu_status <>'-9'
												ORDER BY coremenu_order ASC");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch(PDO::FETCH_ASSOC)){
			$arrGroupMenu = array();
			$arrGroupMenu["ID_COREMENU"] = $rowMenuMobile["id_coremenu"];
			$arrGroupMenu["COREMENU_NAME"] = $rowMenuMobile["coremenu_name"];
			$arrayGroup[] = $arrGroupMenu;
		}
		$arrayResult["CORE_MENU"] = $arrayGroup;
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