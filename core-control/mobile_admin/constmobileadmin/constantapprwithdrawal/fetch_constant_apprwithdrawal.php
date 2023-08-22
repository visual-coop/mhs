<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
		$arrayGroup = array();
		$getUserFromMobile = $conmysql->prepare("SELECT username_core,member_no,id_apprwd_constant  FROM gcconstantapprwithdrawal WHERE is_use = '1'");
		$getUserFromMobile->execute();
		while($rowUser = $getUserFromMobile->fetch(PDO::FETCH_ASSOC)){
			$fetchApvLevel = $conoracle->prepare("SELECT amu.full_name,amu.description as section,aml.description,aml.dept_withdrawmax,aml.dept_depositmax
														FROM amsecusers amu LEFT JOIN amsecapvlevel aml ON amu.apvlevel_id = aml.apvlevel_id WHERE amu.user_name = :username and amu.user_status = 1");
			$fetchApvLevel->execute([':username' => $rowUser["username_core"]]);
			while($rowLevel = $fetchApvLevel->fetch(PDO::FETCH_ASSOC)){
				$arrApvLevel = array();
				$arrApvLevel["ID_APPRWD_CONSTANT"] = $rowUser["id_apprwd_constant"];
				$arrApvLevel["MEMBER_NO"] = $rowUser["member_no"];
				$arrApvLevel["POSITION"] = $rowLevel["DESCRIPTION"];
				$arrApvLevel["SECTION"] = $rowLevel["SECTION"];
				$arrApvLevel["FULL_NAME"] = $rowLevel["FULL_NAME"];
				$arrApvLevel["WITHDRAWMAX"] = number_format($rowLevel["DEPT_WITHDRAWMAX"],2);
				$arrApvLevel["DEPOSITMAX"] = number_format($rowLevel["DEPT_DEPOSITMAX"],2);
				$arrApvLevel["USERNAME"] = $rowUser["username_core"];
				$arrayGroup[] = $arrApvLevel;
			}
		}
		$arrayResult["USER_LEVEL"] = $arrayGroup;
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