<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','color_main','color_text','type_palette'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managepalette')){
		if($dataComing["type_palette"] == '1'){
			$insertPalette = $conmysql->prepare("INSERT INTO gcpalettecolor (type_palette,color_main,color_secon,color_deg,color_text) 
								VALUES (:type_palette,:color_main,:color_main,'0',:color_text)");
			if($insertPalette->execute([
				':type_palette' => $dataComing["type_palette"],
				':color_main' => $dataComing["color_main"],
				':color_text' => $dataComing["color_text"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มถาดสีได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			if(isset($dataComing["color_secon"]) && isset($dataComing["color_deg"])){
				$insertPalette = $conmysql->prepare("INSERT INTO gcpalettecolor (type_palette,color_main,color_secon,color_deg,color_text) 
								VALUES (:type_palette,:color_main,:color_secon,:color_deg,:color_text)");
				if($insertPalette->execute([
					':type_palette' => $dataComing["type_palette"],
					':color_main' => $dataComing["color_main"],
					':color_secon' => $dataComing["color_secon"],
					':color_deg' => $dataComing["color_deg"],
					':color_text' => $dataComing["color_text"]
				])){
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มถาดสีได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
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