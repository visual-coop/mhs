<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_status','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$updatemenu = $conmysql->prepare("UPDATE coresubmenu SET menu_status = :menu_status
									 WHERE id_submenu = :id_submenu");
		if($updatemenu->execute([
			':menu_status' => $dataComing["menu_status"],
			':id_submenu' => $dataComing["id_submenu"]
		])){
			$arrayStruc = [
				':menu_name' => "managemenu",
				':username' => $payload["username"],
				':use_list' => "change menu status",
				':details' => 'interact status '.$dataComing["menu_status"].' on menu_id : '.$dataComing["id_submenu"]
			];
			$log->writeLog('editadmincontrol',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะเมนูได้ กรุณาติดต่อผู้พัฒนา#1 ";
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