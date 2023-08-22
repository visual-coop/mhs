<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','title_menu'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT cbs.id_submenu, cbs.menu_name, cbs.menu_status, cm.coremenu_name, cbs.id_coremenu
												FROM coresubmenu cbs LEFT JOIN coremenu cm ON cbs.id_coremenu = cm.id_coremenu and cm.coremenu_status = '1'
												WHERE cbs.id_menuparent != 0 and cbs.menu_status = '1'
												ORDER BY cbs.id_menuparent ASC");
		$fetchMenuMobile->execute();
		while($rowCoreSubMenu = $fetchMenuMobile->fetch(PDO::FETCH_ASSOC)){
			$arrCoreSubMenu = array();
			$arrGroupCoreSubMenu = array();
			$arrGroupCoreSubMenu["TITLE"] = $rowCoreSubMenu["coremenu_name"];
			$arrGroupCoreSubMenu["ID_COREMENU"] = $rowCoreSubMenu["id_coremenu"];
			$arrCoreSubMenu["ID_SUBMENU"] = $rowCoreSubMenu["id_submenu"];
			$arrCoreSubMenu["ID_COREMENU"] = $rowCoreSubMenu["id_coremenu"];
			$arrCoreSubMenu["MENU_NAME"] = $rowCoreSubMenu["menu_name"];
			if(array_search($rowCoreSubMenu["coremenu_name"],array_column($arrayGroup,'TITLE')) === False){
				$arrGroupCoreSubMenu["SUB_MENU"][] = $arrCoreSubMenu;
				$arrayGroup[] = $arrGroupCoreSubMenu;
			}else{
				($arrayGroup[array_search($rowCoreSubMenu["coremenu_name"],array_column($arrayGroup,'TITLE'))]["SUB_MENU"])[] = $arrCoreSubMenu;
			}
		}
		$arrayResult["CORE_SUB_MENU"] = $arrayGroup;
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