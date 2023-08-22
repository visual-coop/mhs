<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsshare')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_smsconstantshare,
																		share_itemtype_code,
																		allow_smsconstantshare
																	FROM
																		smsconstantshare
																	ORDER BY share_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTSHARE"] = $rowMenuMobile["id_smsconstantshare"];
			$arrConstans["SHRITEMTYPE_CODE"] = $rowMenuMobile["share_itemtype_code"];
			$arrConstans["ALLOW_SMSCONSTANTSHARE"] = $rowMenuMobile["allow_smsconstantshare"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT SHRITEMTYPE_CODE,SHRITEMTYPE_DESC FROM SHUCFSHRITEMTYPE ORDER BY SHRITEMTYPE_CODE ASC");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if((array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE')) === False) || sizeof($arrayChkG) == 0){
						$arrayDepttype["ALLOW_SMSCONSTANTSHARE"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTSHARE"] = $arrayChkG[array_search($rowDepttype["SHRITEMTYPE_CODE"],array_column($arrayChkG,'SHRITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTSHARE"];
				}
				
			$arrayDepttype["SHRITEMTYPE_CODE"] = $rowDepttype["SHRITEMTYPE_CODE"];
			$arrayDepttype["SHRITEMTYPE_DESC"] = $rowDepttype["SHRITEMTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
		$arrayResult["SHARE_DATA"] = $arrayGroup;
		
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