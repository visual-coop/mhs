<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_task'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','calendarcoop')){
		$deleteSendAhead = $conmysql->prepare("DELETE FROM gctaskevent WHERE id_task = :id_task");
		if($deleteSendAhead->execute([
			':id_task' => $dataComing["id_task"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบกิจกรรมนี้ได้ กรุณาติดต่อผู้พัฒนา";
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