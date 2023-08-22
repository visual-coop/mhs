<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$arrDepttypeuse = array();
		$fetchDepttypeUsed = $conmysql->prepare("SELECT DEPT_TYPE_CODE FROM gcconstantaccountdept");
		$fetchDepttypeUsed->execute();
		while($rowDepttypeUse = $fetchDepttypeUsed->fetch(PDO::FETCH_ASSOC)){
			$arrDepttypeuse[] = $rowDepttypeUse["DEPT_TYPE_CODE"];
		}
		$fetchDepttype = $conoracle->prepare("SELECT DEPTTYPE_CODE,DEPTTYPE_DESC FROM DPDEPTTYPE ");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
			$arrayDepttype["DEPTTYPE_CODE"] = $rowDepttype["DEPTTYPE_CODE"];
			$arrayDepttype["DEPTTYPE_DESC"] = $rowDepttype["DEPTTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		$arrayResult["DEPT_TYPE"] = $arrayGroup;
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