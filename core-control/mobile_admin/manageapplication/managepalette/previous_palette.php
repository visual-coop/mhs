<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','color_main','color_text','type_palette','id_palette'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managepalette')){
		if($dataComing["type_palette"] == '1'){
			$updatePalette = $conmysql->prepare("UPDATE gcpalettecolor SET color_main_prev = color_main,color_secon_prev = color_secon,type_palette_prev = type_palette,color_text_prev = color_text,
									color_deg_prev = color_deg, color_main = :color_main,color_secon = :color_main,color_deg = color_deg,color_text = :color_text,type_palette = :type_palette  
									WHERE id_palette =:id_palette");
			if($updatePalette->execute([
				':type_palette' => $dataComing["type_palette"],
				':color_main' => $dataComing["color_main"],
				':color_text' => $dataComing["color_text"],
				':id_palette' => $dataComing["id_palette"]
			])){
				$arrayResult['RESULT'] = TRUE;
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเเก้ไขถาดสีได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
			}
			require_once('../../../../include/exit_footer.php');
		}else{
			if(isset($dataComing["color_secon"]) && isset($dataComing["color_deg"])){
				$updatePalette = $conmysql->prepare("UPDATE gcpalettecolor SET color_main_prev = color_main,color_secon_prev = color_secon,type_palette_prev = type_palette,color_text_prev = color_text,
									color_deg_prev = color_deg, color_main = :color_main,color_secon = :color_secon,color_deg = :color_deg,color_text = :color_text,type_palette = :type_palette  
									WHERE id_palette = :id_palette");
				if($updatePalette->execute([
					':type_palette' => $dataComing["type_palette"],
					':color_main' => $dataComing["color_main"],
					':color_secon' => $dataComing["color_secon"],
					':color_deg' => $dataComing["color_deg"],
					':color_text' => $dataComing["color_text"],
					':id_palette' => $dataComing["id_palette"]
				])){
					$arrayResult['RESULT'] = TRUE;
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถเเก้ไขถาดสีได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
				}
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESULT'] = FALSE;
				http_response_code(400);
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