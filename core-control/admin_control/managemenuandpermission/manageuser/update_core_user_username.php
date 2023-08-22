<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','newusername'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managecoreusers')){
		$updaeusername = $conmysql->prepare("UPDATE coreuser 
										  SET username = :newusername
								          WHERE  username = :username;");
		if($updaeusername->execute([
			':newusername' => $dataComing["newusername"],
			':username' => $dataComing["username"]
		])){
			$arrayStruc = [
				':menu_name' => "manageuser",
				':username' => $payload["username"],
				':use_list' => "change username",
				':details' => 'from '.$dataComing["username"].' to '.$dataComing["newusername"]
			];
			
			$log->writeLog('editadmincontrol',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลื่อนชื่อผู้ใช้ได้ กรุณาติดต่อผู้พัฒนา";
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