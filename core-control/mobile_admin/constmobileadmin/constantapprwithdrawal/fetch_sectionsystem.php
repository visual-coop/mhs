<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT id_section_system, section_system, system_assign FROM coresectionsystem WHERE is_use = '1'");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SECTION_SYSTEM"] = $rowMenuMobile["id_section_system"];
			$arrConstans["SECTION_SYSTEM"] = $rowMenuMobile["section_system"];
			$arrConstans["SYSTEM_ASSIGN"] = $rowMenuMobile["system_assign"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["SECTION_SYSTEM"] = $arrayGroup;
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