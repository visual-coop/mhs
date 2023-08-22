<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingManageDevice')){
		$arrGroupDevice = array();
		$fetchSettingDevice = $conmysql->prepare("SELECT device_name,channel,unique_id,login_date,id_token
													FROM gcuserlogin WHERE is_login = '1' and member_no = :member_no 
													GROUP BY unique_id ORDER BY id_userlogin DESC");
		$fetchSettingDevice->execute([':member_no' => $payload["member_no"]]);
		if($fetchSettingDevice->rowCount() > 0){
			while($rowSetting = $fetchSettingDevice->fetch(PDO::FETCH_ASSOC)){
				$arrDevice = array();
				$arrDevice["DEVICE_NAME"] = $rowSetting["device_name"];
				$arrDevice["CHANNEL"] = $rowSetting["channel"];
				if($rowSetting["unique_id"] == $dataComing["unique_id"]){
					$arrDevice["THIS_DEVICE"] = true;
				}
				$arrDevice["LOGIN_DATE"] = isset($rowSetting["login_date"]) ? $lib->convertdate($rowSetting["login_date"],'D m Y',true) : null;
				$arrDevice["ACCESS_DATE"] = isset($rowSetting["access_date"]) ? $lib->convertdate($rowSetting["access_date"],'D m Y',true) : null;
				$arrDevice["ID_TOKEN"] = $rowSetting["id_token"];
				$arrGroupDevice[] = $arrDevice;
			}
			$arrayResult["DEVICE"] = $arrGroupDevice;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			http_response_code(204);
			
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