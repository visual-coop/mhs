<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews')){
		$arrayGroup = array();
		$arrayExecute = array();
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute["start_date"] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute["end_date"] = $dataComing["end_date"];
		}
			
		$fetchNews = $conmysql->prepare("SELECT 
																id_news,news_title,
																news_detail,
																news_html,
																path_img_header,
																create_date,
																update_date,
																link_news_more,
																img_gallery_1,
																img_gallery_2,
																img_gallery_3,
																img_gallery_4,
																img_gallery_5,
																file_upload
															FROM gcnews
															WHERE is_use = '1' 
															".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
																"and date_format(create_date,'%Y-%m-%d') >= :start_date" : null)."
															".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
																"and date_format(create_date,'%Y-%m-%d') <= :end_date" : null). "
															ORDER BY create_date DESC LIMIT 20");
		$fetchNews->execute($arrayExecute);
		while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
			$arrGroupNews = array();
			$arrGroupNews["ID_NEW"] = $rowNews["id_news"];
			$arrGroupNews["NEWS_TITLE"] = $rowNews["news_title"];
			$arrGroupNews["NEWS_DETAIL"] = $rowNews["news_detail"];
			$arrGroupNews["NEWS_HTML"] = $rowNews["news_html"];
			$arrGroupNews["NEWS_DETAIL_SHORT"] = $lib->text_limit($rowNews["news_detail"],480);
			$arrGroupNews["PATH_IMG_HEADER"] = $rowNews["path_img_header"];
			$arrGroupNews["LINK_News_MORE"] = $rowNews["link_news_more"];
			$arrGroupNews["CREATE_DATE"] = $lib->convertdate($rowNews["create_date"],'d m Y',true); 
			$arrGroupNews["UPDATE_DATE"] = $lib->convertdate($rowNews["update_date"],'d m Y',true);  
			$arrGroupNews["PATH_IMG_1"] = $rowNews["img_gallery_1"];
			$arrGroupNews["PATH_IMG_2"] = $rowNews["img_gallery_2"];
			$arrGroupNews["PATH_IMG_3"] = $rowNews["img_gallery_3"];
			$arrGroupNews["PATH_IMG_4"] = $rowNews["img_gallery_4"];
			$arrGroupNews["PATH_IMG_5"] = $rowNews["img_gallery_5"];
			$arrGroupNews["PATH_FILE"] = $rowNews["file_upload"];
			$arrGroupNews["str_count"] = $str_count;
			
			$arrayGroup[] = $arrGroupNews;
		}
		$arrayResult["NEWS_DATA"] = $arrayGroup;
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
