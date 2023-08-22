<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no','username'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
		$updateConstants = $conmysql->prepare("UPDATE gcconstantapprwithdrawal SET username_core = :username, member_no = :member_no WHERE id_apprwd_constant = :id_apprwd_constant");
		if($updateConstants->execute([
			':username' => $dataComing["username"],
			':member_no' => $dataComing["member_no"],
			':id_apprwd_constant'  => $dataComing["id_apprwd_constant"]
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
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