<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managebackground')){
		$fetchBG = $conmysql->prepare("SELECT id_background,image,update_date,is_use,update_by FROM gcconstantbackground WHERE is_use = '1'");
								
		$fetchBG->execute();
		$arrayGroup = array();
		while($rowbg = $fetchBG->fetch(PDO::FETCH_ASSOC)){
			$arrGroupNews = array();
			$arrGroupNews["ID_BACKGROUND"] = $rowbg["id_background"];
			$arrGroupNews["UPDATE_DATE"] = $rowbg["update_date"];
			$arrGroupNews["IS_USE"] = $rowbg["is_use"];
			$arrGroupNews["UPDATE_BY"] = $rowbg["update_by"];
			
			if(isset($rowbg["image"])){
				$arrGroupNews["IMAGE"] = $config["URL_SERVICE"].$rowbg["image"];
				$explodePathAvatar = explode('.',$rowbg["image"]);
				$arrGroupNews["IMAGE_WEBP"] = $config["URL_SERVICE"].$explodePathAvatar[0].'.webp';
			}else{
				$arrGroupNews["IMAGE"] = null;
				$arrGroupNews["IMAGE_WEBP"] = null;
			}
			
			$arrayGroup[] = $arrGroupNews;
		}
		
		$arrayResult["BG_DATA"] = $arrayGroup;
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