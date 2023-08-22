<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_account_no','deptaccount_no','amt_transfer','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$fetchDataDeposit = $conmysql->prepare("SELECT csb.itemtype_wtd,csb.link_inquirywithd_coopdirect,gba.bank_code,csb.fee_withdraw,csb.bank_short_ename
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$fee_amt = $rowDataDeposit["fee_withdraw"];
		$arrInitDep = $cal_dep->initDept($deptaccount_no,$dataComing["amt_transfer"],$rowDataDeposit["itemtype_wtd"],$fee_amt);
		if($arrInitDep["RESULT"]){
			$arrRightDep = $cal_dep->depositCheckWithdrawRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"],$rowDataDeposit["bank_code"]);
			if($arrRightDep["RESULT"]){
				$amt_transfer = $dataComing["amt_transfer"];
				$getDataUser = $conmysql->prepare("SELECT citizen_id FROM gcbindaccount WHERE deptaccount_no_coop = :deptaccount_no 
													and member_no = :member_no and bindaccount_status = '1'");
				$getDataUser->execute([
					':deptaccount_no' => $payload["member_no"],
					':member_no' => $payload["member_no"]
				]);
				$rowDataUser = $getDataUser->fetch(PDO::FETCH_ASSOC);
				if($rowDataDeposit["bank_code"] == '006'){
					$dateOperC = date('c');
					$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
					$arrSendData = array();
					$arrVerifyToken = array();
					$arrVerifyToken['exp'] = time() + 300;
					$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
					$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
					$arrVerifyToken['bank_code'] = $rowDataDeposit["bank_code"];
					$arrVerifyToken['tran_date'] = $dateOper;
					$arrVerifyToken['amt_transfer'] = $amt_transfer;
					$arrVerifyToken['bank_account'] = $dataComing["bank_account_no"];
					$arrVerifyToken['citizen_id'] = $rowDataUser["citizen_id"];
					$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
					$arrSendData["verify_token"] = $verify_token;
					$arrSendData["app_id"] = $config["APP_ID"];
					$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataDeposit["link_inquirywithd_coopdirect"],$arrSendData);
					if(!$responseAPI["RESULT"]){
						$filename = basename(__FILE__, '.php');
						$arrayResult['RESPONSE_CODE'] = "WS0027";
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':deptaccount_no' => $deptaccount_no,
							':amt_transfer' => $amt_transfer,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
						];
						$log->writeLog('withdrawtrans',$arrayStruc);
						$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
						$lib->sendLineNotify($message_error);
						$func->MaintenanceMenu($dataComing["menu_component"]);
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
					$arrResponse = json_decode($responseAPI);
					if($arrResponse->RESULT){
						$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
						$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
																FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
																WHERE MB.member_no = :member_no");
						$fetchMemberName->execute([
							':member_no' => $member_no
						]);
						$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
						$arrayResult['ACCOUNT_NAME'] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
						if($fee_amt > 0){
							$arrayResult['FEE_AMT'] = $fee_amt;
							$arrayResult['FEE_AMT_FORMAT'] = number_format($fee_amt,2);
						}
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0038";
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':deptaccount_no' => $deptaccount_no,
							':amt_transfer' => $amt_transfer,
							':response_code' => $arrResponse->RESPONSE_CODE,
							':response_message' => $arrResponse->RESPONSE_MESSAGE
						];
						$log->writeLog('withdrawtrans',$arrayStruc);
						if(isset($configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}else if($rowDataDeposit["bank_code"] == '025'){
					$dateOperC = date('c');
					$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
					$arrSendData = array();
					$arrVerifyToken = array();
					$arrVerifyToken['exp'] = time() + 300;
					$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
					$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
					$arrVerifyToken['bank_code'] = $rowDataDeposit["bank_code"];
					$arrVerifyToken['tran_date'] = $dateOper;
					$arrVerifyToken['amt_transfer'] = $amt_transfer;
					$arrVerifyToken['bank_account'] = $dataComing["bank_account_no"];
					$arrVerifyToken['citizen_id'] = $rowDataUser["citizen_id"];
					$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
					$arrSendData["verify_token"] = $verify_token;
					$arrSendData["app_id"] = $config["APP_ID"];
					$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataDeposit["link_inquirywithd_coopdirect"],$arrSendData);
					if(!$responseAPI["RESULT"]){
						$filename = basename(__FILE__, '.php');
						$arrayResult['RESPONSE_CODE'] = "WS0027";
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':deptaccount_no' => $deptaccount_no,
							':amt_transfer' => $amt_transfer,
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
						];
						$log->writeLog('withdrawtrans',$arrayStruc);
						$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
						$lib->sendLineNotify($message_error);
						$func->MaintenanceMenu($dataComing["menu_component"]);
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
					$arrResponse = json_decode($responseAPI);
					if($arrResponse->RESULT){
						$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
						$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
																FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
																WHERE MB.member_no = :member_no");
						$fetchMemberName->execute([
							':member_no' => $member_no
						]);
						$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
						$arrayResult['ACCOUNT_NAME'] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
						if($fee_amt > 0){
							$arrayResult['FEE_AMT'] = $fee_amt;
							$arrayResult['FEE_AMT_FORMAT'] = number_format($fee_amt,2);
						}
						$arrayResult['SOURCE_REFNO'] = $arrResponse->SOURCE_REFNO;
						$arrayResult['ETN_REFNO'] = $arrResponse->ETN_REFNO;	
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0038";
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':deptaccount_no' => $deptaccount_no,
							':amt_transfer' => $amt_transfer,
							':response_code' => $arrResponse->RESPONSE_CODE,
							':response_message' => $arrResponse->RESPONSE_MESSAGE
						];
						$log->writeLog('withdrawtrans',$arrayStruc);
						if(isset($configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}
				if(isset($arrInitDep["PENALTY_AMT"]) && $arrInitDep["PENALTY_AMT"] > 0){
					$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
					$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
					$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
					$arrayResult['CAUTION'] = $arrayCaution;
					$arrayResult['PENALTY_AMT'] = $arrInitDep["PENALTY_AMT"];
					$arrayResult['PENALTY_AMT_FORMAT'] = number_format($arrInitDep["PENALTY_AMT"],2);
				}
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
				if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = $arrInitDep["RESPONSE_CODE"];
			if($arrInitDep["RESPONSE_CODE"] == 'WS0056'){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrInitDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
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