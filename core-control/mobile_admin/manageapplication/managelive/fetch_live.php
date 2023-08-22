<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managelive')){
		$fetchLive = $conmysql->prepare("SELECT id_live, live_url, live_title, update_date, is_use, update_by FROM gclive WHERE is_use <> '-9'");
								
		$fetchLive->execute();
		$arrayGroup = array();
		while($rowbg = $fetchLive->fetch(PDO::FETCH_ASSOC)){
			$arrGroupNews = array();
			$arrGroupNews["ID_LIVE"] = $rowbg["id_live"];
			$arrGroupNews["LIVE_URL"] = $rowbg["live_url"];
			$arrGroupNews["LIVE_TITLE"] = $rowbg["live_title"];
			$arrGroupNews["UPDATE_DATE"] = $rowbg["update_date"];
			$arrGroupNews["IS_USE"] = $rowbg["is_use"];
			$arrGroupNews["UPDATE_BY"] = $rowbg["update_by"];
			$arrayGroup[] = $arrGroupNews;
		}
		
		$arrayResult["LIVE_DATA"] = $arrayGroup;
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