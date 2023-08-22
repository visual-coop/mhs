<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','encode_avatar','channel'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		
		$member_no = $payload["member_no"];
		$encode_avatar = $dataComing["encode_avatar"];
		$destination = __DIR__.'/../../resource/avatar/'.$member_no;
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createAvatar = $lib->base64_to_img($encode_avatar,$file_name,$destination,$webP);
		if($createAvatar == 'oversize'){
			$arrayResult['RESPONSE_CODE'] = "WS0008";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}else{
			if($createAvatar){
				$path_avatar = '/resource/avatar/'.$member_no.'/'.$createAvatar["normal_path"];
				$insertIntoInfo = $conmysql->prepare("UPDATE gcmemberaccount SET path_avatar = :path_avatar,upload_from_channel = :channel,upload_date = NOW()
														WHERE member_no = :member_no");
				if($insertIntoInfo->execute([
					':path_avatar' => $path_avatar,
					':channel' => $dataComing["channel"],
					':member_no' => $member_no
				])){
					$arrayResult['PATH_AVATAR'] = $config["URL_SERVICE"].$path_avatar;
					$arrayResult['PATH_AVATAR_WEBP'] = $config["URL_SERVICE"].'/resource/avatar/'.$member_no.'/'.$createAvatar["webP_path"];
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1008",
						":error_desc" => "อัพโหลดรูปโปรไฟล์ไม่ได้ Path => ".$path_avatar."\n".json_encode($dataComing),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "อัพโหลดรูปโปรไฟล์ไม่ได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$insertIntoInfo->queryString."\n"."Param => ". json_encode([
						':path_avatar' => $path_avatar,
						':channel' => $dataComing["channel"],
						':member_no' => $member_no
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1008";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0007";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}
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