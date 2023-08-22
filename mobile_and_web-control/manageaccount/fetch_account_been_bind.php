<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.deptaccount_no_bank,gpl.type_palette,gpl.color_deg,gpl.color_text,gpl.color_main,gba.id_bindaccount,gba.deptaccount_no_coop,gba.sigma_key,
													gpl.color_secon,csb.bank_short_name,csb.bank_logo_path,csb.bank_format_account,csb.bank_format_account_hide,gba.bindaccount_status,
													gba.bank_account_name,gba.bank_account_name_en,csb.bank_short_ename,csb.bank_code,gba.account_payfee
													FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
													LEFT JOIN gcpalettecolor gpl ON csb.id_palette = gpl.id_palette and gpl.is_use = '1'
													WHERE gba.member_no = :member_no and gba.bindaccount_status NOT IN('8','-9')");
		$fetchAccountBeenBind->execute([
			':member_no' => $payload["member_no"]
		]);
		$arrBindAccount = array();
		while($rowAccountBind = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC)){
			if($rowAccountBind["bank_code"] != "999"){
				$fetchAccountBeenAllow = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction WHERE deptaccount_no = :deptaccount_no and is_use <> '-9'");
				$fetchAccountBeenAllow->execute([':deptaccount_no' =>  $rowAccountBind["deptaccount_no_coop"]]);
				$getDetailAcc = $conoracle->prepare("SELECT deptaccount_name FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no and deptclose_status = 0");
				$getDetailAcc->execute([':deptaccount_no' => $rowAccountBind["deptaccount_no_coop"]]);
				$rowDetailAcc = $getDetailAcc->fetch(PDO::FETCH_ASSOC);
				$arrAccount = array();
				$arrAccount["DEPTACCOUNT_NO_BANK"] = $lib->formataccount($rowAccountBind["deptaccount_no_bank"],$rowAccountBind["bank_format_account"]);
				$arrAccount["DEPTACCOUNT_NO_BANK_HIDE"] = $lib->formataccount_hidden($rowAccountBind["deptaccount_no_bank"],$rowAccountBind["bank_format_account_hide"]);
				$arrAccount["DEPTACCOUNT_NO_COOP"] = $lib->formataccount($rowAccountBind["deptaccount_no_coop"],$func->getConstant('dep_format'));
				$arrAccount["DEPTACCOUNT_NO_COOP_HIDE"] = $lib->formataccount_hidden($rowAccountBind["deptaccount_no_coop"],$func->getConstant('hidden_dep'));
			}else{
				$arrAccount["DEPTACCOUNT_NO_BANK"] = $lib->formatcitizen($rowAccountBind["citizen_id"]);
				$arrAccount["DEPTACCOUNT_NO_BANK_HIDE"] = $lib->formatcitizen($rowAccountBind["citizen_id"]);
			}
			if(isset($rowAccountBind["type_palette"])){
				if($rowAccountBind["type_palette"] == '2'){
					$arrAccount["BANNER_COLOR"] = $rowAccountBind["color_deg"]."|".$rowAccountBind["color_main"].",".$rowAccountBind["color_secon"];
				}else{
					$arrAccount["BANNER_COLOR"] = "90|".$rowAccountBind["color_main"].",".$rowAccountBind["color_main"];
				}
				$arrAccount["BANNER_TEXT_COLOR"] = $rowAccountBind["color_text"];
			}else{
				$arrAccount["BANNER_COLOR"] = $config["DEFAULT_BANNER_COLOR_DEG"]."|".$config["DEFAULT_BANNER_COLOR_MAIN"].",".$config["DEFAULT_BANNER_COLOR_SECON"];
				$arrAccount["BANNER_TEXT_COLOR"] = $config["DEFAULT_BANNER_COLOR_TEXT"];
			}
			$arrAccount["ICON_BANK"] = $config['URL_SERVICE'].$rowAccountBind["bank_logo_path"];
			$explodePathBankLOGO = explode('.',$rowAccountBind["bank_logo_path"]);
			$arrAccount["ICON_BANK_WEBP"] = $config['URL_SERVICE'].$explodePathBankLOGO[0].'.webp';
			$arrAccount["BANK_NAME"] = $rowAccountBind["bank_short_name"];
			$arrAccount["BANK_CODE"] = $rowAccountBind["bank_code"];
			$arrAccount["ID_BINDACCOUNT"] = $rowAccountBind["id_bindaccount"];
			$arrAccount["BANK_SHORT_NAME"] = $rowAccountBind["bank_short_ename"];
			/*if(isset($rowAccountBind["account_payfee"])){
				$arrAccount["DEPTACCOUNT_NO_PAYFEE"] = $lib->formataccount($rowAccountBind["account_payfee"],$func->getConstant('dep_format'));
			}*/
			$arrAccount["SIGMA_KEY"] = $rowAccountBind["sigma_key"];
			$arrAccount["BIND_STATUS"] = $rowAccountBind["bindaccount_status"];
			$arrAccount["ACCOUNT_COOP_NAME"] = $lang_locale == 'th' ? $rowAccountBind["bank_account_name"] : $rowAccountBind["bank_account_name_en"];
			$arrBindAccount[] = $arrAccount;
		}
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
		$arrayResult['BIND_ACCOUNT'] = $arrBindAccount;
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
