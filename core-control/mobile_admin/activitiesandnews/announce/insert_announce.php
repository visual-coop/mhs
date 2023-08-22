<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','effect_date','priority','flag_granted'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','announce')){
		$pathImg = null;
		if(isset($dataComing["announce_cover"]) && $dataComing["announce_cover"] != null){
			$destination = __DIR__.'/../../../../resource/announce';
			$file_name = $lib->randomText('all',6);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_img($dataComing["announce_cover"],$file_name,$destination,null);
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}else{
				if($createImage){
					$pathImg = $config["URL_SERVICE"]."resource/announce/".$createImage["normal_path"];
				}else{
					$pathImg= $dataComing["announce_cover"];
				}
			}
		}
		
		if(isset($dataComing["news_html_root_"]) && $dataComing["news_html_root_"] != null){
		$detail_html = '<!DOCTYPE HTML>
								<html>
								<head>
								<style>
								img {
									max-width: 100%;
								}
								</style>
							  <meta charset="UTF-8">
							  <meta name="viewport" content="width=device-width, initial-scale=1.0">
							  '.$dataComing["news_html_root_"].'
							  </body>
								</html>';
		}
		
		$insert_announce = $conmysql->prepare("INSERT INTO gcannounce(
																	announce_cover,
																	announce_title,
																	announce_detail,
																	effect_date,
																	due_date,
																	priority,
																	flag_granted,
																	is_check,
																	check_text,
																	accept_text,
																	cancel_text,
																	username,
																	is_show_between_due,
																	announce_html)
																VALUES(
																	:announce_cover,
																	:announce_title,
																	:announce_detail,
																	:effect_date,
																	:due_date,
																	:priority,
																	:flag_granted,
																	:is_check,
																	:check_text,
																	:accept_text,
																	:cancel_text,
																	:username,
																	:is_show_between_due,
																	:detail_html)");
		if($insert_announce->execute([
			':announce_title' =>  $dataComing["announce_title"],
			':announce_detail' => $dataComing["announce_detail"],
			':effect_date' =>  $dataComing["effect_date"],	
			':due_date' =>  isset($dataComing["due_date"]) && $dataComing["due_date"] != '' ? $dataComing["due_date"] : null,
			':priority' =>  $dataComing["priority"],
			':flag_granted' =>  $dataComing["flag_granted"],
			':is_check' =>  isset($dataComing["is_check"]) && $dataComing["is_check"] != '' ? $dataComing["is_check"] : '0',
			':check_text' =>  isset($dataComing["check_text"]) && $dataComing["check_text"] != "" ? $dataComing["check_text"] : null,
			':accept_text' =>  $dataComing["accept_text"],
			':cancel_text' =>  $dataComing["cancel_text"],
			':username' =>  $payload["username"],
			':announce_cover' =>  $pathImg ?? null,
			':is_show_between_due' => $dataComing["is_show_between_due"],
			':detail_html' => $detail_html ?? null
			
		])){
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแจ้งประกาศได้ กรุณาติดต่อผู้พัฒนา ";
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
