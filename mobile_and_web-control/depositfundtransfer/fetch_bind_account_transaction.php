<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$arrGroupAccBind = array();
		$fetchBindAccount = $conmysql->prepare("SELECT gba.id_bindaccount,gba.sigma_key,gba.deptaccount_no_coop,gba.deptaccount_no_bank,csb.bank_logo_path,gba.bank_code,
												csb.bank_format_account,csb.bank_format_account_hide,csb.bank_short_name
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status = '1' ORDER BY gba.deptaccount_no_coop");
		$fetchBindAccount->execute([':member_no' => $payload["member_no"]]);
		if($fetchBindAccount->rowCount() > 0){
			while($rowAccBind = $fetchBindAccount->fetch(PDO::FETCH_ASSOC)){
				$arrAccBind = array();
				$arrAccBind["ID_BINDACCOUNT"] = $rowAccBind["id_bindaccount"];
				$arrAccBind["SIGMA_KEY"] = $rowAccBind["sigma_key"];
				$arrAccBind["BANK_NAME"] = $rowAccBind["bank_short_name"];
				$arrAccBind["BANK_CODE"] = $rowAccBind["bank_code"];
				$arrAccBind["BANK_LOGO"] = $config["URL_SERVICE"].$rowAccBind["bank_logo_path"];
				$explodePathLogo = explode('.',$rowAccBind["bank_logo_path"]);
				$arrAccBind["BANK_LOGO_WEBP"] = $config["URL_SERVICE"].$explodePathLogo[0].'.webp';
				if($rowAccBind["bank_code"] == '025'){
					$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $rowAccBind["deptaccount_no_bank"];
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $rowAccBind["deptaccount_no_bank"];
				}else{
					$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account"]);
					$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account_hide"]);
				}
				$arrGroupAccBind["BIND"][] = $arrAccBind;
			}
			$fetchAccountBeenAllow = $conmysql->prepare("SELECT gat.deptaccount_no 
												FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON 
												gat.id_accountconstant = gct.id_accountconstant
												WHERE gct.allow_deposit_outside = '1' and gat.member_no = :member_no and gat.is_use = '1'");
			$fetchAccountBeenAllow->execute([':member_no' =>  $payload["member_no"]]);
			if($fetchAccountBeenAllow->rowCount() > 0){
				$dep_format = $func->getConstant('dep_format');
				$dep_formathide = $func->getConstant('hidden_dep');
				while($rowAccCoop = $fetchAccountBeenAllow->fetch(PDO::FETCH_ASSOC)){
					$checkSeqAmt = $cal_dep->getSequestAmt($rowAccCoop["deptaccount_no"]);
					if($checkSeqAmt["CAN_DEPOSIT"]){
						$getDataAcc = $conoracle->prepare("SELECT TRIM(dpm.deptaccount_name) as DEPTACCOUNT_NAME,dpt.depttype_desc,dpm.depttype_code,
															dpm.PRNCBAL,dpt.minprncbal
															FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
															WHERE dpm.deptaccount_no = :deptaccount_no and dpm.deptclose_status = 0");
						$getDataAcc->execute([':deptaccount_no' => $rowAccCoop["deptaccount_no"]]);
						$rowDataAcc = $getDataAcc->fetch(PDO::FETCH_ASSOC);
						if(isset($rowDataAcc["DEPTTYPE_DESC"])){
							$arrAccCoop = array();
							$arrAccCoop["DEPTACCOUNT_NO"] = $rowAccCoop["deptaccount_no"];
							$arrAccCoop["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccCoop["deptaccount_no"],$dep_format);
							$arrAccCoop["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccCoop["DEPTACCOUNT_NO_FORMAT"],$dep_formathide);
							$arrAccCoop["ACCOUNT_NAME"] = preg_replace('/\"/','',trim($rowDataAcc["DEPTACCOUNT_NAME"]));
							$arrAccCoop["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
							$arrAccCoop["BALANCE"] = $rowDataAcc["PRNCBAL"];
							$arrAccCoop["BALANCE_FORMAT"] = number_format($arrAccCoop["BALANCE"],2);
							$arrGroupAccBind["COOP"][] = $arrAccCoop;
						}
					}
				}
			}
			if(sizeof($arrGroupAccBind["BIND"]) > 0 && sizeof($arrGroupAccBind["COOP"]) > 0){
				$arrayResult['ACCOUNT'] = $arrGroupAccBind;
				$arrayResult['SCHEDULE']["ENABLED"] = FALSE;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0021";
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
