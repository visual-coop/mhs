<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
		$updateConstants = $conmysql->prepare("INSERT INTO gcconstantapprwithdrawal(member_no, username_core) 
																VALUES (:member_no,:username)");
		if($updateConstants->execute([
			':member_no' => $dataComing["member_no"],
			':username' => $dataComing["username"]
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่เป็นค่าว่างได้";
		$arrayResult['RESULT'] = FALSE;
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>