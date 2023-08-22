<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','seq_no','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		if(($dataComing["memo_text_emoji_"] == "" || empty($dataComing["memo_text_emoji_"])) && ($dataComing["memo_icon_path"] == "" || empty($dataComing["memo_icon_path"]))
		&& $dataComing["memo_text_emoji_"] != '0'){
			$arrayResult['RESPONSE_CODE'] = "WS4004";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
			require_once('../../include/exit_footer.php');
			
		}
		$updateMemoDept = $conmysql->prepare("UPDATE gcmemodept SET memo_text = :memo_text,memo_icon_path = :memo_icon_path
												WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
		if($updateMemoDept->execute([
			':memo_text' => $dataComing["memo_text_emoji_"] == "" ? null : $dataComing["memo_text_emoji_"],
			':memo_icon_path' => $dataComing["memo_icon_path"] == "" ? null : $dataComing["memo_icon_path"],
			':deptaccount_no' => $account_no,
			':seq_no' => $dataComing["seq_no"]
		]) && $updateMemoDept->rowCount() > 0){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$insertMemoDept = $conmysql->prepare("INSERT INTO gcmemodept(memo_text,memo_icon_path,deptaccount_no,seq_no) 
													VALUES(:memo_text,:memo_icon_path,:deptaccount_no,:seq_no)");
			if($insertMemoDept->execute([
				':memo_text' => $dataComing["memo_text_emoji_"] == "" ? null : $dataComing["memo_text_emoji_"],
				':memo_icon_path' => $dataComing["memo_icon_path"] == "" ? null : $dataComing["memo_icon_path"],
				':deptaccount_no' => $account_no,
				':seq_no' => $dataComing["seq_no"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1005",
					":error_desc" => "เพิ่มบันทึกช่วยจำไม่ได้ "."\n".json_encode($dataComing),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "เพิ่มบันทึกช่วยจำไม่ได้เพราะ Insert ลงตาราง gcmemodept ไม่ได้ "."\n"."Query => ".$insertMemoDept->queryString."\n"."Param =>".json_encode([
					':memo_text' => $dataComing["memo_text_emoji_"] == "" ? null : $dataComing["memo_text_emoji_"],
					':memo_icon_path' => $dataComing["memo_icon_path"] == "" ? null : $dataComing["memo_icon_path"],
					':deptaccount_no' => $account_no,
					':seq_no' => $dataComing["seq_no"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1005";
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