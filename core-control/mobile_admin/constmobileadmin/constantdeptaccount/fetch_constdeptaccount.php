<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_accountconstant,
																		dept_type_code,
																		member_cate_code,
																		allow_deposit_inside,
																		allow_withdraw_inside,
																		allow_deposit_outside,
																		allow_withdraw_outside
																	FROM
																		gcconstantaccountdept
																	ORDER BY dept_type_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_ACCCONSTANT"] = $rowMenuMobile["id_accountconstant"];
			$arrConstans["DEPTTYPE_CODE"] = $rowMenuMobile["dept_type_code"];
			$arrConstans["MEMBER_TYPE_CODE"] = $rowMenuMobile["member_cate_code"];
			$arrConstans["ALLOW_DEPOSIT_INSIDE"] = $rowMenuMobile["allow_deposit_inside"];
			$arrConstans["ALLOW_WITHDRAW_INSIDE"] = $rowMenuMobile["allow_withdraw_inside"];
			$arrConstans["ALLOW_DEPOSIT_OUTSIDE"] = $rowMenuMobile["allow_deposit_outside"];
			$arrConstans["ALLOW_WITHDRAW_OUTSIDE"] = $rowMenuMobile["allow_withdraw_outside"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT DEPTTYPE_CODE,DEPTTYPE_DESC FROM DPDEPTTYPE ORDER BY DEPTTYPE_CODE ASC  ");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_DEPOSIT_INSIDE"] = '0';
						$arrayDepttype["ALLOW_WITHDRAW_INSIDE"] = '0';
						$arrayDepttype["ALLOW_DEPOSIT_OUTSIDE"] = '0';
						$arrayDepttype["ALLOW_WITHDRAW_OUTSIDE"] = '0';
						$arrayDepttype["MEMBER_TYPE_CODE"] = 'AL';
				}else{
					$arrayDepttype["ALLOW_DEPOSIT_INSIDE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_DEPOSIT_INSIDE"];
					$arrayDepttype["ALLOW_WITHDRAW_INSIDE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_WITHDRAW_INSIDE"];
					$arrayDepttype["ALLOW_DEPOSIT_OUTSIDE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_DEPOSIT_OUTSIDE"];
					$arrayDepttype["ALLOW_WITHDRAW_OUTSIDE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_WITHDRAW_OUTSIDE"];
					$arrayDepttype["MEMBER_TYPE_CODE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["MEMBER_TYPE_CODE"];
					//$arrayDepttype["ID_ACCCONSTANT"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ID_ACCCONSTANT"];
				}
				
			$arrayDepttype["DEPTTYPE_CODE"] = $rowDepttype["DEPTTYPE_CODE"];
			$arrayDepttype["DEPTTYPE_DESC"] = $rowDepttype["DEPTTYPE_DESC"];
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