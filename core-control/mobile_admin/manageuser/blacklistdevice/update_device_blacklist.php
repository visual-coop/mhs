<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','arr_id_blacklist'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','blacklistdevice')){
		$updateform = $conmysql->prepare("UPDATE gcdeviceblacklist SET is_blacklist = '0'
											WHERE id_blacklist IN(".implode(',',$dataComing["arr_id_blacklist"]).")");
		if($updateform->execute()){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเเก้ไขสถานะนี้ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['data'] = $dataComing["arr_id_blacklist"];
			$arrayResult['sql'] = "UPDATE gcdeviceblacklist SET is_blacklist = 0
											WHERE id_blacklist IN(".implode(',',$dataComing["arr_id_blacklist"]).")";
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