<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['pin'],$dataComing)){
	$checkResign = $conoracle->prepare("SELECT resign_status FROM mbmembmaster WHERE member_no = :member_no");
	$checkResign->execute([':member_no' => $payload["member_no"]]);
	$rowResign = $checkResign->fetch(PDO::FETCH_ASSOC);
	if($rowResign["RESIGN_STATUS"] == '1'){
		$updateStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = '-6' WHERE member_no = :member_no");
		$updateStatus->execute([':member_no' => $payload["member_no"]]);
		$arrayResult['RESPONSE_CODE'] = "WS0051";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
		
	}
	if($dataComing["channel"] == "mobile_app" && isset($dataComing["is_root"])){
		if($dataComing["is_root"] == "1"){
			$insertBlackList = $conmysql->prepare("INSERT INTO gcdeviceblacklist(unique_id,member_no,type_blacklist,new_id_token,old_id_token)
												VALUES(:unique_id,:member_no,'1',:id_token,:id_token)");
			if($insertBlackList->execute([
				':unique_id' => $dataComing["unique_id"],
				':member_no' => $payload["member_no"],
				':id_token' => $payload["id_token"]
			])){
				$arrayResult['RESPONSE_CODE'] = "WS0069";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$updateBlacklist = $conmysql->prepare("UPDATE gcdeviceblacklist SET is_blacklist = '0' WHERE unique_id = :unique_id and type_blacklist = '1'");
			$updateBlacklist->execute([':unique_id' => $dataComing["unique_id"]]);
		}
	}
	if(isset($dataComing["flag"]) && $dataComing["flag"] == "TOUCH_ID"){
		$is_refreshToken_arr = $auth->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
		$lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]),$jwt_token,$config["SECRET_KEY_JWT"]);
		if(!$is_refreshToken_arr){
			$arrayResult['RESPONSE_CODE'] = "WS0014";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(401);
			require_once('../../include/exit_footer.php');
			
		}
		$arrayResult['NEW_TOKEN'] = $is_refreshToken_arr["ACCESS_TOKEN"];
		$arrayResult['RESULT'] = TRUE;		
		if ($forceNewSecurity == true) {
			$newArrayResult = array();
			$newArrayResult['ENC_TOKEN'] = $lib->generate_jwt_token($arrayResult, $jwt_token, $config["SECRET_KEY_JWT"]);
			$arrayResult = array();
			$arrayResult = $newArrayResult;
		}

		require_once('../../include/exit_footer.php');
		
	}
	$checkPinNull = $conmysql->prepare("SELECT pin,account_status FROM gcmemberaccount WHERE member_no = :member_no and account_status IN('1','-9')");
	$checkPinNull->execute([':member_no' => $payload["member_no"]]);
	$rowPinNull = $checkPinNull->fetch(PDO::FETCH_ASSOC);
	if(isset($rowPinNull["pin"])){
		if(password_verify($dataComing["pin"], $rowPinNull['pin'])){
			if($rowPinNull["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$is_refreshToken_arr = $auth->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]),$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "WS0014";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(401);
				require_once('../../include/exit_footer.php');
				
			}
			$arrayResult['NEW_TOKEN'] = $is_refreshToken_arr["ACCESS_TOKEN"];
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':ip_address' => $dataComing["ip_address"]
			];
			$log->writeLog('use_application',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;		
			
			if ($forceNewSecurity == true) {
				$newArrayResult = array();
				$newArrayResult['ENC_TOKEN'] = $lib->generate_jwt_token($arrayResult, $jwt_token, $config["SECRET_KEY_JWT"]);
				$arrayResult = array();
				$arrayResult = $newArrayResult;
			}

			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0011";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		if(strtolower($lib->mb_str_pad($dataComing["pin"])) == $payload["member_no"]){
			$arrayResult['RESPONSE_CODE'] = "WS0057";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$pin_split = str_split($dataComing["pin"]);
		$duplicateDigit = $func->getConstant('check_duplicate_pin');
		$sequestDigit = $func->getConstant('pin_sequest_digit');
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
		$pin = password_hash($dataComing["pin"], PASSWORD_DEFAULT);
		$updatePin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = :pin WHERE member_no = :member_no");
		if($updatePin->execute([
			':pin' => $pin,
			':member_no' => $payload["member_no"]
		])){
			if($rowPinNull["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$is_refreshToken_arr = $auth->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,
			$lib->fetch_payloadJWT($access_token,$jwt_token,$config["SECRET_KEY_JWT"]),$jwt_token,$config["SECRET_KEY_JWT"]);
			if(!$is_refreshToken_arr){
				$arrayResult['RESPONSE_CODE'] = "WS0014";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(401);
				require_once('../../include/exit_footer.php');
				
			}
			$arrayResult['NEW_TOKEN'] = $is_refreshToken_arr["ACCESS_TOKEN"];
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':ip_address' => $dataComing["ip_address"]
			];
			$log->writeLog('use_application',$arrayStruc);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1009",
				":error_desc" => "ตั้ง Pin ไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถตั้ง Pin ได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updatePin->queryString."\n"."Param => ". json_encode([
				':pin' => $pin,
				':member_no' => $payload["member_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1009";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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
