<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','searchmember')){
		$arrayGroup = array();
		$fetchProvince = $conoracle->prepare("SELECT province_code,province_desc FROM mbucfprovince");
		$fetchProvince->execute();
		while($rowProvince = $fetchProvince->fetch(PDO::FETCH_ASSOC)){
			$arrayProvince = array();
			$arrayProvince["PROVINCE_CODE"] = $rowProvince["PROVINCE_CODE"];
			$arrayProvince["PROVINCE_DESC"] = $rowProvince["PROVINCE_DESC"];
			$arrayGroup[] = $arrayProvince;
		}
		$arrayResult["PROVINCE"] = $arrayGroup;
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