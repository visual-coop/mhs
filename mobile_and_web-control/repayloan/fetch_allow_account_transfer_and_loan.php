<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrGroupAccFav = array();
		$arrayDept = array();
		$arrLoanGrp = array();
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_payloan = '1'");
		$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccAllowTrans->rowCount() > 0){
			while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
				$arrayAcc[] = "'".$rowAccAllow["deptaccount_no"]."'";
			}
			// $arrayResult['arrayAcc'] = $arrayAcc;
			// $arrayResult['RESULT'] = FALSE;
			// require_once('../../include/exit_footer.php');
			$getDataBalAcc = $conoracle->prepare("SELECT DPM.DEPTACCOUNT_NO,DPM.DEPTACCOUNT_NAME,DPT.DEPTTYPE_DESC,DPM.WITHDRAWABLE_AMT AS PRNCBAL,DPM.DEPTTYPE_CODE
												FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
												WHERE dpm.deptaccount_no IN(".implode(',',$arrayAcc).") and dpm.deptclose_status = 0
												ORDER BY dpm.deptaccount_no ASC");
			$getDataBalAcc->execute();
			
			
			while($rowDataAccAllow = $getDataBalAcc->fetch(PDO::FETCH_ASSOC)){
				$checkAccJoint = $conmysql->prepare("SELECT deptaccount_no FROM gcdeptaccountjoint WHERE deptaccount_no = :deptaccount_no and is_joint = '1'");
				$checkAccJoint->execute([':deptaccount_no' => TRIM($rowDataAccAllow["DEPTACCOUNT_NO"])]);
				if($checkAccJoint->rowCount() > 0){
				}else{
					$arrAccAllow = array();
					$checkDep = $cal_dep->getSequestAmt($rowDataAccAllow["DEPTACCOUNT_NO"]);
					if($checkDep["CAN_WITHDRAW"]){
						$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAllow["DEPTACCOUNT_NO"];
						$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAllow["DEPTACCOUNT_NO"],$formatDept);
						$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccAllow["DEPTACCOUNT_NO_FORMAT"],$formatDeptHidden);
						$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAllow["DEPTACCOUNT_NAME"]);
						$arrAccAllow["DEPT_TYPE"] = $rowDataAccAllow["DEPTTYPE_DESC"];
						$arrAccAllow["BALANCE"] = $cal_dep->getWithdrawable($rowDataAccAllow["DEPTACCOUNT_NO"]) - $checkDep["SEQUEST_AMOUNT"];
						$arrAccAllow["BALANCE_FORMAT"] = number_format($arrAccAllow["BALANCE"],2);
						$arrGroupAccAllow[] = $arrAccAllow;
					}
				}
			}
			$fetchLoanRepay = $conoracle->prepare("SELECT LNT.LOANTYPE_DESC,LNM.LOANCONTRACT_NO,LNM.PRINCIPAL_BALANCE,LNM.PERIOD_PAYAMT,LNM.LAST_PERIODPAY,LNM.LOANTYPE_CODE,
													LNM.LASTCALINT_DATE,LNM.LOANPAYMENT_TYPE,LNM.INTEREST_RETURN,LNM.RKEEP_PRINCIPAL
													FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.LOANTYPE_CODE = lnt.LOANTYPE_CODE 
													WHERE member_no = :member_no and contract_status > 0 and contract_status <> 8");
			$fetchLoanRepay->execute([':member_no' => $member_no]);
			while($rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC)){
				$interest = 0;
				$arrLoan = array();
				$interest = $cal_loan->calculateIntAPI($rowLoan["LOANCONTRACT_NO"]);
				$arrLoan['TT_II'] = $interest;
				if($interest["INT_PAYMENT"] > 0){
					$arrLoan["INT_BALANCE"] = $interest["INT_PAYMENT"];
				}
				if(file_exists(__DIR__.'/../../resource/loan-type/'.$rowLoan["LOANTYPE_CODE"].'.png')){
					$arrLoan["LOAN_TYPE_IMG"] = $config["URL_SERVICE"].'resource/loan-type/'.$rowLoan["LOANTYPE_CODE"].'.png?v='.date('Ym');
				}else{
					$arrLoan["LOAN_TYPE_IMG"] = null;
				}
				$arrLoan["LOAN_TYPE"] = $rowLoan["LOANTYPE_DESC"];
				$arrLoan["CONTRACT_NO"] = $rowLoan["LOANCONTRACT_NO"];
				$arrLoan["BALANCE"] = number_format($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"],2);
				$arrLoan["SUM_BALANCE"] = number_format(($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"]) + $interest["INT_PAYMENT"],2);
				$arrLoan["PERIOD_ALL"] = number_format($rowLoan["PERIOD_PAYAMT"],0);
				$arrLoan["PERIOD_BALANCE"] = number_format($rowLoan["LAST_PERIODPAY"],0);
				$arrLoanGrp[] = $arrLoan;
			}
			
			$getAccFav = $conmysql->prepare("SELECT fav_refno,name_fav,from_account,destination FROM gcfavoritelist WHERE member_no = :member_no and flag_trans = 'LPM' and is_use = '1'");
			$getAccFav->execute([':member_no' => $payload["member_no"]]);
			while($rowAccFav = $getAccFav->fetch(PDO::FETCH_ASSOC)){
				$arrFavMenu = array();
				$arrFavMenu["NAME_FAV"] = $rowAccFav["name_fav"];
				$arrFavMenu["FAV_REFNO"] = $rowAccFav["fav_refno"];
				$arrFavMenu["DESTINATION"] = $rowAccFav["destination"];
				$arrFavMenu["DESTINATION_FORMAT"] = $rowAccFav["destination"];
				$arrFavMenu["DESTINATION_HIDDEN"] = $rowAccFav["destination"];
				if(isset($rowAccFav["from_account"])) {
					$arrFavMenu["FROM_ACCOUNT"] = $rowAccFav["from_account"];
					$arrFavMenu["FROM_ACCOUNT_FORMAT"] = $lib->formataccount($rowAccFav["from_account"],$func->getConstant('dep_format'));
					$arrFavMenu["FROM_ACCOUNT_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccFav["from_account"],$func->getConstant('hidden_dep'));
				}
				$arrGroupAccFav[] = $arrFavMenu;
			}
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
					$arrGroupAccBind[] = $arrAccBind;
				}
			}
			if(sizeof($arrGroupAccBind) > 0){
				// $arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['BANK_ACCOUNT_ALLOW'] = $arrGroupAccBind;
				$arrayResult['LOAN'] = $arrLoanGrp;
				$arrayResult['ACCOUNT_FAV'] = $arrGroupAccFav;
				$arrayResult['FAV_SAVE_SOURCE'] = FALSE;
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
			$arrayResult['RESPONSE_CODE'] = "WS0023";
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