<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrGroupAccount = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmdeposit');
		$arrayResult['LIMIT_DURATION'] = $limit;
		if($lib->checkCompleteArgument(["date_start"],$dataComing)){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		if($lib->checkCompleteArgument(["date_end"],$dataComing)){
			$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
		}else{
			$date_now = date('Y-m-d');
		}
		$fetchLastStmAcc = $conoracle->prepare("SELECT * from (SELECT dps.deptaccount_no,dt.depttype_desc,dpm.deptaccount_name,dpm.prncbal as BALANCE,
											(SELECT max(OPERATE_DATE) FROM dpdeptstatement WHERE deptaccount_no = dpm.deptaccount_no) as LAST_OPERATE_DATE
											FROM dpdeptmaster dpm LEFT JOIN dpdeptslip dps ON dpm.deptaccount_no = dps.deptaccount_no  and dpm.coop_id = dps.coop_id
												LEFT JOIN DPDEPTTYPE dt ON dpm.depttype_code = dt.depttype_code
												WHERE dpm.member_no = :member_no and dps.deptgroup_code IS NOT NULL and dpm.deptclose_status <> 1 ORDER BY dps.deptslip_date DESC,dps.deptslip_no DESC) where rownum <= 1");
		$fetchLastStmAcc->execute([':member_no' => $member_no]);
		$rowAccountLastSTM = $fetchLastStmAcc->fetch(PDO::FETCH_ASSOC);
		$account_no = preg_replace('/-/','',$rowAccountLastSTM["DEPTACCOUNT_NO"]);
		$arrAccount = array();
		$account_no_format = isset($account_no) && $account_no != "" ? $lib->formataccount($account_no,$func->getConstant('dep_format')) : null;
		$arrAccount["DEPTACCOUNT_NO"] = $account_no_format;
		$arrAccount["DEPTACCOUNT_NO_HIDDEN"] = isset($account_no_format) ? $lib->formataccount_hidden($account_no_format,$func->getConstant('hidden_dep')) : null;
		$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',TRIM($rowAccountLastSTM["DEPTACCOUNT_NAME"]));
		$arrAccount["BALANCE"] = number_format($rowAccountLastSTM["BALANCE"],2);
		$arrAccount["LAST_OPERATE_DATE"] = $lib->convertdate($rowAccountLastSTM["LAST_OPERATE_DATE"],'y-n-d');
		$arrAccount["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowAccountLastSTM["LAST_OPERATE_DATE"],'D m Y');
		$arrAccount["DATA_TIME"] = date('H:i');
		if($dataComing["channel"] == 'mobile_app'){
			$rownum = $func->getConstant('limit_fetch_stm_dept');
			if(isset($dataComing["fetch_type"]) && $dataComing["fetch_type"] == 'refresh'){
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.SEQ_NO > ".$dataComing["old_seq_no"] : "and dsm.SEQ_NO > 0";
			}else{
				$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and dsm.SEQ_NO < 999999";
			}
		}else{
			$rownum = 999999;
			$old_seq_no = isset($dataComing["old_seq_no"]) ? "and dsm.SEQ_NO < ".$dataComing["old_seq_no"] : "and dsm.SEQ_NO < 999999";
		}
		$fetchSlipTrans = $conmysql->prepare("SELECT coop_slip_no FROM gctransaction WHERE (from_account = :deptaccount_no OR destination = :deptaccount_no) and result_transaction = '-9'");
		$fetchSlipTrans->execute([':deptaccount_no' => $account_no]);
		$arrSlipTrans = array();
		$arrSlipStm = array();
		while($rowslipTrans = $fetchSlipTrans->fetch(PDO::FETCH_ASSOC)){
			$arrSlipTrans[] = $rowslipTrans["coop_slip_no"];
		}
		if(sizeof($arrSlipTrans) > 0){
			$fetchStmSeqDept = $conoracle->prepare("SELECT dpstm_no FROM dpdeptslip WHERE (deptslip_no IN('".implode("','",$arrSlipTrans)."') OR refer_slipno IN('".implode("','",$arrSlipTrans)."')) and deptaccount_no = :deptacc_no");
			$fetchStmSeqDept->execute([':deptacc_no' => $account_no]);
			while($rowstmseq = $fetchStmSeqDept->fetch(PDO::FETCH_ASSOC)){
				$arrSlipStm[] = $rowstmseq["DPSTM_NO"];
			}
		}
		if(sizeof($arrSlipStm) > 0){
			$getStatement = $conoracle->prepare("SELECT * FROM (SELECT dit.DEPTITEMTYPE_DESC AS TYPE_TRAN,dit.SIGN_FLAG,dsm.seq_no,
												dsm.operate_date,dsm.DEPTITEM_AMT as TRAN_AMOUNT,dsm.PRNCBAL
												FROM dpdeptstatement dsm LEFT JOIN DPUCFDEPTITEMTYPE dit
												ON dsm.DEPTITEMTYPE_CODE = dit.DEPTITEMTYPE_CODE 
												WHERE dsm.deptaccount_no = :account_no and dsm.seq_no NOT IN('".implode("','",$arrSlipStm)."') and dsm.OPERATE_DATE 
												BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ".$old_seq_no." 
												ORDER BY dsm.SEQ_NO DESC) WHERE rownum <= ".$rownum." ");
		}else{
			$getStatement = $conoracle->prepare("SELECT * FROM (SELECT dit.DEPTITEMTYPE_DESC AS TYPE_TRAN,dit.SIGN_FLAG,dsm.seq_no,
												dsm.operate_date,dsm.DEPTITEM_AMT as TRAN_AMOUNT,dsm.PRNCBAL
												FROM dpdeptstatement dsm LEFT JOIN DPUCFDEPTITEMTYPE dit
												ON dsm.DEPTITEMTYPE_CODE = dit.DEPTITEMTYPE_CODE 
												WHERE dsm.deptaccount_no = :account_no and dsm.OPERATE_DATE 
												BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ".$old_seq_no." 
												ORDER BY dsm.SEQ_NO DESC) WHERE rownum <= ".$rownum." ");
		}
		$getStatement->execute([
			':account_no' => $account_no,
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		$getMemoDP = $conmysql->prepare("SELECT memo_text,memo_icon_path,seq_no FROM gcmemodept 
											WHERE deptaccount_no = :account_no");
		$getMemoDP->execute([
			':account_no' => $account_no
		]);
		$arrMemo = array();
		while($rowMemo = $getMemoDP->fetch(PDO::FETCH_ASSOC)){
			$arrMemo[] = $rowMemo;
		}
		while($rowStm = $getStatement->fetch(PDO::FETCH_ASSOC)){
			$arrSTM = array();
			$arrSTM["TYPE_TRAN"] = $rowStm["TYPE_TRAN"];
			$arrSTM["SIGN_FLAG"] = $rowStm["SIGN_FLAG"];
			$arrSTM["SEQ_NO"] = $rowStm["SEQ_NO"];
			$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrSTM["TRAN_AMOUNT"] = number_format($rowStm["TRAN_AMOUNT"],2);
			$arrSTM["PRIN_BAL"] = number_format($rowStm["PRNCBAL"],2);
			if(array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no')) === False){
				$arrSTM["MEMO_TEXT"] = null;
				$arrSTM["MEMO_ICON_PATH"] = null;
			}else{
				$arrSTM["MEMO_TEXT"] = $arrMemo[array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no'))]["memo_text"] ?? null;
				$arrSTM["MEMO_ICON_PATH"] = $arrMemo[array_search($rowStm["SEQ_NO"],array_column($arrMemo,'seq_no'))]["memo_icon_path"] ?? null;
			}
			$arrayGroupSTM[] = $arrSTM;
		}
		if($dataComing["fetch_type"] != 'more'){
			$arrayResult["HEADER"] = $arrAccount;
		}
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		$arrayResult["REQUEST_STATEMENT"] = TRUE;
		$arrayResult["RESULT"] = TRUE;
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