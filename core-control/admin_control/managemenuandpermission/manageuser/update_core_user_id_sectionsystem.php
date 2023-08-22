<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','id_section_system'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managecoreusers')){
		$updateidsectionsystem = $conmysql->prepare("UPDATE coreuser 
										  SET id_section_system = :id_section_system
								          WHERE  username = :username;");
		if($updateidsectionsystem->execute([
			':id_section_system' => $dataComing["id_section_system"],
			':username' => $dataComing["username"]
		])){
			$arrayStruc = [
				':menu_name' => "manageuser",
				':username' => $payload["username"],
				':use_list' => "change department ",
				':details' => 'id_section : '.$dataComing["id_section_system"].' on username : '.$dataComing["username"]
			];
			$log->writeLog('editadmincontrol',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขแผนกได้ กรุณาติดต่อผู้พัฒนา";
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