<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$arrayBankGrp = array();
		$getBankAllow = $conmysql->prepare("SELECT bank_code,bank_name,bank_short_name,bank_short_ename,bank_logo_path
											FROM csbankdisplay");
		$getBankAllow->execute();
		while($rowAllow = $getBankAllow->fetch(PDO::FETCH_ASSOC)){
			$arrayBank = array();
			$arrayBank["IS_BIND"] = FALSE;
			$checkRegis = $conmysql->prepare("SELECT deptaccount_no_coop,deptaccount_no_bank,bank_account_name,bank_account_name_en FROM gcbindaccount 
											WHERE bank_code = :bank_code and member_no = :member_no and bindaccount_status = '1'");
			$checkRegis->execute([
				':bank_code' => $rowAllow["bank_code"],
				':member_no' => $payload["member_no"]
			]);
			if($checkRegis->rowCount() > 0){
				$rowRegis = $checkRegis->fetch(PDO::FETCH_ASSOC);
				$arrayBank["IS_BIND"] = TRUE;
				$arrayBank["COOP_ACCOUNT_NO"] = $rowRegis["deptaccount_no_coop"];
				$arrayBank["BANK_ACCOUNT_NO"] = $rowRegis["deptaccount_no_bank"];
				if($lang_locale == 'th'){
					$arrayBank["BANK_ACCOUNT_NAME"] = $rowRegis["bank_account_name"];
				}else{
					$arrayBank["BANK_ACCOUNT_NAME"] = $rowRegis["bank_account_name_en"];
				}
			}
			$arrayBank["BANK_CODE"] = $rowAllow["bank_code"];
			$arrayBank["BANK_NAME"] = $rowAllow["bank_name"];
			$arrayBank["BANK_SHORT_NAME"] = $rowAllow["bank_short_name"];
			$arrayBank["BANK_SHORT_ENAME"] = $rowAllow["bank_short_ename"];
			$arrayBank["BANK_LOGO_PATH"] = $config["URL_SERVICE"].$rowAllow["bank_logo_path"];
			$arrPic = explode('.',$rowAllow["bank_logo_path"]);
			$arrayBank["BANK_LOGO_PATH_WEBP"] = $config["URL_SERVICE"].$arrPic[0].'.webp';
			$arrayBankGrp[] = $arrayBank;
		}
		$arrayResult['BANK_LIST'] = $arrayBankGrp;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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