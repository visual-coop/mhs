<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_list'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		$conmysql->beginTransaction();
		foreach($dataComing["menu_list"] as $menu_list){
			$updatemenu = $conmysql->prepare("UPDATE coresubmenu SET menu_order = :menu_order
										 WHERE id_submenu = :id_submenu");
			if($updatemenu->execute([
				':menu_order' => $menu_list["order"],
				':id_submenu' => $menu_list["menu_id"]
			])){
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถจัดเรียงเมนูได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		$conmysql->commit();
		$arrayStruc = [
			':menu_name' => "managemenu",
			':username' => $payload["username"],
			':use_list' => "order menu",
			':details' => json_encode($dataComing["menu_list"])
		];
		$log->writeLog('editadmincontrol',$arrayStruc);
		$arrayResult["RESULT"] = TRUE;
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