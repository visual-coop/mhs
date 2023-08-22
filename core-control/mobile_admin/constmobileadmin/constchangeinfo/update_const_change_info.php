<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantchangeinfo')){
		foreach($dataComing["contdata"] as $constData){
			$updateConst = $conmysql->prepare("UPDATE gcconstantchangeinfo SET is_change = :is_change,save_tablecore = :save_tablecore WHERE const_code = :const_code");
			if($updateConst->execute([
				':is_change' => $constData["IS_CHANGE"],
				':save_tablecore' => $constData["SAVE_TABLECORE"],
				':const_code' => $constData["CONST_CODE"]
			])){
			}else{
				$arrayResult['RESULT'] = FALSE;
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
				echo json_encode($arrayResult);
				exit();
			}
		}
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