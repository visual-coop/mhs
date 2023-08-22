<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','checkcreditmember')){
		$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
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
				$arrCollShould = array();
				if(file_exists('../../../../mobile_and_web-control/credit/calculate_loan_'.$rowCanCal["loantype_code"].'.php')){
					include('../../../../mobile_and_web-control/credit/calculate_loan_'.$rowCanCal["loantype_code"].'.php');
				}else{
					include('../../../../mobile_and_web-control/credit/calculate_loan_etc.php');
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
				if(isset($collOnePerson)){
					$arrSubCollPerson["LABEL"] = "สิทธิ์การกู้สำหรับคนค้ำคนเดียว";
					$arrSubCollPerson["CREDIT_AMT"] = $collOnePerson;
					$arrCollShould[] = $arrSubCollPerson;
				}
				if(isset($collTwoPerson)){
					$arrSubCollPerson["LABEL"] = "สิทธิ์การกู้สำหรับคนค้ำมากกว่า 1 คน";
					$arrSubCollPerson["CREDIT_AMT"] = $collTwoPerson;
					$arrCollShould[] = $arrSubCollPerson;
				}
				$arrCredit["COLL_SHOULD_CHECK"] = $arrCollShould;
				$arrCredit["ALLOW_REQUEST"] = $canRequest;
				$arrCredit["LOANTYPE_CODE"] = $rowCanCal["loantype_code"];
				$arrCredit["LOANTYPE_DESC"] = $rowLoanType["LOANTYPE_DESC"];
				$arrCredit["LOAN_PERMIT_AMT"] = $maxloan_amt;
				$arrCredit["LOAN_PERMIT_DESC"] = $rights_desc;
				$arrGroupCredit[] = $arrCredit;
			}
		}
		
		$arrayResult["LOAN_CREDIT"] = $arrGroupCredit;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>
