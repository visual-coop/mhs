<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['pin'],$dataComing)){
	$checkPin = $conmysql->prepare("SELECT member_no,pin FROM gcmemberaccount WHERE member_no = :member_no");
	$checkPin->execute([
		':member_no' => $payload["member_no"]
	]);
	$rowaccount = $checkPin->fetch(PDO::FETCH_ASSOC);
	if(password_verify($dataComing["pin"], $rowaccount['pin']) || (isset($dataComing["flag"]) && $dataComing["flag"] == "TOUCH_ID")){
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
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0011";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
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
