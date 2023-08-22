<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrGroupAccFav = array();
		$arrayDept = array();
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and (gad.allow_deposit_inside = '1' OR gad.allow_withdraw_inside = '1')");
		$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccAllowTrans->rowCount() > 0){
			while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
				$arrayDept[] = $rowAccAllow["deptaccount_no"];
			}
			$getAllAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.depttype_code,dpm.PRNCBAL,
											dpm.sequest_amount,dpm.sequest_status,dpt.minprncbal,dpm.CHECKPEND_AMT
											FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
											WHERE dpm.deptclose_status = '0' and dpm.member_no = :member_no
											ORDER BY dpm.deptaccount_no");
			$getAllAcc->execute([':member_no' => $member_no]);
			while($rowDataAccAll = $getAllAcc->fetch(PDO::FETCH_ASSOC)){
				$fetchConstantAllowDept = $conmysql->prepare("SELECT allow_deposit_inside,allow_withdraw_inside FROM gcconstantaccountdept 
															WHERE dept_type_code = :dept_type_code");
				$fetchConstantAllowDept->execute([
					':dept_type_code' => $rowDataAccAll["DEPTTYPE_CODE"]
				]);
				$rowContAllow = $fetchConstantAllowDept->fetch(PDO::FETCH_ASSOC);
				if(in_array($rowDataAccAll["DEPTACCOUNT_NO"],$arrayDept)){
					$arrAccAllow = array();
					if(file_exists(__DIR__.'/../../resource/dept-type/'.$rowDataAccAll["DEPTTYPE_CODE"].'.png')){
						$arrAccAllow["DEPT_TYPE_IMG"] = $config["URL_SERVICE"].'resource/dept-type/'.$rowDataAccAll["DEPTTYPE_CODE"].'.png?v='.date('Ym');
					}else{
						$arrAccAllow["DEPT_TYPE_IMG"] = null;
					}
					$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAll["DEPTACCOUNT_NO"];
					$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAll["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
					$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowDataAccAll["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
					$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAll["DEPTACCOUNT_NAME"]);
					$arrAccAllow["DEPT_TYPE"] = $rowDataAccAll["DEPTTYPE_DESC"];
					$arrAccAllow["CAN_DEPOSIT"] = $rowContAllow["allow_deposit_inside"] ?? '0';
					$arrAccAllow["CAN_WITHDRAW"] = $rowContAllow["allow_withdraw_inside"] ?? '0';
					if($rowDataAccAll["SEQUEST_STATUS"] == '1'){
						$arrAccAllow["BALANCE"] = $rowDataAccAll["PRNCBAL"] - $rowDataAccAll["SEQUEST_AMOUNT"] - $rowDataAccAll["MINPRNCBAL"] - $rowDataAccAll["CHECKPEND_AMT"];
					}else{
						$arrAccAllow["BALANCE"] = $rowDataAccAll["PRNCBAL"] - $rowDataAccAll["MINPRNCBAL"];
					}
					$arrAccAllow["BALANCE_DEST"] = number_format($rowDataAccAll["PRNCBAL"],2);
					$arrAccAllow["BALANCE_FORMAT"] = number_format($rowDataAccAll["PRNCBAL"],2);
					$arrGroupAccAllow[] = $arrAccAllow;
				}
			}
			if($dataComing["menu_component"] == 'TransferDepInsideCoop'){
				$getAccFav = $conmysql->prepare("SELECT fav_refno,name_fav,destination FROM gcfavoritelist WHERE member_no = :member_no and flag_trans = 'TRN'");
				$getAccFav->execute([':member_no' => $payload["member_no"]]);
				while($rowAccFav = $getAccFav->fetch(PDO::FETCH_ASSOC)){
					$arrFavMenu = array();
					$arrFavMenu["NAME_FAV"] = $rowAccFav["name_fav"];
					$arrFavMenu["FAV_REFNO"] = $rowAccFav["fav_refno"];
					$arrFavMenu["DESTINATION"] = $rowAccFav["destination"];
					$arrFavMenu["DESTINATION_FORMAT"] = $lib->formataccount($rowAccFav["destination"],$func->getConstant('dep_format'));
					$arrFavMenu["DESTINATION_HIDDEN"] = $lib->formataccount_hidden($rowAccFav["destination"],$func->getConstant('hidden_dep'));
					$arrGroupAccFav[] = $arrFavMenu;
				}
			}
			if(sizeof($arrGroupAccAllow) > 0 || sizeof($arrGroupAccFav) > 0){
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['ACCOUNT_FAV'] = $arrGroupAccFav;
				$arrayResult["FORMAT_DEPT"] = $func->getConstant('dep_format');
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
		":error_desc" => "??? Argument ???????? "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "???? ".$filename." ??? Argument ????????????? "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>