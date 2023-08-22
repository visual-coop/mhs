<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','processsmsservicefee')){
		$arrayGroup = array();
		$MonthNow = date("Ym");
		$sumFee = 0;
		$sumRound = 0;
		$arrSMSCont = array();
		$getSMSConstant = $conmysql->prepare("SELECT smscs_name,smscs_value FROM smsconstantsystem");
		$getSMSConstant->execute();
		while($rowSMSConstant = $getSMSConstant->fetch(PDO::FETCH_ASSOC)){
			$arrSMSCont[$rowSMSConstant["smscs_name"]] = $rowSMSConstant["smscs_value"];
		}
		$fetchSmsTranWassent = $conmysql->prepare("SELECT count(sm.id_smssent) as round_send,sm.member_no,sm.deptaccount_no,sc.request_flat_date,
												sc.smscsp_pay_type,sc.accrued_amt
												FROM smstranwassent sm LEFT JOIN smsconstantperson sc ON sm.deptaccount_no = sc.smscsp_account
												WHERE sm.process_flag = '0' and sm.is_receive = '1' GROUP BY sm.member_no,sm.deptaccount_no");
		$fetchSmsTranWassent->execute();
		while($rowSmsTranWassent = $fetchSmsTranWassent->fetch(PDO::FETCH_ASSOC)){
			if($rowSmsTranWassent["smscsp_pay_type"] == '0'){
				$FeeNet = $arrSMSCont["sms_fee_amt_per_trans"] * $rowSmsTranWassent["round_send"];
			}else{
				if($MonthNow > $rowSmsTranWassent["request_flat_date"]){
					$FeeNet = $arrSMSCont["flat_price_in_month"];
				}else{
					$FeeNet = $arrSMSCont["sms_fee_amt_per_trans"] * $rowSmsTranWassent["round_send"];
				}
			}
			$arrGroupSmsTranWassent = array();
			$arrGroupSmsTranWassent["MEMBER_NO"] = $rowSmsTranWassent["member_no"];
			$arrGroupSmsTranWassent["ACCRUED_AMT"] = $rowSmsTranWassent["accrued_amt"];
			$arrGroupSmsTranWassent["PROCESS_ROUND"] = number_format($rowSmsTranWassent["round_send"],0);
			$arrGroupSmsTranWassent["DEPTACCOUNT_NO"] = $lib->formataccount($rowSmsTranWassent["deptaccount_no"],$func->getConstant('dep_format'));
			if($rowSmsTranWassent["smscsp_pay_type"] == '1'){
				if($MonthNow > $rowSmsTranWassent["request_flat_date"]){
					$arrGroupSmsTranWassent["PAY_TYPE"] = '1';
				}else{
					$arrGroupSmsTranWassent["PAY_TYPE"] = '0';
				}
			}else{
				$arrGroupSmsTranWassent["PAY_TYPE"] = $rowSmsTranWassent["smscsp_pay_type"];
			}
			$arrGroupSmsTranWassent["FEE_FORMAT"] = number_format($FeeNet,2);
			$sumFee += $FeeNet + $rowSmsTranWassent["accrued_amt"];
			$sumRound += $rowSmsTranWassent["round_send"];
			$arrayGroup[] = $arrGroupSmsTranWassent;
		}
		$arrayResult["SUM_FEE"] = number_format($sumFee,2);
		$arrayResult["SUM_ROUND"] = number_format($sumRound,0);
		$arrayResult["SMS_TRAN_WASSENT"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
		
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>