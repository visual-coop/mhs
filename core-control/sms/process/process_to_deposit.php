<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','processsmsservicefee')){
		$arrayGroup = array();
		$MonthNow = date("Ym");
		$dateNow = date('d/m/y');
		$arrSMSCont = array();
		$getSMSConstant = $conmysql->prepare("SELECT smscs_name,smscs_value FROM smsconstantsystem");
		$getSMSConstant->execute();
		while($rowSMSConstant = $getSMSConstant->fetch(PDO::FETCH_ASSOC)){
			$arrSMSCont[$rowSMSConstant["smscs_name"]] = $rowSMSConstant["smscs_value"];
		}
		$bulkInsert = array();
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
			$fee_amt = $FeeNet + $rowSmsTranWassent["accrued_amt"];
			$getSeqNo = $conoracle->prepare("SELECT MAX(seq_no) as MAX_SEQNO FROM dpdeptstatement WHERE deptaccount_no = :deptaccount_no");
			$getSeqNo->execute([':deptaccount_no' => $rowSmsTranWassent["deptaccount_no"]]);
			$rowSeqNo = $getSeqNo->fetch(PDO::FETCH_ASSOC);
			$lastSeqNo = $rowSeqNo["MAX_SEQNO"] + 1;
			$bulkInsert[] = "INTO dpdepttran (coop_id,deptaccount_no,seq_no,system_code,tran_date,tran_status,member_no,deptitem_amt,ref_coopid) VALUES('".$config["COOP_ID"]."','".$rowSmsTranWassent["deptaccount_no"]."',".$lastSeqNo.",'SMS','".$dateNow."',0,'".$rowSmsTranWassent["member_no"]."',".$fee_amt.",'".$config["COOP_ID"]."')";
			if(sizeof($bulkInsert) == 1000){
				$insertDeptTran = $conoracle->prepare("INSERT ALL ".implode(' ',$bulkInsert)."
													SELECT * FROM dual");
				if($insertDeptTran->execute()){
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถผ่านรายการลง dpdepttran ได้กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../include/exit_footer.php');
				}
				unset($bulkInsert);
				$bulkInsert = array();
			}
		}
		if(sizeof($bulkInsert) > 0){
			$insertDeptTran = $conoracle->prepare("INSERT ALL ".implode(' ',$bulkInsert)."
													SELECT * FROM dual");
			if($insertDeptTran->execute()){
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถผ่านรายการลง dpdepttran ได้กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../include/exit_footer.php');
			}
		}
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