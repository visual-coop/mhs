<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
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
		
		$update_announce = $conmysql->prepare("UPDATE  gcannounce SET
																		announce_cover = :announce_cover, 
																		announce_title = :announce_title,
																		announce_detail = :announce_detail,
																		effect_date = :effect_date,
																		due_date =:due_date,
																		username = :username,
																		is_update = '1',
																		is_show_between_due = :is_show_between_due,
																		is_check = :is_check,
																		accept_text = :accept_text,
																		cancel_text = :cancel_text,
																		check_text = :check_text,
																		announce_html = :announce_html
																	WHERE id_announce = :id_announce");
		if($update_announce->execute([
			':id_announce' =>  $dataComing["id_announce"],
			':announce_title' =>  $dataComing["announce_title"],
			':announce_detail' => $dataComing["announce_detail"],
			':effect_date' =>  $dataComing["effect_date"],	
			':due_date' => (isset($dataComing["due_date"]) && $dataComing["due_date"] != null && $dataComing["due_date"] != "") ? $dataComing["due_date"] : null,
			':username' =>  $payload["username"],
			':announce_cover' =>  $pathImg ?? null,
			':is_show_between_due' => $dataComing["is_show_between_due"],
			':is_check' => (isset($dataComing["check_accept"]) && $dataComing["check_accept"] != null && $dataComing["check_accept"] != "") ? $dataComing["check_accept"] : 0,
			':accept_text' => (isset($dataComing["accept_text"]) && $dataComing["accept_text"] != null && $dataComing["accept_text"] != "") ? $dataComing["accept_text"] : null,
			':cancel_text' => (isset($dataComing["cancel_text"]) && $dataComing["cancel_text"] != null && $dataComing["cancel_text"] != "") ? $dataComing["cancel_text"] : null,
			':check_text' => (isset($dataComing["check_text"]) && $dataComing["check_text"] != null && $dataComing["check_text"] != "") ? $dataComing["check_text"] : null,
			':announce_html' => $detail_html ?? null
		])){
			$arrayResult["RESULT"] = TRUE;
			$arrayResult["announce_html"] = $dataComing["announce_html"];
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขประกาศได้ กรุณาติดต่อผู้พัฒนา ";
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
