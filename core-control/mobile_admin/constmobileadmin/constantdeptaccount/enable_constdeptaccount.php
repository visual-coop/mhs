<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_accountconstant','is_use'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$updateConstants = $conmysql->prepare("UPDATE gcconstantaccountdept SET is_use = :is_use
												WHERE id_accountconstant = :id_accountconstant");
		if($updateConstants->execute([
			':is_use' => $dataComing["is_use"],
			':id_accountconstant' => $dataComing["id_accountconstant"]
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขการเปิดใช้งานประเภทบัญชีได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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