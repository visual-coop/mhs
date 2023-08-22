<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'News')){
		$arrayGroupNews = array();
		$fetchNews = $conmysql->prepare("SELECT news_title,news_detail,path_img_header,create_by,update_date,id_news,link_news_more,news_html,file_upload
										FROM gcnews WHERE is_use = '1' ORDER BY create_date DESC LIMIT 5");
		$fetchNews->execute();
		while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
			$arrayNews = array();
			$arrayNews["TITLE"] = $lib->text_limit($rowNews["news_title"]);
			$arrayNews["DETAIL"] = $lib->text_limit($rowNews["news_detail"],100);
			$arrayNews["DETAIL_FULL"] = $rowNews["news_detail"];
			$arrayNews["NEWS_HTML"] = $rowNews["news_html"];
			$arrayNews["IMAGE_HEADER"] = $rowNews["path_img_header"];
			$arrayNews["UPDATE_DATE"] = $lib->convertdate($rowNews["update_date"],'D m Y',true);
			$arrayNews["ID_NEWS"] = $rowNews["id_news"];
			$arrayNews["CREATE_BY"] = $rowNews["create_by"];
			$arrayNews["LINK_NEWS_MORE"] = $rowNews["link_news_more"];
			$arrayNews["FILE_UPLOAD"] = $rowNews["file_upload"];
			$arrayGroupNews[] = $arrayNews;
		}
		$arrayResult['NEWS'] = $arrayGroupNews;
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