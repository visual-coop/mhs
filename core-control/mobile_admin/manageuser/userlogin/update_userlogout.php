<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_token','type_logout'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','userlogin')){
		if($func->logout($dataComing['id_token'],$dataComing['type_logout'])){
			$arrayStruc = [
				':menu_name' => "userlogin",
				':username' => $payload["username"],
				':use_list' => "logout",
				':details' => $dataComing["id_token"],
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถบังคับผู้ใช้นี้ออกจากระบบได้ กุรณาติดต่อผู้พัฒนา";
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