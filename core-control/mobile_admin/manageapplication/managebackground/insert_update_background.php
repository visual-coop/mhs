<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managebackground')){
		
		$fetchBG = $conmysql->prepare("SELECT id_background,image,update_date,is_use,update_by FROM gcconstantbackground");
								
		$fetchBG->execute();
		$arrayGroup = array();
		while($rowbg = $fetchBG->fetch(PDO::FETCH_ASSOC)){
			$arrGroupBg = array();
			$arrGroupBg["ID_BACKGROUND"] = $rowbg["id_background"];
			$arrGroupBg["IMAGE"] = $rowbg["image"];
			$arrGroupBg["UPDATE_DATE"] = $rowbg["update_date"];
			$arrGroupBg["IS_USE"] = $rowbg["is_use"];
			$arrGroupBg["UPDATE_BY"] = $rowbg["update_by"];
			$arrayGroup[] = $arrGroupBg;
		}
		
			$encode_image = $dataComing["image"];
			$destination = __DIR__.'/../../../../resource/background';
			$random_text = $lib->randomText('all',6);
			$file_name = 'appbg';
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createBg = $lib->base64_to_img($encode_image,$file_name,$destination,$webP);
			if($createBg == 'oversize'){
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}else{
				if($createBg){
					if(count($arrayGroup) > 0){
						$path_bg = '/resource/background/'.$createBg["normal_path"];
						$insertIntoInfo = $conmysql->prepare("UPDATE gcconstantbackground SET image=:path_bg,update_by=:username,is_use = '1' WHERE id_background = :id_background");
						if($insertIntoInfo->execute([
							':path_bg' => $path_bg.'?'.$random_text,
							'username' => $payload["username"],
							'id_background' => $arrayGroup[0]["ID_BACKGROUND"]
						])){
							$arrayResult['RESULT'] = TRUE;
							require_once('../../../../include/exit_footer.php');
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "อัพโหลดรุปภาพไม่สำเร็จ";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
					}else{
						$path_bg = '/resource/background/'.$createBg["normal_path"];
						$insert_news = $conmysql->prepare("INSERT INTO gcconstantbackground(image,update_by) VALUES (:path_bg,:username)");
						if($insert_news->execute([
								':path_bg' => $path_bg,
								':username' => $payload["username"]
						])){
							$arrayResult['RESULT'] = TRUE;
							require_once('../../../../include/exit_footer.php');
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "อัพโหลดรุปภาพไม่สำเร็จ";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
					}
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "อัพโหลดรุปภาพไม่สำเร็จ";
					$arrayResult['RESULT'] = FALSE;
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