<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_group'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managegroup')){
		$DeleteSmsGroup = $conmysql->prepare("DELETE FROM smsgroupmember WHERE id_groupmember = :id_group");
		if($DeleteSmsGroup->execute([
			':id_group' => $dataComing["id_group"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบกลุ่มได้ กรุณาติดต่อผู้พัฒนา";
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