<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_name','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managemenu')){
		$updatemenu = $conmysql->prepare("UPDATE coresubmenu SET menu_name = :menu_name
									 WHERE id_submenu = :id_submenu");
		if($updatemenu->execute([
			':menu_name' => $dataComing["menu_name"],
			':id_submenu' => $dataComing["id_submenu"]
		])){
			$arrayStruc = [
				':menu_name' => "managemenu",
				':username' => $payload["username"],
				':use_list' => "change menu name",
				':details' => 'menu_id : '.$dataComing["id_submenu"].' to > name : '.$dataComing["menu_name"]
			];
			$log->writeLog('editadmincontrol',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขชื่อเมนูได้ กรุณาติดต่อผู้พัฒนา";
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