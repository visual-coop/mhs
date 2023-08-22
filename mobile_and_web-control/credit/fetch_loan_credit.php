<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanCredit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupCredit = array();
		$arrCanCal = array();
		$arrCanReq = array();
		$getMemberType = $conoracle->prepare("SELECT MEMBER_TYPE FROM mbmembmaster WHERE member_no = :member_no");
		$getMemberType->execute([':member_no' => $member_no]);
		$rowMemb = $getMemberType->fetch(PDO::FETCH_ASSOC);
		$fetchLoanCanCal = $conmysql->prepare("SELECT loantype_code,is_loanrequest FROM gcconstanttypeloan WHERE is_creditloan = '1' ORDER BY loantype_code ASC");
		$fetchLoanCanCal->execute();
		while($rowCanCal = $fetchLoanCanCal->fetch(PDO::FETCH_ASSOC)){
			$fetchLoanType = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code and (member_type = :member_type OR member_type = '0')");
			$fetchLoanType->execute([
				':loantype_code' => $rowCanCal["loantype_code"],
				':member_type' => $rowMemb["MEMBER_TYPE"]
			]);
			$rowLoanType = $fetchLoanType->fetch(PDO::FETCH_ASSOC);
			if(isset($rowLoanType["LOANTYPE_DESC"]) && $rowLoanType["LOANTYPE_DESC"] != ""){
				$arrCredit = array();
				$maxloan_amt = 0;
				$arrCollShould = array();
				$arrOtherInfo = array();
				$canRequest = FALSE;
				if(file_exists('calculate_loan_'.$rowCanCal["loantype_code"].'.php')){
					include('calculate_loan_'.$rowCanCal["loantype_code"].'.php');
				}else{
					include('calculate_loan_etc.php');
				}
				if($canRequest === TRUE){
					$canRequest = $rowCanCal["is_loanrequest"] == '1' ? TRUE : FALSE;
					$CheckIsReq = $conmysql->prepare("SELECT reqloan_doc,req_status
																FROM gcreqloan WHERE loantype_code = :loantype_code and member_no = :member_no and req_status NOT IN('-9','9')");
					$CheckIsReq->execute([
						':loantype_code' => $rowCanCal["loantype_code"],
						':member_no' => $member_no
					]);
					if($CheckIsReq->rowCount() > 0 || $maxloan_amt <= 0){
						$canRequest = FALSE;
					}else {
						if(!$func->check_permission($payload["user_type"],'LoanRequestForm','LoanRequestForm')){
							$canRequest = FALSE;
						}
					}
				}
				
				$arrCredit["OTHER_INFO"] = $arrOtherInfo;
				$arrCredit["COLL_SHOULD_CHECK"] = $arrCollShould;
				$arrCredit["ALLOW_REQUEST"] = $canRequest;
				$arrCredit["LOANTYPE_CODE"] = $rowCanCal["loantype_code"];
				$arrCredit["LOANTYPE_DESC"] = $rowLoanType["LOANTYPE_DESC"];
				$arrCredit["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrGroupCredit[] = $arrCredit;
			}
		}
		$arrayResult["LOAN_CREDIT"] = $arrGroupCredit;
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