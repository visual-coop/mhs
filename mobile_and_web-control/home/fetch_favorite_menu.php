<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		$arrGroupFavmenu = array();
		$fetchFavMenu = $conmysql->prepare("SELECT gfl.fav_name,gpc.type_palette,gpc.color_deg,gpc.color_main,gpc.color_secon,gpc.color_text
											FROM gcfavoritemenu gfm LEFT JOIN gcpalettecolor gpc ON gfm.id_palette = gpc.id_palette and gpc.is_use = '1'
											LEFT JOIN gcfavoritelist gfl ON gfm.fav_refno = gfl.fav_refno and gfl.is_use = '1'
											WHERE gfl.member_no = :member_no ORDER BY gfm.seq_no ASC");
		$fetchFavMenu->execute([':member_no' => $payload["member_no"]]);
		while($rowFavMenu = $fetchFavMenu->fetch(PDO::FETCH_ASSOC)){
			$arrayFavMenu = array();
			$arrayFavMenu["FAV_NAME_MENU"] = $rowFavMenu["fav_name"];
			$arrayFavMenu["FAV_ICON_MENU"] = mb_substr($rowFavMenu["fav_name"],0,1);
			if(isset($rowFavMenu["type_palette"])){
				if($rowFavMenu["type_palette"] == '2'){
					$arrayFavMenu["ACCOUNT_COOP_COLOR"] = $rowFavMenu["color_deg"]."|".$rowFavMenu["color_main"].",".$rowFavMenu["color_secon"];
				}else{
					$arrayFavMenu["ACCOUNT_COOP_COLOR"] = "90|".$rowFavMenu["color_main"].",".$rowFavMenu["color_main"];
				}
				$arrayFavMenu["ACCOUNT_COOP_TEXT_COLOR"] = $rowFavMenu["color_text"];
			}else{
				$arrayFavMenu["ACCOUNT_COOP_COLOR"] = $config["DEFAULT_BANNER_COLOR_DEG"]."|".$config["DEFAULT_BANNER_COLOR_MAIN"].",".$config["DEFAULT_BANNER_COLOR_SECON"];
				$arrayFavMenu["ACCOUNT_COOP_TEXT_COLOR"] = $config["DEFAULT_BANNER_COLOR_TEXT"];
			}
			$arrGroupFavmenu[] = $arrayFavMenu;
		}
		$arrayResult['FAV_MENU'] = $arrGroupFavmenu;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>