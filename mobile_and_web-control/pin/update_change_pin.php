<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['pin','menu_component'],$dataComing)){
	if(($func->check_permission($payload["user_type"],'ChangePin' ,'ChangePin') && ($dataComing["menu_component"] == 'Pin') || 
	$func->check_permission($payload["user_type"],$dataComing["menu_component"] ,'ChangePin') )){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if(strtolower($lib->mb_str_pad($dataComing["pin"])) == $member_no){
			$arrayResult['RESPONSE_CODE'] = "WS0057";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$duplicateDigit = $func->getConstant('check_duplicate_pin');
		$sequestDigit = $func->getConstant('pin_sequest_digit');
		$pin_split = str_split($dataComing["pin"]);
		$countSeqNumber = 1;
		$countReverseSeqNumber = 1;
		foreach($pin_split as $key => $value){
			if($duplicateDigit == "0"){
				if(($value == $dataComing["pin"][($key - 1) < 0 ? 7 : $key - 1] && $value == $dataComing["pin"][$key + 1]) || 
				($value == $dataComing["pin"][($key - 1) < 0 ? 7 : $key - 1] && $value == $dataComing["pin"][($key - 2) < 0 ? 7 : $key - 2])){
					$arrayResult['RESPONSE_CODE'] = "WS0057";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}
			if($key < strlen($dataComing["pin"]) - 1){
				if($value == ($dataComing["pin"][$key + 1] - 1)){
					$countSeqNumber++;
				}else{
					if($countSeqNumber < 3){
						$countSeqNumber = 1;
					}
				}
				if($value - 1 == $dataComing["pin"][$key + 1]){
					$countReverseSeqNumber++;
				}else{
					if($countReverseSeqNumber < 3){
						$countReverseSeqNumber = 1;
					}
				}
			}
		}
		if($sequestDigit == "0"){
			if($countSeqNumber > 3 || $countReverseSeqNumber > 3){
				$arrayResult['RESPONSE_CODE'] = "WS0057";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}
		$updatePin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = :pin WHERE member_no = :member_no");
		if($updatePin->execute([
			':pin' => password_hash($dataComing["pin"], PASSWORD_DEFAULT),
			':member_no' => $payload["member_no"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1015",
				":error_desc" => "เปลี่ยน Pin ไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถเปลี่ยน PIN ได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updatePin->queryString."\n"."Param => ". json_encode([
				':pin' => password_hash($dataComing["pin"], PASSWORD_DEFAULT),
				':member_no' => $payload["member_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1015";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
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
