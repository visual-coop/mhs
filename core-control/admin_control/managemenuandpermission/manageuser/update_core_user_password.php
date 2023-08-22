<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','newpassword'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managecoreusers')){
		$updatepassword = $conmysql->prepare("UPDATE coreuser 
										  SET password = :newpassword
								          WHERE  username = :username;");
		$new_password = password_hash($dataComing["newpassword"], PASSWORD_DEFAULT);								  
		if($updatepassword->execute([
			':newpassword' => $new_password,
			':username' => $dataComing["username"]
		])){
				$arrayStruc = [
					':menu_name' => "manageuser",
					':username' => $payload["username"],
					':use_list' => "change password",
					':details' => 'username : '.$dataComing["username"]
				];
			
			$log->writeLog('editadmincontrol',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนรหัสผ่านได้ กรุณาติดต่อผู้พัฒนา";
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