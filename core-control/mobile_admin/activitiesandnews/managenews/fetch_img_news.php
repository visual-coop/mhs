<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_gallery'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews')){
		$arrayGroup = array();
		$fetchGallery = $conmysql->prepare("SELECT  id_news, path_img_header, img_gallery_1, img_gallery_2, img_gallery_3, img_gallery_4, img_gallery_5
										  FROM gcnews
										  WHERE id_news='$dataComing[id_gallery]'");
		$fetchGallery->execute();
		while($rowGallery = $fetchGallery->fetch(PDO::FETCH_ASSOC)){
			$arrGallery = array();
			$arrGallery["ID_GALLERY"] = $rowGallery["id_news"];
			$arrGallery["PATH_IMG_HEADER"] = $rowGallery["path_img_header"];
			$arrGallery["PATH_IMG_1"] = $rowGallery["img_gallery_1"];
			$arrGallery["PATH_IMG_2"] = $rowGallery["img_gallery_2"];
			$arrGallery["PATH_IMG_3"] = $rowGallery["img_gallery_3"];
			$arrGallery["PATH_IMG_4"] = $rowGallery["img_gallery_4"];
			$arrGallery["PATH_IMG_5"] = $rowGallery["img_gallery_5"];
			$arrayGroup[] = $arrGallery;
		}
		$arrayResult["GALLERY_DATA"] = $arrayGroup;
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