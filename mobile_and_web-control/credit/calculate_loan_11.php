<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$collNotOver2M = null;
$collNotOver3M = null;
$getMemberData = $conoracle->prepare("SELECT (sh.sharestk_amt * 10) as SHARE_BALANCE,mb.SALARY_AMOUNT,sh.LAST_PERIOD
									FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no WHERE mb.member_no = :member_no");
$getMemberData->execute([':member_no' => $member_no]);
$rowMembData = $getMemberData->fetch(PDO::FETCH_ASSOC);
$fetchCredit = $conoracle->prepare("SELECT lc.maxloan_amt,lc.multiple_share,lc.multiple_salary FROM lnloantypecustom lc,mbmembmaster mb 
									LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no WHERE mb.member_no = :member_no and lc.LOANTYPE_CODE = :loantype_code
									and sh.LAST_PERIOD BETWEEN lc.startmember_time and lc.endmember_time");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = ($rowMembData["SALARY_AMOUNT"] * $rowCredit["MULTIPLE_SALARY"]) + ($rowMembData["SHARE_BALANCE"] * $rowCredit["MULTIPLE_SHARE"]);
if($maxloan_amt > $rowCredit["MAXLOAN_AMT"]){
	$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
}
$collNotOver2M = number_format($maxloan_amt,2);
if($collNotOver2M > 2000000){
	$collNotOver2M = number_format(2000000,2);
}
$arrSubOtherInfo["LABEL"] = "ต้องใช้ผู้ค้ำประกันสำหรับการกู้น้อยกว่า 2,000,000";
if($rowCredit["MULTIPLE_SALARY"] == '20'){
	$arrSubOtherInfo["VALUE"] = "3 คน";
}else if($rowCredit["MULTIPLE_SALARY"] == '30'){
	$arrSubOtherInfo["VALUE"] = "3 คน";
}else if($rowCredit["MULTIPLE_SALARY"] == '40'){
	$arrSubOtherInfo["VALUE"] = "4 คน";
}else if($rowCredit["MULTIPLE_SALARY"] == '50'){
	$arrSubOtherInfo["VALUE"] = "4 คน";
}else if($rowCredit["MULTIPLE_SALARY"] == '60'){
	$arrSubOtherInfo["VALUE"] = "5 คน";
}else if($rowCredit["MULTIPLE_SALARY"] == '70'){
	$arrSubOtherInfo["VALUE"] = "5 คน";
}else if($rowCredit["MULTIPLE_SALARY"] == '80'){
	$arrSubOtherInfo["VALUE"] = "5 คน";
}
$arrOtherInfo[] = $arrSubOtherInfo;
if($rowCredit["LAST_PERIOD"] > 101){
	$arrSubOtherInfo["LABEL"] = "ต้องใช้ผู้ค้ำประกันสำหรับการกู้ 2,000,100 - 3,000,000";
	if($rowCredit["LAST_PERIOD"] <= '110'){
		$arrSubOtherInfo["VALUE"] = "8 คน";
	}else if($rowCredit["LAST_PERIOD"] <= '120'){
		$arrSubOtherInfo["VALUE"] = "8 คน";
	}else if($rowCredit["LAST_PERIOD"] <= '130'){
		$arrSubOtherInfo["VALUE"] = "10 คน";
	}else if($rowCredit["LAST_PERIOD"] > '130'){
		$arrSubOtherInfo["VALUE"] = "10 คน";
	}
	$arrOtherInfo[] = $arrSubOtherInfo;
	$collNotOver3M = number_format($maxloan_amt,2);
}
if(isset($collNotOver2M)){
	$arrSubCollCheckAmt["LABEL"] = "สิทธิ์สำหรับกู้ไม่เกิน 2,000,000 ระยะเวลาชำระหนี้ไม่เกิน 213 งวด";
	$arrSubCollCheckAmt["CREDIT_AMT"] = $collNotOver2M;
	$arrCollShould[] = $arrSubCollCheckAmt;
}
if(isset($collNotOver3M)){
	$arrSubCollCheckAmt["LABEL"] = "สิทธิ์สำหรับกู้ 2,000,100 - 3,000,000 ระยะเวลาชำระหนี้ไม่เกิน 199 งวด";
	$arrSubCollCheckAmt["CREDIT_AMT"] = $collNotOver3M;
	$arrCollShould[] = $arrSubCollCheckAmt;
}
?>