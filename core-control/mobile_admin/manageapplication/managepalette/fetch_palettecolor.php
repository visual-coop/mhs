<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managepalette')){
		$arrayGroup = array();
		$fetchPalette = $conmysql->prepare("SELECT id_palette,type_palette,color_main,color_secon,color_deg,color_text,
										type_palette_prev,color_main_prev,color_secon_prev,color_text_prev,color_deg_prev,update_date
										FROM gcpalettecolor WHERE is_use = '1'");
		$fetchPalette->execute();
		while($rowPalette = $fetchPalette->fetch(PDO::FETCH_ASSOC)){
			$arrPalette = array();
			$arrPalette["ID_PALETTE"] = $rowPalette["id_palette"];
			$arrPalette["TYPE_PALETTE"] = $rowPalette["type_palette"];
			$arrPalette["COLOR_MAIN"] = $rowPalette["color_main"];
			$arrPalette["COLOR_SECON"] = $rowPalette["color_secon"];
			$arrPalette["COLOR_DEG"] = $rowPalette["color_deg"];
			$arrPalette["COLOR_TEXT"] = $rowPalette["color_text"];
			$arrPalette["TYPE_PALETTE_PREV"] = $rowPalette["type_palette_prev"];
			$arrPalette["COLOR_MAIN_PREV"] = $rowPalette["color_main_prev"];
			$arrPalette["COLOR_SECON_PREV"] = $rowPalette["color_secon_prev"];
			$arrPalette["COLOR_DEG_PREV"] = $rowPalette["color_deg_prev"];
			$arrPalette["COLOR_TEXT_PREV"] = $rowPalette["color_text_prev"];
			$arrPalette["UPDATE_DATE"] = $rowPalette["update_date"];
			
			$arrayGroup[] = $arrPalette;
		}
		$arrayResult["PALETTE_DATA"] = $arrayGroup;
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