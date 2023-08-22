<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$showSplitSlip = $func->getConstant('show_split_slip_report');
		$arrGroupDetail = array();
		$getDetailKP = $conoracle->prepare("SELECT 
													CASE kut.system_code 
													WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
													WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
													ELSE kut.keepitemtype_desc
													END as TYPE_DESC,
													kpd.SEQ_NO,
													kut.keepitemtype_grp as TYPE_GROUP,
													kpd.MONEY_RETURN_STATUS,
													kpd.ADJUST_ITEMAMT,
													kpd.ADJUST_PRNAMT,
													kpd.ADJUST_INTAMT,
													case kut.keepitemtype_grp 
														WHEN 'DEP' THEN kpd.description
														WHEN 'LON' THEN kpd.loancontract_no
													ELSE kpd.description END as PAY_ACCOUNT,
													kpd.period,
													NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
													NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
													NVL(kpd.principal_payment,0) AS PRN_BALANCE,
													NVL(kpd.interest_payment,0) AS INT_BALANCE
													FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
													LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
													WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
													ORDER BY kut.SORT_IN_RECEIVE ASC");
		$getDetailKP->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		while($rowDetail = $getDetailKP->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["ADJUST_PRNAMT"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["ADJUST_INTAMT"],2);
				}else{
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
				}
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
			}
			if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ADJUST_ITEMAMT"],2);
			}else{
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
			}
			$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			$arrDetail["SEQ_NO"] = $rowDetail["SEQ_NO"];
			$arrGroupDetail[] = $arrDetail;
		}
		$arrayResult['SPLIT_SLIP'] = $showSplitSlip == "1" ? TRUE : FALSE;
		$arrayResult['SHOW_SLIP_REPORT'] = TRUE;
		$arrayResult['DETAIL'] = $arrGroupDetail;
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
