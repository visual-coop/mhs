<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','password','id_section_system'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managecoreusers')){
		$updatemenu = $conmysql->prepare("INSERT INTO coreuser (username, password, id_section_system) 
										 VALUES(:username,:password,:id_section_system)");
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		if($updatemenu->execute([
			':username' => $dataComing["username"],
			':password' => $password,
			':id_section_system' => $dataComing["id_section_system"]
		])){
			$arrayStruc = [
				':menu_name' => "manageuser",
				':username' => $payload["username"],
				':use_list' => "insert core user",
				':details' => 'add username : '.$dataComing["username"]
			];
			$log->writeLog('editadmincontrol',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มผู้ใช้งานได้กรุณาติดต่อผู้พัฒนา";
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