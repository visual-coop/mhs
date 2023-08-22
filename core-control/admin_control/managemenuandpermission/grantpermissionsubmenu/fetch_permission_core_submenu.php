<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT cpsm.id_submenu,cpm.id_coremenu FROM corepermissionmenu cpm LEFT JOIN corepermissionsubmenu cpsm 
												ON cpm.id_permission_menu = cpsm.id_permission_menu WHERE cpsm.is_use = '1' and cpm.is_use = '1' and cpm.username = :username");
		$fetchMenuMobile->execute([
			':username' => $dataComing["username"]
		]);
		while($rowCoreSubMenu = $fetchMenuMobile->fetch(PDO::FETCH_ASSOC)){
			$arrCoreSubMenu = array();
			$arrCoreSubMenu["ID_SUBMENU"] = $rowCoreSubMenu["id_submenu"];
			$arrCoreSubMenu["ID_COREMENU"] = $rowCoreSubMenu["id_coremenu"];
			$arrayGroup[] = $arrCoreSubMenu;
		}
		$arrayResult["PERMISSION_MENU"] = $arrayGroup;
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