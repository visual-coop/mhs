<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$shareColl = null;
$getShareData = $conoracle->prepare("SELECT (sh.sharestk_amt * 10) as SHARE_BALANCE
									FROM shsharemaster sh WHERE sh.member_no = :member_no");
$getShareData->execute([':member_no' => $member_no]);
$rowShareData = $getShareData->fetch(PDO::FETCH_ASSOC);
$shareColl = $rowShareData["SHARE_BALANCE"] * 0.90;
if(isset($shareColl)){
	$arrSubCollCheckAmt["LABEL"] = "ใช้หุ้นค้ำ";
	$arrSubCollCheckAmt["CREDIT_AMT"] = $shareColl;
	$arrCollShould[] = $arrSubCollCheckAmt;
}
$getDeptAccount = $conoracle->prepare("SELECT DEPTACCOUNT_NO,PRNCBAL FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status = '0'");
$getDeptAccount->execute([':member_no' => $member_no]);
while($rowDept = $getDeptAccount->fetch(PDO::FETCH_ASSOC)){
	$arrSubCollCheckAmt = array();
	$deptvalue = $rowDept["PRNCBAL"] * 0.90;
	$arrSubCollCheckAmt["LABEL"] = "ใช้เงินฝากค้ำ ".$lib->formataccount($rowDept["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
	$arrSubCollCheckAmt["CREDIT_AMT"] = $deptvalue;
	$arrCollShould[] = $arrSubCollCheckAmt;
}
?>