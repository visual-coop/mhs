<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestTrack')){
		$arrGrpReq = array();
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$fetchReqLoan = $conmysql->prepare("SELECT reqloan_doc,loantype_code,request_amt,period_payment,period,req_status,loanpermit_amt,
															diff_old_contract,receive_net,salary_img,citizen_img,remark,approve_date,contractdoc_url
															FROM gcreqloan WHERE member_no = :member_no and req_status = :req_status ORDER BY update_date DESC");
			$fetchReqLoan->execute([
				':member_no' => $payload["member_no"],
				':req_status' => $dataComing["req_status"]
			]);
			while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
				$getLoanType = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
				$getLoanType->execute([':loantype_code' => $rowReqLoan["loantype_code"]]);
				$rowLoan = $getLoanType->fetch(PDO::FETCH_ASSOC);
				$arrayReq = array();
				$arrayReq["LOANTYPE_DESC"] = $rowLoan["LOANTYPE_DESC"];
				$arrayReq["REQLOAN_DOC"] = $rowReqLoan["reqloan_doc"];
				$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["loantype_code"];
				$arrayReq["REQUEST_AMT"] = $rowReqLoan["request_amt"];
				$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["period_payment"];
				$arrayReq["PERIOD"] = $rowReqLoan["period"];
				$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
				$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["req_status"]][0][$lang_locale];
				$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["loanpermit_amt"];
				$arrayReq["DIFFOLD_CONTRACT"] = $rowReqLoan["diff_old_contract"];
				$arrayReq["RECEIVE_NET"] = $rowReqLoan["receive_net"];
				$arrayReq["SALARY_IMG"] = $rowReqLoan["salary_img"];
				$arrayReq["CITIZEN_IMG"] = $rowReqLoan["citizen_img"];
				$arrayReq["REMARK"] = $rowReqLoan["remark"];
				$arrayReq["CONTRACTDOC_URL"] = $rowReqLoan["contractdoc_url"];
				$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["approve_date"]) && $rowReqLoan["approve_date"] != "" ? $lib->convertdate($rowReqLoan["approve_date"],'d m Y') : null;
				$arrGrpReq[] = $arrayReq;
			}
		}else{
			$fetchReqLoan = $conmysql->prepare("SELECT reqloan_doc,loantype_code,request_amt,period_payment,period,req_status,loanpermit_amt,
															diff_old_contract,receive_net,salary_img,citizen_img,remark,approve_date,contractdoc_url
															FROM gcreqloan WHERE member_no = :member_no ORDER BY update_date DESC");
			$fetchReqLoan->execute([':member_no' => $payload["member_no"]]);
			while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
				$getLoanType = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
				$getLoanType->execute([':loantype_code' => $rowReqLoan["loantype_code"]]);
				$rowLoan = $getLoanType->fetch(PDO::FETCH_ASSOC);
				$arrayReq = array();
				$arrayReq["LOANTYPE_DESC"] = $rowLoan["LOANTYPE_DESC"];
				$arrayReq["REQLOAN_DOC"] = $rowReqLoan["reqloan_doc"];
				$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["loantype_code"];
				$arrayReq["REQUEST_AMT"] = $rowReqLoan["request_amt"];
				$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["period_payment"];
				$arrayReq["PERIOD"] = $rowReqLoan["period"];
				$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
				$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["req_status"]][0][$lang_locale];
				$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["loanpermit_amt"];
				$arrayReq["DIFFOLD_CONTRACT"] = $rowReqLoan["diff_old_contract"];
				$arrayReq["RECEIVE_NET"] = $rowReqLoan["receive_net"];
				$arrayReq["SALARY_IMG"] = $rowReqLoan["salary_img"];
				$arrayReq["CITIZEN_IMG"] = $rowReqLoan["citizen_img"];
				$arrayReq["REMARK"] = $rowReqLoan["remark"];
				$arrayReq["CONTRACTDOC_URL"] = $rowReqLoan["contractdoc_url"];
				$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["approve_date"]) && $rowReqLoan["approve_date"] != "" ? $lib->convertdate($rowReqLoan["approve_date"],'d m Y') : null;
				$arrGrpReq[] = $arrayReq;
			}
		}
		$arrayResult['REQ_LIST'] = $arrGrpReq;
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