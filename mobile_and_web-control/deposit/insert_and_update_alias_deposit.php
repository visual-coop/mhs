<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		if(($dataComing["base64_img"] == "" || empty($dataComing["base64_img"])) && ($dataComing["alias_name_emoji_"] == "" || empty($dataComing["alias_name_emoji_"]))
		&& $dataComing["alias_name_emoji_"] != '0'){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS4004",
				":error_desc" => "ส่ง Argument มาไม่ครบ ".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS4004";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
			require_once('../../include/exit_footer.php');
			
		}
		$arrExecute = array();
		if(isset($dataComing["base64_img"]) && $dataComing["base64_img"] != ""){
			$encode_avatar = $dataComing["base64_img"];
			$destination = __DIR__.'/../../resource/alias_account_dept';
			$file_name = $account_no;
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
				if(!$createAvatar){
					$arrayResult['RESPONSE_CODE'] = "WS0007";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}
			$path_alias_img = '/resource/alias_account_dept/'.$createAvatar["normal_path"].'?v='.$lib->randomText('all',6);
			$arrExecute["path_alias_img"] = $path_alias_img;
		}
		if(isset($dataComing["alias_name_emoji_"]) && $dataComing["alias_name_emoji_"] != ""){
			$arrExecute["alias_name"] = $dataComing["alias_name_emoji_"];
		}
		$arrExecute["deptaccount_no"] = $account_no;
		$updateMemoDept = $conmysql->prepare("UPDATE gcdeptalias SET update_date = NOW(),".(isset($dataComing["alias_name_emoji_"]) && $dataComing["alias_name_emoji_"] != "" ? "alias_name = :alias_name," : null)."deptaccount_no = :deptaccount_no
												".(isset($dataComing["base64_img"]) && $dataComing["base64_img"] != "" ? ",path_alias_img = :path_alias_img" : null)." 
												WHERE deptaccount_no = :deptaccount_no");
		if($updateMemoDept->execute($arrExecute) && $updateMemoDept->rowCount() > 0){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$insertMemoDept = $conmysql->prepare("INSERT INTO gcdeptalias(alias_name,path_alias_img,deptaccount_no)
													VALUES(:alias_name,:path_alias_img,:deptaccount_no)");
			if($insertMemoDept->execute([
				':alias_name' => $dataComing["alias_name_emoji_"] == "" ? null : $dataComing["alias_name_emoji_"],
				':path_alias_img' => $path_alias_img ?? null,
				':deptaccount_no' => $account_no
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1027",
					":error_desc" => "เพิ่มชื่อเล่นบัญชีไม่ได้ "."\n".json_encode($dataComing),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "เพิ่มชื่อเล่นบัญชีไม่ได้เพราะ Insert ลงตาราง gcdeptalias ไม่ได้ "."\n"."Query => ".$insertMemoDept->queryString."\n"." Param =>".json_encode([
					':alias_name' => $dataComing["alias_name_emoji_"] == "" ? null : $dataComing["alias_name_emoji_"],
					':path_alias_img' => $path_alias_img ?? null,
					':deptaccount_no' => $account_no
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1027";
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