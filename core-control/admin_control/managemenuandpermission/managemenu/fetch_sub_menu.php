<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT id_submenu, menu_name, menu_status,id_coremenu,id_menuparent,menu_order
												FROM coresubmenu
												WHERE menu_status <>'-9' AND id_menuparent !=0
												ORDER BY menu_order ASC");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch(PDO::FETCH_ASSOC)){
			$arrGroupMenu = array();
			$arrGroupMenu["ID_SUbMENU"] = $rowMenuMobile["id_submenu"];
			$arrGroupMenu["MENU_NAME"] = $rowMenuMobile["menu_name"];
			$arrGroupMenu["MENU_STATUS"] = $rowMenuMobile["menu_status"];
			$arrGroupMenu["ID_MENUPARENT"] = $rowMenuMobile["id_menuparent"];
			$arrGroupMenu["MENU_ORDER"] = $rowMenuMobile["menu_order"];
			$arrGroupMenu["ID_COREMENU"] = $rowMenuMobile["id_coremenu"];
			$arrayGroup[] = $arrGroupMenu;
		}
		$arrayResult["MENU_ALL"] = $arrayGroup;
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