<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_palette'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managepalette')){
		$updatePalette = $conmysql->prepare("UPDATE gcpalettecolor SET is_use = '-9' WHERE id_palette = :id_palette");
		if($updatePalette->execute([
			':id_palette' => $dataComing["id_palette"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถลบถาดสีได้ กรุณาติดต่อผู้พัฒนา";
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