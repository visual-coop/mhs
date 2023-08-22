<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_status','id_menu'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenu')){
		if($dataComing["menu_status"] == "close"){
			$updatemenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = '0'
										 WHERE id_menu = :id_menu");
			if($updatemenu->execute([
				':id_menu' => $dataComing["id_menu"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะเมนูได้ กรุณาติดต่อผู้พัฒนา #close";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$updatemenu = $conmysql->prepare("UPDATE gcmenu SET menu_status = '1', menu_channel = :menu_channel
										 WHERE id_menu = :id_menu");
			if($updatemenu->execute([
				':menu_channel' => $dataComing["menu_status"],
				':id_menu' => $dataComing["id_menu"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเปลี่ยนสถานะเมนูได้ กรุณาติดต่อผู้พัฒนา #channel";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
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