<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','source_type'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayTransaction = array();
		$rownum = $func->getConstant('limit_fetch_history_trans');
		if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
			$old_ref_no = isset($dataComing["ref_no"]) ? "and ref_no > ".$dataComing["ref_no"] : "and ref_no > 0";
		}else{
			$old_ref_no = isset($dataComing["ref_no"]) ? "and ref_no < ".$dataComing["ref_no"] : "and ref_no < 99999999999999999";
		}
		$fetchTransList = $conmysql->prepare("SELECT REF_NO,TRANSFER_MODE,DESTINATION,DESTINATION_TYPE,AMOUNT_RECEIVE,OPERATE_DATE,FEE_AMT,PENALTY_AMT,TRANS_FLAG
															FROM gctransaction WHERE member_no = :member_no and from_account = :deptaccount_no and result_transaction <> '-9' ".$old_ref_no."
															ORDER BY ref_no DESC LIMIT ".$rownum);
		$fetchTransList->execute([
			':member_no' => $payload["member_no"],
			':deptaccount_no' => preg_replace('/-/','',$dataComing["deptaccount_no"])
		]);
		if($dataComing["source_type"] == "coop"){
			$fetchFormatAccBank = $conmysql->prepare("SELECT bank_format_account,bank_format_account_hide FROM csbankdisplay WHERE bank_code = '004' ");
			$fetchFormatAccBank->execute();
			$rowBankDS = $fetchFormatAccBank->fetch(PDO::FETCH_ASSOC);
		}
		while($rowTrans = $fetchTransList->fetch(PDO::FETCH_ASSOC)){
			$arrayTrans = array();
			$arrayTrans["REF_NO"] = $rowTrans["REF_NO"];
			$arrayTrans["AMOUNT"] = number_format($rowTrans["AMOUNT_RECEIVE"],2);
			$arrayTrans["OPERATE_DATE"] = $lib->convertdate($rowTrans["OPERATE_DATE"],'d m Y',true);
			if($rowTrans["TRANSFER_MODE"] == '1'){
				$arrayTrans["TRANSFER_MODE"] = "โอนเงินฝากไปบัญชีภายในสหกรณ์";
				$arrayTrans["PENALTY_AMT"] = number_format($rowTrans["PENALTY_AMT"],2);
			}else if($rowTrans["TRANSFER_MODE"] == '2'){
				$arrayTrans["TRANSFER_MODE"] = "โอนเงินฝากไปชำระหนี้";
				$arrayTrans["PENALTY_AMT"] = number_format($rowTrans["PENALTY_AMT"],2);
			}else if($rowTrans["TRANSFER_MODE"] == '3'){
				$arrayTrans["TRANSFER_MODE"] = "โอนเงินฝากไปซื้อหุ้น";
				$arrayTrans["PENALTY_AMT"] = number_format($rowTrans["PENALTY_AMT"],2);
			}else{
				if($dataComing["source_type"] == "coop"){
					$arrayTrans["TRANSFER_MODE"] = "โอนเงินฝากไปยังบัญชีธนาคาร";
					$arrayTrans["PENALTY_AMT"] = number_format($rowTrans["PENALTY_AMT"],2);
					$arrayTrans["FEE_AMT"] = number_format($rowTrans["FEE_AMT"],2);
				}else{
					$arrayTrans["TRANSFER_MODE"] = "โอนเงินบัญชีธนาคารเข้าบัญชีสหกรณ์";
					$arrayTrans["FEE_AMT"] = number_format($rowTrans["FEE_AMT"],2);
				}
			}
			if($dataComing["source_type"] == "coop"){
				if($rowTrans["DESTINATION_TYPE"] == "1"){
					if($rowTrans["TRANSFER_MODE"] == '9'){
						$fetchNameAccDes = $conoracle->prepare("SELECT mp.prename_desc || mb.memb_name || ' ' || mb.memb_surname as DEPTACCOUNT_NAME
																	FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
																	WHERE mb.member_no = :member_no");
						$fetchNameAccDes->execute([':member_no' => $member_no]);
					}else{
						$fetchNameAccDes = $conoracle->prepare("SELECT TRIM(DEPTACCOUNT_NAME) as DEPTACCOUNT_NAME FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
						$fetchNameAccDes->execute([':deptaccount_no' => $rowTrans["DESTINATION"]]);
					}
					$rowNameAcc = $fetchNameAccDes->fetch(PDO::FETCH_ASSOC);
					$arrayTrans["DESTINATION_NAME"] = preg_replace('/\"/','',trim($rowNameAcc["DEPTACCOUNT_NAME"]));
					$arrayTrans["DESTINATION_TYPE_DESC"] = 'เลขบัญชี';
					$arrayTrans["DESTINATION"] = $lib->formataccount($rowTrans["DESTINATION"],$rowBankDS["bank_format_account"]);
					$arrayTrans["DESTINATION_HIDDEN"] = $lib->formataccount_hidden($rowTrans["DESTINATION"],$rowBankDS["bank_format_account_hide"]);
				}else if($rowTrans["DESTINATION_TYPE"] == "3"){
					$fetchNameLoanDes = $conoracle->prepare("SELECT lnt.loantype_desc FROM lncontmaster lnm LEFT JOIN lnloantype lnt ON lnm.loantype_code = lnt.loantype_code
																	WHERE lnm.loancontract_no = :loancontract_no");
					$fetchNameLoanDes->execute([':loancontract_no' => $rowTrans["DESTINATION"]]);
					$rowNameLn = $fetchNameLoanDes->fetch(PDO::FETCH_ASSOC);
					$arrayTrans["DESTINATION_NAME"] = $rowNameLn["LOANTYPE_DESC"];
					$arrayTrans["DESTINATION_TYPE_DESC"] = 'เลขสัญญา';
					$contract_no = preg_replace('/\//','',$rowTrans["DESTINATION"]);
					if(mb_stripos($contract_no,'.') === FALSE){
						$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
						if(mb_strlen($contract_no) == 10){
							$arrayTrans["DESTINATION"] = $loan_format;
						}else if(mb_strlen($contract_no) == 11){
							$arrayTrans["DESTINATION"] = $loan_format.'-'.mb_substr($contract_no,10);
						}
					}else{
						$arrayTrans["DESTINATION"] = $contract_no;
					}
				}else{
					$fetchNameDes = $conoracle->prepare("SELECT mp.prename_desc || mb.memb_name || ' ' || mb.memb_surname as FULL_NAME
																	FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
																	WHERE mb.member_no = :member_no");
					$fetchNameDes->execute([':member_no' => $rowTrans["DESTINATION"]]);
					$rowName = $fetchNameDes->fetch(PDO::FETCH_ASSOC);
					$arrayTrans["DESTINATION_NAME"] = $rowName["FULL_NAME"];
					$arrayTrans["DESTINATION_TYPE_DESC"] = 'เลขสมาชิก';
					$arrayTrans["DESTINATION"] = $rowTrans["DESTINATION"];
				}
			}else{
				$fetchNameAccDes = $conoracle->prepare("SELECT DEPTACCOUNT_NAME as DEPTACCOUNT_NAME FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
				$fetchNameAccDes->execute([':deptaccount_no' => $rowTrans["DESTINATION"]]);
				$rowNameAcc = $fetchNameAccDes->fetch(PDO::FETCH_ASSOC);
				$arrayTrans["DESTINATION_NAME"] = preg_replace('/\"/','',trim($rowNameAcc["DEPTACCOUNT_NAME"]));
				$arrayTrans["DESTINATION_TYPE_DESC"] = 'เลขบัญชี';
				$arrayTrans["DESTINATION"] = $lib->formataccount($rowTrans["DESTINATION"],$func->getConstant('dep_format'));
				$arrayTrans["DESTINATION_HIDDEN"] = $lib->formataccount_hidden($rowTrans["DESTINATION"],$func->getConstant('hidden_dep'));
			}
			$arrayTransaction[] = $arrayTrans;
		}
		$arrayResult['TRANSACTION_LIST'] = $arrayTransaction;
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