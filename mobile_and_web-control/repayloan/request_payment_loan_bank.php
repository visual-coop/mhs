<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','amt_transfer','contract_no','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepPayLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$dataComing["amt_transfer"] = number_format($dataComing["amt_transfer"],2,'.','');
		$getBankDisplay = $conmysql->prepare("SELECT cs.link_deposit_coopdirect,cs.bank_short_ename,gc.bank_code,gc.account_payfee,
												cs.fee_deposit,cs.bank_short_ename,gc.deptaccount_no_bank
												FROM gcbindaccount gc LEFT JOIN csbankdisplay cs ON gc.bank_code = cs.bank_code
												WHERE gc.sigma_key = :sigma_key and gc.bindaccount_status = '1'");
		$getBankDisplay->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowBankDisplay = $getBankDisplay->fetch(PDO::FETCH_ASSOC);
		$vccAccID = null;
		if($rowBankDisplay["bank_code"] == '025'){
			$vccAccID = $func->getConstant('map_account_id_bay');
		}else if($rowBankDisplay["bank_code"] == '006'){
			$vccAccID = $func->getConstant('map_account_id_ktb');
		}
		$from_account_no = $rowBankDisplay["deptaccount_no_bank"];
		$itemtypeWithdraw = 'WFS';
		$ref_no = time().$lib->randomText('all',3);
		// $dateOper = date('c');
		// $dateOperC = date('Y-m-d H:i:s',strtotime($dateOper));
		$dateOperC = date('c');
		if(date('Hi') >= 2300){ 
			$dateOper = date('Y-m-d', strtotime($dateOperC. ' +1 days'));
			$dateOper = $dateOper.' 00:00:00';
		}else{
			$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		}
		
		
		
		$dataCont = $cal_loan->getContstantLoanContract($dataComing["contract_no"]);
		$int_returnSrc = 0;
		$int_return = $dataCont["INTEREST_RETURN"];
		$prinPay = 0;
		$interest = 0;
		$int_returnFull = 0;
		$interestPeriod = 0;
		$withdrawStatus = FALSE;
		$interest = $cal_loan->calculateIntAPI($dataComing["contract_no"],$dataComing["amt_transfer"]);
		$intarrear = $interest["INT_ARREAR"];
		$int_returnFull = $interest["INT_RETURN"];
		$interestPeriod = $interest["INT_PERIOD"];
		if($interestPeriod < 0){
			$interestPeriod = 0;
		}
		if($interest["INT_PAYMENT"] > 0){
			if($dataComing["amt_transfer"] < $interest["INT_PAYMENT"]){
				$interest["INT_PAYMENT"] = $dataComing["amt_transfer"];
			}else{
				$prinPay = $dataComing["amt_transfer"] - $interest["INT_PAYMENT"];
			}
			if($prinPay < 0){
				$prinPay = 0;
			}
		}else{
			$prinPay = $dataComing["amt_transfer"];
		}
		$getBalanceAccFee = $conoracle->prepare("SELECT PRNCBAL FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
		$getBalanceAccFee->execute([':deptaccount_no' => $rowBankDisplay["account_payfee"]]);
		$rowBalFee = $getBalanceAccFee->fetch(PDO::FETCH_ASSOC);
		$dataAccFee = $cal_dep->getConstantAcc($rowBankDisplay["account_payfee"]);
		// $getTransactionForFee = $conmysql->prepare("SELECT COUNT(ref_no) as C_TRANS FROM gctransaction WHERE member_no = :member_no and trans_flag = '1' and
													// transfer_mode = '9' and result_transaction = '1' and DATE_FORMAT(operate_date,'%Y%m') = DATE_FORMAT(NOW(),'%Y%m')");
		// $getTransactionForFee->execute([
			// ':member_no' => $payload["member_no"]
		// ]);
		// $rowCountFee = $getTransactionForFee->fetch(PDO::FETCH_ASSOC);
		
		$arrSlipnoPayin = $cal_dep->generateDocNo('ONLINETXLON',$lib);
		$arrSlipDocNoPayin = $cal_dep->generateDocNo('ONLINETXRECEIPT',$lib);
		$payinslip_no = $arrSlipnoPayin["SLIP_NO"];
		$payinslipdoc_no = $arrSlipDocNoPayin["SLIP_NO"];
		$lastdocument_noPayin = $arrSlipnoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
		$lastdocument_noDocPayin = $arrSlipDocNoPayin["QUERY"]["LAST_DOCUMENTNO"] + 1;
		$updateDocuControlPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXLON'");
		$updateDocuControlPayin->execute([':lastdocument_no' => $lastdocument_noPayin]);
		$updateDocuControlDocPayin = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXRECEIPT'");
		$updateDocuControlDocPayin->execute([':lastdocument_no' => $lastdocument_noDocPayin]);
		$getlastseqFeeAcc = $cal_dep->getLastSeqNo($rowBankDisplay["account_payfee"]);
		$conoracle->beginTransaction();
		$conmysql->beginTransaction();
		$payslip = $cal_loan->paySlip($conoracle,$dataComing["amt_transfer"],$config,$payinslipdoc_no,$dateOper,
		$vccAccID,null,$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_no,$itemtypeWithdraw,$conmysql,0,true);
		if($payslip["RESULT"]){
			$payslipdet = $cal_loan->paySlipLonDet($conoracle,$dataCont,$dataComing["amt_transfer"],$config,$dateOper,$log,$payload,
			$from_account_no,$payinslip_no,'LON',$dataCont["LOANTYPE_CODE"],$dataComing["contract_no"],$prinPay,$interest["INT_PAYMENT"],
			$intarrear,$int_returnSrc,$interestPeriod,'1');
			if($payslipdet["RESULT"]){
				$repayloan = $cal_loan->repayLoan($conoracle,$dataComing["contract_no"],$dataComing["amt_transfer"],0,
				$config,$payinslipdoc_no,$dateOper,
				$vccAccID,null,$log,$lib,$payload,$from_account_no,$payinslip_no,$member_no,$ref_no,$dataComing["app_version"],$dataComing["fee_amt"]);
				if($repayloan["RESULT"]){
					
					// $limit_number = 2;
					// if($rowCountFee["C_TRANS"] + 1 > $limit_number){
						if($rowBankDisplay["fee_deposit"] > 0){
							if($rowBalFee["PRNCBAL"] - $rowBankDisplay["fee_deposit"] < $dataAccFee["MINPRNCBAL"]){
								$conoracle->rollback();
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "WS0100";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
							$vccamtPenalty = $func->getConstant("accidfee_receive");
							$arrSlipDPnoFee = $cal_dep->generateDocNo('ONLINETXFEE',$lib);
							$deptslip_noFee = $arrSlipDPnoFee["SLIP_NO"];
							$lastdocument_noFee = $arrSlipDPnoFee["QUERY"]["LAST_DOCUMENTNO"] + 1;
							$updateDocuControlFee = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXFEE'");
							$updateDocuControlFee->execute([':lastdocument_no' => $lastdocument_noFee]);
							$penaltyWtd = $cal_dep->insertFeeTransaction($conoracle,$rowBankDisplay["account_payfee"],$vccamtPenalty,'FDM',
							$dataComing["amt_transfer"],$rowBankDisplay["fee_deposit"],$dateOper,$config,null,$lib,$getlastseqFeeAcc["MAX_SEQ_NO"],$dataAccFee,true,$payinslip_no,$rowCountFee["C_TRANS"] + 1,$deptslip_noFee);
							if($penaltyWtd["RESULT"]){
								
							}else{
								$conoracle->rollback();
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = $penaltyWtd["RESPONSE_CODE"];
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayStruc = [
									':member_no' => $payload["member_no"],
									':id_userlogin' => $payload["id_userlogin"],
									':operate_date' => $dateOper,
									':sigma_key' => $dataComing["sigma_key"],
									':amt_transfer' => $amt_transfer,
									':response_code' => $arrayResult['RESPONSE_CODE'],
									':response_message' => 'ชำระค่าธรรมเนียมไม่สำเร็จ / '.$penaltyWtd["ACTION"]
								];
								$log->writeLog('deposittrans',$arrayStruc);
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
						}
					// }else{
						// if($rowBankDisplay["fee_deposit"] > 0){
							// $vccamtPenaltyDepPromo = $func->getConstant("accidfee_promotion");
							// $arrSlipDPnoFee = $cal_dep->generateDocNo('ONLINETXFEE',$lib);
							// $deptslip_noFee = $arrSlipDPnoFee["SLIP_NO"];
							// $lastdocument_noFee = $arrSlipDPnoFee["QUERY"]["LAST_DOCUMENTNO"] + 1;
							// $updateDocuControlFee = $conoracle->prepare("UPDATE cmdocumentcontrol SET last_documentno = :lastdocument_no WHERE document_code = 'ONLINETXFEE'");
							// $updateDocuControlFee->execute([':lastdocument_no' => $lastdocument_noFee]);
						
							// $penaltyWtdPromo = $cal_dep->insertFeePromotion($conoracle,$rowBankDisplay["account_payfee"],$vccamtPenaltyDepPromo,'FDM',
							// $dataComing["amt_transfer"],$rowBankDisplay["fee_deposit"],$dateOper,$config,null,$lib,$getlastseqFeeAcc["MAX_SEQ_NO"],$dataAccFee,$rowCountFee["C_TRANS"] + 1,$deptslip_noFee);
							// if($penaltyWtdPromo["RESULT"]){
							
							// }else{
								// $conoracle->rollback();
								// $conmysql->rollback();
								
								// $arrayResult['RESPONSE_CODE'] = $penaltyWtdPromo["RESPONSE_CODE"];
								// $arrayResult['penaltyWtdPromo'] = $penaltyWtdPromo;
								// $arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								// $arrayStruc = [
									// ':member_no' => $payload["member_no"],
									// ':id_userlogin' => $payload["id_userlogin"],
									// ':operate_date' => $dateOper,
									// ':sigma_key' => $dataComing["sigma_key"],
									// ':amt_transfer' => $amt_transfer,
									// ':response_code' => $arrayResult['RESPONSE_CODE'],
									// ':response_message' => 'ชำระค่าธรรมเนียมไม่สำเร็จ / '.$penaltyWtdPromo["ACTION"]
								// ];
								// $log->writeLog('deposittrans',$arrayStruc);
								// $arrayResult['RESULT'] = FALSE;
								// require_once('../../include/exit_footer.php');
							// }
						// }
					// }
					$arrSendData = array();
					$arrVerifyToken['exp'] = time() + 300;
					$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
					$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
					$arrVerifyToken['amt_transfer'] = $dataComing["amt_transfer"];
					$arrVerifyToken['operate_date'] = $dateOperC;
					$arrVerifyToken['ref_trans'] = $ref_no;
					$arrVerifyToken['coop_account_no'] = null;
					if($rowBankDisplay["bank_code"] == '025'){
						$arrVerifyToken['etn_trans'] = $dataComing["ETN_REFNO"];
						$arrVerifyToken['transaction_ref'] = $dataComing["SOURCE_REFNO"];
					}
					$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
					$arrSendData["verify_token"] = $verify_token;
					$arrSendData["app_id"] = $config["APP_ID"];
					// Deposit Inside --------------------------------------
					// $responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowBankDisplay["link_deposit_coopdirect"],$arrSendData);
					// if(!$responseAPI["RESULT"]){
						// $conoracle->rollback();
						// $conmysql->rollback();
						// $filename = basename(__FILE__, '.php');
						// $arrayResult['RESPONSE_CODE'] = "WS0027";
						// $arrayStruc = [
							// ':member_no' => $payload["member_no"],
							// ':id_userlogin' => $payload["id_userlogin"],
							// ':operate_date' => $dateOperC,
							// ':sigma_key' => $dataComing["sigma_key"],
							// ':amt_transfer' => $rowReceiveAmt["RECEIVE_AMT"],
							// ':response_code' => $arrayResult['RESPONSE_CODE'],
							// ':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
						// ];
						// $log->writeLog('deposittrans',$arrayStruc);
						// $message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
						// $lib->sendLineNotify($message_error);
						// $func->MaintenanceMenu($dataComing["menu_component"]);
						// $arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						// $arrayResult['RESULT'] = FALSE;
						// require_once('../../include/exit_footer.php');
						
					// }
					// $arrResponse = json_decode($responseAPI);
					
					$arrResponse = [
						'RESULT' => true,
						'TRANSACTION_NO' => 'DL00000001',
						'EXTERNAL_REF' => 'ETN0000001'
						
					];
					
					if($arrResponse['RESULT']){
						$transaction_no = $arrResponse['TRANSACTION_NO'];
						$etn_ref = $arrResponse['EXTERNAL_REF'];
					
					// if($arrResponse->RESULT){
						// $transaction_no = $arrResponse->TRANSACTION_NO;
						// $etn_ref = $arrResponse->EXTERNAL_REF;
						$insertTransactionLog = $conmysql->prepare("INSERT INTO gctransaction(ref_no,transaction_type_code,from_account,destination_type,
																	destination,transfer_mode
																	,amount,fee_amt,amount_receive,trans_flag,operate_date,result_transaction,member_no,
																	etn_refno,id_userlogin,ref_no_source,bank_code)
																	VALUES(:ref_no,:slip_type,:from_account,'3',:destination,'9',:amount,:fee_amt,
																	:amount_receive,'1',:operate_date,'1',:member_no,:etn_refno,:id_userlogin,:ref_no_source,:bank_code)");
						$insertTransactionLog->execute([
							':ref_no' => $ref_no,
							':slip_type' => $itemtypeWithdraw,
							':from_account' => $from_account_no,
							':destination' => $dataComing["contract_no"],
							':amount' => $dataComing["amt_transfer"],
							':fee_amt' => $dataComing["fee_amt"],
							':amount_receive' => $dataComing["amt_transfer"],
							':operate_date' => $dateOperC,
							':member_no' => $payload["member_no"],
							':etn_refno' => $etn_ref,
							':id_userlogin' => $payload["id_userlogin"],
							':ref_no_source' => $transaction_no,
							':bank_code' => $rowBankDisplay["bank_code"]
						]);
						$conoracle->commit();
						$conmysql->commit();
						$arrToken = $func->getFCMToken('person',$payload["member_no"]);
						$templateMessage = $func->getTemplateSystem($dataComing["menu_component"],1);
						$dataMerge = array();
						$dataMerge["DEPTACCOUNT"] = $lib->formataccount_hidden($from_account_no,$func->getConstant('hidden_dep'));
						$dataMerge["CONTRACT_NO"] = $dataComing["contract_no"];
						$dataMerge["AMOUNT"] = number_format($dataComing["amt_transfer"],2);
						$dataMerge["INT_PAY"] = number_format($interest,2);
						$dataMerge["PRIN_PAY"] = number_format($prinPay,2);
						$dataMerge["OPERATE_DATE"] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
						$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
						foreach($arrToken["LIST_SEND"] as $dest){
							if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
								$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
								$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
								$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
								$arrMessage["BODY"] = $message_endpoint["BODY"];
								$arrMessage["PATH_IMAGE"] = null;
								$arrPayloadNotify["PAYLOAD"] = $arrMessage;
								$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
								$arrPayloadNotify["SEND_BY"] = 'system';
								$arrPayloadNotify["TYPE_NOTIFY"] = '2';
								if($func->insertHistory($arrPayloadNotify,'2')){
									$lib->sendNotify($arrPayloadNotify,"person");
								}
							}
						}
						foreach($arrToken["LIST_SEND_HW"] as $dest){
							if($dest["RECEIVE_NOTIFY_TRANSACTION"] == '1'){
								$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
								$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
								$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
								$arrMessage["BODY"] = $message_endpoint["BODY"];
								$arrMessage["PATH_IMAGE"] = null;
								$arrPayloadNotify["PAYLOAD"] = $arrMessage;
								$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
								$arrPayloadNotify["SEND_BY"] = 'system';
								$arrPayloadNotify["TYPE_NOTIFY"] = '2';
								if($func->insertHistory($arrPayloadNotify,'2')){
									$lib->sendNotifyHW($arrPayloadNotify,"person");
								}
							}
						}
						$arrayResult['TRANSACTION_NO'] = $ref_no;
						$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOperC,'D m Y',true);
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}else{
						$conoracle->rollback();
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "WS0038";
						$arrayStruc = [
							':member_no' => $payload["member_no"],
							':id_userlogin' => $payload["id_userlogin"],
							':operate_date' => $dateOperC,
							':sigma_key' => $dataComing["sigma_key"],
							':amt_transfer' => $rowReceiveAmt["RECEIVE_AMT"],
							':response_code' => $arrResponse->RESPONSE_CODE,
							':response_message' => $arrResponse->RESPONSE_MESSAGE
						];
						$log->writeLog('deposittrans',$arrayStruc);
						if(isset($configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}else{
					$conoracle->rollback();
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = $repayloan["RESPONSE_CODE"];
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$conoracle->rollback();
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = $payslipdet["RESPONSE_CODE"];
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$conoracle->rollback();
			$conmysql->rollback();
			$arrayResult['payslip'] = $payslip;
			$arrayResult['RESPONSE_CODE'] = $payslip["RESPONSE_CODE"];
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