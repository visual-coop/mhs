<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','bindacc_status','id_bindaccount'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','manageuser')){
		$updaeusername = $conmysql->prepare("UPDATE gcbindaccount 
										  SET bindaccount_status = :bindacc_status
								          WHERE  id_bindaccount = :id_bindaccount;;");
		if($updaeusername->execute([
			':bindacc_status' => $dataComing["bindacc_status"],
			':id_bindaccount' => $dataComing["id_bindaccount"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขชื่อเมนูได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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