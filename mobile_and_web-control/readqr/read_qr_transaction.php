<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','transaction_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'QRCodeScanner')){
		$fetchDataTrans = $conmysql->prepare("SELECT from_account,destination_type,transfer_mode,amount,bank_code,
											fee_amt,operate_date,member_no,transaction_type_code,destination,penalty_amt
											FROM gctransaction WHERE ref_no = :tran_id and result_transaction = '1'");
		$fetchDataTrans->execute([':tran_id' => $dataComing["transaction_no"]]);
		if($fetchDataTrans->rowCount() > 0){
			$formatDept = $func->getConstant('dep_format');
			$formatDeptHidden = $func->getConstant('hidden_dep');
			$rowDataTrans = $fetchDataTrans->fetch(PDO::FETCH_ASSOC);
			if($rowDataTrans["transfer_mode"] == '1'){
				$arrayResult['TRANS_TYPE'] = 'โอนภายในบัญชีสหกรณ์';
			}else if($rowDataTrans["transfer_mode"] == '2'){
				if($rowDataTrans["transaction_type_code"] == 'WFS'){
					$arrayResult['TRANS_TYPE'] = 'ชำระหนี้';
				}else if($rowDataTrans["transaction_type_code"] == 'WTM'){
					$arrayResult['TRANS_TYPE'] = 'ชำระรายเดือน';
				}
			}else if($rowDataTrans["transfer_mode"] == '3'){
				$arrayResult['TRANS_TYPE'] = 'ซื้อหุ้น';
			}else if($rowDataTrans["transfer_mode"] == '4'){
				$arrayResult['TRANS_TYPE'] = 'รับเงินกู้';
			}else if($rowDataTrans["transfer_mode"] == '5'){
				$arrayResult['TRANS_TYPE'] = 'ชำระผ่าน Billpayment';
			}else if($rowDataTrans["transfer_mode"] == '9'){
				if(substr($rowDataTrans["transaction_type_code"],0,1) == 'W'){
					$arrayResult['TRANS_TYPE'] = 'ถอนเงินฝาก';
				}else{
					$arrayResult['TRANS_TYPE'] = 'ฝากเงิน';
				}
			}
			if($rowDataTrans["transfer_mode"] == '1' || $rowDataTrans["transfer_mode"] == '2' || $rowDataTrans["transfer_mode"] == '3'
			|| ($rowDataTrans["transfer_mode"] == '9' && substr($rowDataTrans["transaction_type_code"],0,1) == 'W')){
				if(isset($rowDataTrans["bank_code"])){
					$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.bank_account_name,csb.bank_logo_path,csb.bank_format_account,
																csb.bank_format_account_hide
																FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
																WHERE gba.deptaccount_no_bank = :acc_bank and gba.bank_code = :bank_code 
																and gba.bindaccount_status = '1' and gba.member_no = :member_no");
					$fetchAccountBeenBind->execute([
						':acc_bank' => $rowDataTrans["from_account"],
						':bank_code' => $rowDataTrans["bank_code"],
						':member_no' => $rowDataTrans["member_no"]
					]);
					$rowDataBind = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
					if($rowDataTrans["bank_code"] == '025'){
						$arrayResult['FROM_ACCOUNT_NO_FORMAT_HIDE'] = $rowDataTrans["from_account"];
						$arrayResult['FROM_ACCOUNT_NO_FORMAT'] = $rowDataTrans["from_account"];
						$arrayResult['FROM_ACCOUNT_NAME'] = $rowDataBind["bank_account_name"];
						$arrayResult['FROM_IMG'] = $config['URL_SERVICE'].$rowDataBind["bank_logo_path"];
					}else{
						$arrayResult['FROM_ACCOUNT_NO_FORMAT'] = $lib->formataccount($rowDataTrans["from_account"],$rowDataBind["bank_format_account"]);
						$arrayResult['FROM_ACCOUNT_NO_FORMAT_HIDE'] = $lib->formataccount_hidden($arrayResult['FROM_ACCOUNT_NO_FORMAT'],$rowDataBind["bank_format_account_hide"]);
						$arrayResult['FROM_ACCOUNT_NAME'] = $rowDataBind["bank_account_name"];
						$arrayResult['FROM_IMG'] = $config['URL_SERVICE'].$rowDataBind["bank_logo_path"];
					}
				}else{
					$getAccount = $conoracle->prepare("SELECT dt.depttype_desc,dp.deptaccount_name
												FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
												WHERE dp.deptaccount_no = :deptaccount_no and dp.deptclose_status <> 1");
					$getAccount->execute([':deptaccount_no' => $rowDataTrans["from_account"]]);
					$rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC);
					$arrayResult['FROM_ACCOUNT_NO_FORMAT'] = $lib->formataccount($rowDataTrans["from_account"],$formatDept);
					$arrayResult['FROM_ACCOUNT_NO_FORMAT_HIDE'] = $lib->formataccount_hidden($arrayResult['FROM_ACCOUNT_NO_FORMAT'],$formatDeptHidden);
					$arrayResult['FROM_ACCOUNT_NAME'] = $rowAccount["DEPTACCOUNT_NAME"];
				}
			}else{
				if($rowDataTrans["transfer_mode"] == '9'){
					$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.bank_account_name,csb.bank_logo_path,csb.bank_format_account,
																csb.bank_format_account_hide
																FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
																WHERE gba.deptaccount_no_bank = :acc_bank and gba.bank_code = :bank_code 
																and gba.bindaccount_status = '1' and gba.member_no = :member_no");
					$fetchAccountBeenBind->execute([
						':acc_bank' => $rowDataTrans["from_account"],
						':bank_code' => $rowDataTrans["bank_code"],
						':member_no' => $rowDataTrans["member_no"]
					]);
					$rowDataBind = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
					if($rowDataTrans["bank_code"] == '025'){
						$arrayResult['FROM_ACCOUNT_NO_FORMAT'] = $rowDataTrans['from_account'];
						$arrayResult['FROM_ACCOUNT_NO_FORMAT_HIDE'] = $rowDataTrans['from_account'];
						$arrayResult['FROM_IMG'] = $config['URL_SERVICE'].$rowDataBind["bank_logo_path"];
						$arrayResult['FROM_ACCOUNT_NAME'] = $rowDataBind["bank_account_name"];
					}else{
						$arrayResult['FROM_ACCOUNT_NO_FORMAT'] = $lib->formataccount($rowDataTrans["from_account"],$rowDataBind["bank_format_account"]);
						$arrayResult['FROM_ACCOUNT_NO_FORMAT_HIDE'] = $lib->formataccount_hidden($arrayResult['FROM_ACCOUNT_NO_FORMAT'],$rowDataBind["bank_format_account_hide"]);
						$arrayResult['FROM_ACCOUNT_NAME'] = $rowDataBind["bank_account_name"];
						$arrayResult['FROM_IMG'] = $config['URL_SERVICE'].$rowDataBind["bank_logo_path"];
					}
				}else{
					$arrayResult['FROM_ACCOUNT_NO_FORMAT'] = $rowDataTrans["from_account"];
					$arrayResult['FROM_ACCOUNT_NO_FORMAT_HIDE'] = $rowDataTrans["from_account"];
				}
			}
			$arrayResult['AMT_TRANSFER'] = $rowDataTrans["amount"];
			if($rowDataTrans["fee_amt"] > 0){
				$arrayResult['FEE_AMT'] = $rowDataTrans["fee_amt"];
			}
			if($rowDataTrans["penalty_amt"] > 0){
				$arrayResult['PENALTY_AMT'] = $rowDataTrans["penalty_amt"];
			}
			if($rowDataTrans["destination_type"] == '1'){
				if(isset($rowDataTrans["bank_code"])){
					if(substr($rowDataTrans["transaction_type_code"],0,1) == 'W'){
						$fetchAccountBeenBind = $conmysql->prepare("SELECT gba.bank_account_name,csb.bank_logo_path,csb.bank_format_account,
																	csb.bank_format_account_hide
																	FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
																	WHERE gba.deptaccount_no_bank = :acc_bank and gba.bank_code = :bank_code 
																	and gba.bindaccount_status = '1' and gba.member_no = :member_no");
						$fetchAccountBeenBind->execute([
							':acc_bank' => $rowDataTrans["destination"],
							':bank_code' => $rowDataTrans["bank_code"],
							':member_no' => $rowDataTrans["member_no"]
						]);
						$rowDataBind = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
						if($rowDataTrans["bank_code"] == '025'){
							$arrayResult['TO_ACCOUNT_NO_FORMAT_HIDE'] = $rowDataTrans["destination"];
							$arrayResult['TO_ACCOUNT_NO_FORMAT'] = $rowDataTrans["destination"];
							$arrayResult['TO_ACCOUNT_NAME'] = $rowDataBind["bank_account_name"];
							$arrayResult['TO_IMG'] = $config['URL_SERVICE'].$rowDataBind["bank_logo_path"];
						}else{
							$arrayResult['TO_ACCOUNT_NO_FORMAT'] = $lib->formataccount($rowDataTrans["destination"],$rowDataBind["bank_format_account"]);
							$arrayResult['TO_ACCOUNT_NO_FORMAT_HIDE'] = $lib->formataccount_hidden($arrayResult['TO_ACCOUNT_NO_FORMAT'],$rowDataBind["bank_format_account_hide"]);
							$arrayResult['TO_ACCOUNT_NAME'] = $rowDataBind["bank_account_name"];
							$arrayResult['TO_IMG'] = $config['URL_SERVICE'].$rowDataBind["bank_logo_path"];
						}
					}else{
						$getAccount = $conoracle->prepare("SELECT dt.depttype_desc,dp.deptaccount_name
													FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
													WHERE dp.deptaccount_no = :deptaccount_no and dp.deptclose_status <> 1");
						$getAccount->execute([':deptaccount_no' => $rowDataTrans["destination"]]);
						$rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC);
						$arrayResult['TO_ACCOUNT_NAME'] = $rowAccount["DEPTACCOUNT_NAME"];
						$arrayResult['TO_ACCOUNT_NO_FORMAT'] = $lib->formataccount($rowDataTrans["destination"],$formatDept);
						$arrayResult['TO_ACCOUNT_NO_FORMAT_HIDE'] = $lib->formataccount_hidden($arrayResult["TO_ACCOUNT_NO_FORMAT"],$formatDeptHidden);
					}
				}else{
					$getAccount = $conoracle->prepare("SELECT dt.depttype_desc,dp.deptaccount_name
												FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code
												WHERE dp.deptaccount_no = :deptaccount_no and dp.deptclose_status <> 1");
					$getAccount->execute([':deptaccount_no' => $rowDataTrans["from_account"]]);
					$rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC);
					$arrayResult['TO_ACCOUNT_NAME'] = $rowAccount["DEPTACCOUNT_NAME"];
					$arrayResult['TO_ACCOUNT_NO_FORMAT'] = $lib->formataccount($rowDataTrans["destination"],$formatDept);
					$arrayResult['TO_ACCOUNT_NO_FORMAT_HIDE'] = $lib->formataccount($arrayResult["TO_ACCOUNT_NO_FORMAT"],$formatDeptHidden);
				}
			}else if($rowDataTrans["destination_type"] == '2'){
				$getMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.member_no = :member_no");
				$getMemberName->execute([':member_no' => $rowDataTrans["destination"]]);
				$rowMember = $getMemberName->fetch(PDO::FETCH_ASSOC);
				$arrayResult['TO_ACCOUNT_NAME'] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
				$arrayResult['TO_ACCOUNT_NO_FORMAT'] = $rowDataTrans["destination"];
				$arrayResult['TO_ACCOUNT_NO_FORMAT_HIDE'] = $rowDataTrans["destination"];
			}else if($rowDataTrans["destination_type"] == '3'){
				if($rowDataTrans["transaction_type_code"] == 'WFS'){
					$getDataPayLoan = $conmysql->prepare("SELECT loancontract_no,principal,interest
														FROM gcrepayloan WHERE ref_no = :ref_no");
					$getDataPayLoan->execute([':ref_no' => $dataComing["transaction_no"]]);
					$rowDataPay = $getDataPayLoan->fetch(PDO::FETCH_ASSOC);
					$getLoantype = $conoracle->prepare("SELECT LT.LOANTYPE_DESC
														FROM lncontmaster ln LEFT JOIN lnloantype lt ON ln.loantype_code = lt.loantype_code
														WHERE ln.loancontract_no = :loancontract_no");
					$getLoantype->execute([':loancontract_no' => $rowDataPay["loancontract_no"]]);
					$rowLoantype = $getLoantype->fetch(PDO::FETCH_ASSOC);
					$arrayResult['TO_ACCOUNT_NAME'] = $rowLoantype["LOANTYPE_DESC"];
					$arrayResult['TO_ACCOUNT_NO_FORMAT'] = $rowDataTrans["destination"];
					$arrayResult['TO_ACCOUNT_NO_FORMAT_HIDE'] = $rowDataTrans["destination"];
					$arrayResult['INTEREST_PAYMENT'] = $rowDataPay["interest"];
					$arrayResult['PRIN_PAYMENT'] = $rowDataPay["principal"];
				}else if($rowDataTrans["transaction_type_code"] == 'WTM'){
					$arrayResult['TO_ACCOUNT_NAME'] = 'ชำระเรียกเก็บ(เต็มยอด)';
					$arrayResult['TO_ACCOUNT_NO_FORMAT'] = $rowDataTrans["destination"];
					$arrayResult['TO_ACCOUNT_NO_FORMAT_HIDE'] = $rowDataTrans["destination"];
				}
			}
			
			$arrayResult['TRANSACTION_NO'] = $dataComing["transaction_no"];
			$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($rowDataTrans["operate_date"],'D m Y',true);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0113";
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