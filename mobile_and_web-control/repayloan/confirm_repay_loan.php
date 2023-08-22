<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loancontract_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$dataComing["amt_transfer"] = number_format($dataComing["amt_transfer"],2,'.','');
		if(isset($dataComing["deptaccount_no"]) && $dataComing["deptaccount_no"] != ""){
			$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
			$arrInitDep = $cal_dep->initDept($deptaccount_no,$dataComing["amt_transfer"],'WFS');
			if($arrInitDep["RESULT"]){
				$arrRightDep = $cal_dep->depositCheckWithdrawRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
				if($arrRightDep["RESULT"]){
					if(isset($arrInitDep["PENALTY_AMT"]) && $arrInitDep["PENALTY_AMT"] > 0){
						$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
						$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
						$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
						$arrayResult['CAUTION'] = $arrayCaution;
						$arrayResult['FEE_AMT'] = $arrInitDep["PENALTY_AMT"];
						$arrayResult['FEE_AMT_FORMAT'] = number_format($arrInitDep["PENALTY_AMT"],2);
					}
					$fetchLoanRepay = $conoracle->prepare("SELECT PRINCIPAL_BALANCE,INTEREST_RETURN,RKEEP_PRINCIPAL
															FROM lncontmaster
															WHERE loancontract_no = :loancontract_no");
					$fetchLoanRepay->execute([':loancontract_no' => $dataComing["loancontract_no"]]);
					$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
					$interest = $cal_loan->calculateIntAPI($dataComing["loancontract_no"],$dataComing["amt_transfer"]);
					if($interest["INT_PAYMENT"] > 0){
						if($dataComing["amt_transfer"] < $interest["INT_PAYMENT"]){
							$interest["INT_PAYMENT"] = $dataComing["amt_transfer"];
						}else{
							$prinPay = $dataComing["amt_transfer"] - $interest["INT_PAYMENT"];
						}
						if($prinPay < 0){
							$prinPay = 0;
						}
						if($interest["INT_PAYMENT"] > 0){
							$arrayResult["PAYMENT_INT"] = $interest["INT_PAYMENT"];
						}
						$arrayResult["PAYMENT_PRIN"] = $prinPay;
					}else{
						$arrayResult["PAYMENT_PRIN"] = $dataComing["amt_transfer"];
					}
					if(number_format($dataComing["amt_transfer"],2,'.','') > number_format(($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"]) + $interest["INT_PAYMENT"],2,'.','')){
						$arrayResult['RESPONSE_CODE'] = "WS0098";
						$arrayResult['RESPONSE_MESSAGE'] = str_replace('${LOAN_BALANCE}',number_format($rowLoan["PRINCIPAL_BALANCE"],2),$configError["LOAN_BALANCE_FOR_REPAY"][0][$lang_locale]);
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
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
			$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
														FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
														WHERE MB.member_no = :member_no");
			$fetchMemberName->execute([
				':member_no' => $member_no
			]);
			$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
			$account_name_th = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			$getBankDisplay = $conmysql->prepare("SELECT cs.link_inquirydep_coopdirect,cs.bank_short_ename,gc.bank_code,cs.fee_deposit,cs.bank_short_ename,gc.account_payfee,cs.fee_deposit
													FROM gcbindaccount gc LEFT JOIN csbankdisplay cs ON gc.bank_code = cs.bank_code
													WHERE gc.sigma_key = :sigma_key and gc.bindaccount_status = '1'");
			$getBankDisplay->execute([':sigma_key' => $dataComing["sigma_key"]]);
			if($getBankDisplay->rowCount() > 0){
				$rowBankDisplay = $getBankDisplay->fetch(PDO::FETCH_ASSOC);
				if($rowBankDisplay["bank_code"] == '025'){
					$dateOperC = date('c');
					$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
					$arrVerifyToken['exp'] = time() + 300;
					$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
					$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
					$arrVerifyToken['operate_date'] = $dateOperC;
					$arrVerifyToken['amt_transfer'] = $dataComing["amt_transfer"];
					$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
					$arrSendData["verify_token"] = $verify_token;
					$arrSendData["app_id"] = $config["APP_ID"];
					$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowBankDisplay["link_inquirydep_coopdirect"],$arrSendData);
					if(!$responseAPI["RESULT"]){
						$filename = basename(__FILE__, '.php');
						$arrayResult['RESPONSE_CODE'] = "WS0027";
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':sigma_key' => $dataComing["sigma_key"],
							':amt_transfer' => $dataComing["amt_transfer"],
							':response_code' => $arrayResult['RESPONSE_CODE'],
							':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
						];
						$log->writeLog('deposittrans',$arrayStruc);
						$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
						$lib->sendLineNotify($message_error);
						$func->MaintenanceMenu($dataComing["menu_component"]);
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
					$arrResponse = json_decode($responseAPI);
					if($arrResponse->RESULT){
						$arrayResult['bank_code'] = $rowBankDisplay["bank_code"];
						$arrayResult['FEE_AMT'] = $arrResponse->FEE_AMT;
						$arrayResult['FEE_AMT_FORMAT'] = number_format($arrayResult["FEE_AMT"],2);
						$arrayResult['SOURCE_REFNO'] = $arrResponse->SOURCE_REFNO;
						$arrayResult['ETN_REFNO'] = $arrResponse->ETN_REFNO;
						$arrayResult['ACCOUNT_NAME'] = $account_name_th;
						$arrayResult['RESULT'] = TRUE;
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0038";
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOper,
							':sigma_key' => $dataComing["sigma_key"],
							':amt_transfer' => $dataComing["amt_transfer"],
							':response_code' => $arrResponse->RESPONSE_CODE,
							':response_message' => $arrResponse->RESPONSE_MESSAGE
						];
						$log->writeLog('deposittrans',$arrayStruc);
						if(isset($configError[$rowBankDisplay["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowBankDisplay["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}else if($rowBankDisplay["bank_code"] == '006'){
					// $getTransactionForFee = $conmysql->prepare("SELECT COUNT(ref_no) as C_TRANS FROM gctransaction WHERE member_no = :member_no and trans_flag = '1' and
																// transfer_mode = '9' and result_transaction = '1' and DATE_FORMAT(operate_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')");
					// $getTransactionForFee->execute([
						// ':member_no' => $payload["member_no"]
					// ]);
					// $rowCountFee = $getTransactionForFee->fetch(PDO::FETCH_ASSOC);
					// if($rowCountFee["C_TRANS"] + 1 > 2){
					if($rowBankDisplay["fee_deposit"] > 0){
						$getBalanceAccFee = $conoracle->prepare("SELECT PRNCBAL FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
						$getBalanceAccFee->execute([':deptaccount_no' => $rowBankDisplay["account_payfee"]]);
						$rowBalFee = $getBalanceAccFee->fetch(PDO::FETCH_ASSOC);
						$dataAccFee = $cal_dep->getConstantAcc($rowBankDisplay["account_payfee"]);
						if($rowBalFee["PRNCBAL"] - $rowBankDisplay["fee_deposit"] < $dataAccFee["MINPRNCBAL"]){
							$arrayResult['RESPONSE_CODE'] = "WS0100";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
						$arrayResult['FEE_AMT'] = $rowBankDisplay["fee_deposit"];
						$arrayResult['FEE_AMT_FORMAT'] = number_format($arrayResult["FEE_AMT"],2);
					}
					// }
					
					$arrayResult['sigma_key'] = $dataComing["sigma_key"];
					$arrayResult['bank_code'] = $rowBankDisplay["bank_code"];
					$arrayResult['ACCOUNT_NAME'] = $account_name_th;
					$arrayResult['RESULT'] = TRUE;
				}
			}else{
				$arrayResult['bank_code'] = $rowBankDisplay["bank_code"];
				$arrayResult['ACCOUNT_NAME'] = $account_name_th;
				$arrayResult['RESULT'] = TRUE;
			}
			$fetchLoanRepay = $conoracle->prepare("SELECT PRINCIPAL_BALANCE,INTEREST_RETURN,RKEEP_PRINCIPAL
													FROM lncontmaster
													WHERE loancontract_no = :loancontract_no");
			$fetchLoanRepay->execute([':loancontract_no' => $dataComing["loancontract_no"]]);
			$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
			
			$interest = $cal_loan->calculateIntAPI($dataComing["loancontract_no"],$dataComing["amt_transfer"]);
			$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
			$penalty_amt = 0;
			try {
				$arrayGroup = array();
				$arrayGroup["coop_id"] = $config["COOP_ID"];
				$arrayGroup["loancontract_no"] = $dataComing["loancontract_no"];
				$arrayGroup["member_no"] = $member_no;
				$arrayGroup["operate_date"] = $dateOperC;
				$arrayGroup["slip_date"] = $dateOperC;
				$arrayGroup["fee_amt"] = $penalty_amt;
				$arrayGroup["entry_id"] = "mobile_app";
				$argumentWS = [
					"as_wspass" => $config["WS_STRC_DB"],
					"astr_lninitloans" => $arrayGroup
				];
				$resultWS = $clientWS->__call("of_initslippayin_mobile", array($argumentWS));
				$responseInitLn = $resultWS->of_initslippayin_mobileResult;
				$arrayResult['responseInitLn'] = $responseInitLn;
				$arrayGroupSaveLn["bfshrcont_balamt"] = $responseInitLn->bfshrcont_balamt;
				$arrayGroupSaveLn["bfintarrset_amt"] = $responseInitLn->bfintarrset_amt;
				$arrayGroupSaveLn["interest_period"] = $responseInitLn->interest_period;
				$interest["INT_PAYMENT"] = $responseInitLn->interest_period + $responseInitLn->bfintarrset_amt;
				if($interest["INT_PAYMENT"] > 0){
					if($dataComing["amt_transfer"] < $interest["INT_PAYMENT"]){
						$interest["INT_PAYMENT"] = $dataComing["amt_transfer"];
					}else{
						$prinPay = $dataComing["amt_transfer"] - $interest["INT_PAYMENT"];
					}
					if($prinPay < 0){
						$prinPay = 0;
					}
					if($interest["INT_PAYMENT"] > 0){
						$arrayResult["PAYMENT_INT"] = $interest["INT_PAYMENT"];
					}
					$arrayResult["PAYMENT_PRIN"] = $prinPay;
				}else{
					$arrayResult["PAYMENT_PRIN"] = $dataComing["amt_transfer"];
				}
				if(number_format($dataComing["amt_transfer"],2,'.','') > number_format(($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"]) + $interest["INT_PAYMENT"],2,'.','')){
					$arrayResult['RESPONSE_CODE'] = "WS0098";
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${LOAN_BALANCE}',number_format($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"],2),$configError["LOAN_BALANCE_FOR_REPAY"][0][$lang_locale]);
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
				require_once('../../include/exit_footer.php');
			}catch(SoapFault $e){
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':operate_date' => $dateOper,
					':deptaccount_no' => $from_account_no,
					':amt_transfer' => $dataComing["amt_transfer"],
					':status_flag' => '0',
					':destination' => $dataComing["contract_no"],
					':response_code' => "WS0066",
					':response_message' => ($e->getMessage() ?? " Service ไม่ได้ Return Error มาให้"),
				];
				$log->writeLog('repayloan',$arrayStruc);
				$arrayResult["RESPONSE_CODE"] = 'WS0066';
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			
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