<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$arrSubOtherInfo = array();
$arrSubOtherInfoSalaryRemain = array();

$getMemberData = $conoracle->prepare("SELECT member_date,salary_amount FROM mbmembmaster WHERE member_no = :member_no");
$getMemberData->execute([':member_no' => $member_no]);
$rowMembData = $getMemberData->fetch(PDO::FETCH_ASSOC);
$duration_month = $lib->count_duration($rowMembData["MEMBER_DATE"],'m');
if($duration_month <= 3){
	$maxloan_amt = 0;
	return;
}
$getDeptATM = $conoracle->prepare("SELECT DEPTACCOUNT_NO FROM dpdeptmaster WHERE member_no = :member_no and depttype_code = '88' and deptclose_status = '0'");
$getDeptATM->execute([':member_no' => $member_no]);
$rowDeptATM = $getDeptATM->fetch(PDO::FETCH_ASSOC);
if(empty($rowDeptATM["DEPTACCOUNT_NO"]) || $rowDeptATM["DEPTACCOUNT_NO"] == ""){
	$maxloan_amt = 0;
	return;
}
$getLoanCustomCredit = $conoracle->prepare("SELECT lc.maxloan_amt FROM lnloantypecustom lc,mbmembmaster mb 
											WHERE mb.member_no = :member_no and lc.LOANTYPE_CODE = :loantype_code 
											and mb.salary_amount BETWEEN lc.startsalary_amt and lc.endsalary_amt");
$getLoanCustomCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowLoanCustom = $getLoanCustomCredit->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowLoanCustom["MAXLOAN_AMT"];
$arrSubOtherInfo["LABEL"] = "ต้องใช้ผู้ค้ำประกัน";
$arrSubOtherInfoSalaryRemain["LABEL"] = "ต้องมีเงินเดือนคงเหลืออย่างน้อย";
if($rowMembData["SALARY_AMOUNT"] <= 10000){
	$arrSubOtherInfo["VALUE"] = "1 คน";
	$arrSubOtherInfoSalaryRemain["VALUE"] = "1,000 บาท";
}else if($rowMembData["SALARY_AMOUNT"] > 10000 && $rowMembData["SALARY_AMOUNT"] <= 15000){
	$arrSubOtherInfo["VALUE"] = "2 คน";
	$arrSubOtherInfoSalaryRemain["VALUE"] = "1,000 บาท";
}else if($rowMembData["SALARY_AMOUNT"] > 15000 && $rowMembData["SALARY_AMOUNT"] <= 20000){
	$arrSubOtherInfo["VALUE"] = "2 คน";
	$arrSubOtherInfoSalaryRemain["VALUE"] = "1,500 บาท";
}else if($rowMembData["SALARY_AMOUNT"] > 20000 && $rowMembData["SALARY_AMOUNT"] <= 25000){
	$arrSubOtherInfo["VALUE"] = "3 คน";
	$arrSubOtherInfoSalaryRemain["VALUE"] = "2,000 บาท";
}else if($rowMembData["SALARY_AMOUNT"] > 25000 && $rowMembData["SALARY_AMOUNT"] <= 30000){
	$arrSubOtherInfo["VALUE"] = "3 คน";
	$arrSubOtherInfoSalaryRemain["VALUE"] = "2,500 บาท";
}else if($rowMembData["SALARY_AMOUNT"] > 30000 && $rowMembData["SALARY_AMOUNT"] <= 35000){
	$arrSubOtherInfo["VALUE"] = "4 คน";
	$arrSubOtherInfoSalaryRemain["VALUE"] = "3,000 บาท";
}else if($rowMembData["SALARY_AMOUNT"] > 35000 && $rowMembData["SALARY_AMOUNT"] <= 40000){
	$arrSubOtherInfo["VALUE"] = "4 คน";
	$arrSubOtherInfoSalaryRemain["VALUE"] = "3,500 บาท";
}else if($rowMembData["SALARY_AMOUNT"] > 40000){
	$arrSubOtherInfo["VALUE"] = "5 คน";
	$arrSubOtherInfoSalaryRemain["VALUE"] = "4,000 บาท";
}
$arrOtherInfo[] = $arrSubOtherInfo;
$arrOtherInfo[] = $arrSubOtherInfoSalaryRemain;
?>