<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsdeposit')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_smsconstantdept,
																		dept_itemtype_code,
																		allow_smsconstantdept
																	FROM
																		smsconstantdept
																	ORDER BY dept_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTDEPT"] = $rowMenuMobile["id_smsconstantdept"];
			$arrConstans["DEPTITEMTYPE_CODE"] = $rowMenuMobile["dept_itemtype_code"];
			$arrConstans["ALLOW_SMSCONSTANTDEPT"] = $rowMenuMobile["allow_smsconstantdept"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT DEPTITEMTYPE_CODE,DEPTITEMTYPE_DESC FROM DPUCFDEPTITEMTYPE ORDER BY DEPTITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if((array_search($rowDepttype["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE')) === False) || sizeof($arrayChkG) == 0){
						$arrayDepttype["ALLOW_SMSCONSTANTDEPT"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTDEPT"] = $arrayChkG[array_search($rowDepttype["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTDEPT"];
				}
				
			$arrayDepttype["DEPTITEMTYPE_CODE"] = $rowDepttype["DEPTITEMTYPE_CODE"];
			$arrayDepttype["DEPTITEMTYPE_DESC"] = $rowDepttype["DEPTITEMTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
		$arrayResult["ACCOUNT_DATA"] = $arrayGroup;
		
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