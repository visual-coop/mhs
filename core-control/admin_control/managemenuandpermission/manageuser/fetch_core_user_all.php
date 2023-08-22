<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroup = array();
		$fetchUser = $conmysql->prepare("SELECT coreuser.username,coreuser.id_section_system, coresectionsystem.section_system, coresectionsystem.system_assign ,coreuser.user_status
											FROM coreuser
											INNER JOIN coresectionsystem
											ON coresectionsystem.id_section_system = coreuser.id_section_system WHERE coresectionsystem.id_section_system NOT IN('1','11')
											and coreuser.user_status = '1'");
		$fetchUser->execute();
		while($rowCoreSubMenu = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrGroupCoreUser = array();
			$arrGroupCoreUser["USERNAME"] = $rowCoreSubMenu["username"];
			$arrGroupCoreUser["ID_SECTION_SYSTEM"] = $rowCoreSubMenu["id_section_system"];
			$arrGroupCoreUser["SECTION_SYSTEM"] = $rowCoreSubMenu["section_system"];
			$arrGroupCoreUser["SYSTEM_ASSIGN"] = $rowCoreSubMenu["system_assign"];
			$arrGroupCoreUser["USER_STATUS"] = $rowCoreSubMenu["user_status"];
			$arrayGroup[] = $arrGroupCoreUser;
		}
		$arrayResult["CORE_USER"] = $arrayGroup;
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