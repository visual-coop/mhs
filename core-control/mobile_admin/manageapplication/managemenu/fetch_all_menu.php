<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$arrayGroup = array();
		$fetchMenuMobile = $conmysql->prepare("SELECT id_menu, menu_name,menu_name_en, menu_status,menu_channel,menu_parent FROM gcmenu 
											  WHERE menu_status <> '-9' AND menu_parent IN(0,18,19,-9,-8,-1)
											  ORDER BY menu_order ASC ");
		$fetchMenuMobile->execute();
		while($rowMenuMobile = $fetchMenuMobile->fetch(PDO::FETCH_ASSOC)){
			$arrGroupMenu = array();
			$arrGroupMenu["ID_MENU"] = $rowMenuMobile["id_menu"];
			$arrGroupMenu["MENU_NAME"] = $rowMenuMobile["menu_name"];
			$arrGroupMenu["MENU_NAME_EN"] = $rowMenuMobile["menu_name_en"];
			$arrGroupMenu["MENU_STATUS"] = $rowMenuMobile["menu_status"];
			$arrGroupMenu["MENU_PARENT"] = $rowMenuMobile["menu_parent"];
			$arrGroupMenu["MENU_CHANNEL"] = $rowMenuMobile["menu_channel"];
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