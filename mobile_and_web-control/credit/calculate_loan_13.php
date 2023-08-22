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
$fetchCredit = $conoracle->prepare("SELECT lc.maxloan_amt
											FROM lnloantypecustom lc LEFT JOIN lnloantype lt ON lc.loantype_code = lt.loantype_code,mbmembmaster mb
											WHERE mb.member_no = :member_no and 
											LT.LOANTYPE_CODE = :loantype_code
											and TRUNC(MONTHS_BETWEEN (SYSDATE,mb.member_date ) /12 *12) BETWEEN lc.startmember_time and lc.endmember_time");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$maxloan_amt = $rowCredit["MAXLOAN_AMT"];
$arrSubOtherInfo["LABEL"] = "กรณีกู้ไม่เกิน 200,000 ใช้คนค้ำประกัน";
$arrSubOtherInfo["VALUE"] = "1 คน";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo["LABEL"] = "กรณีกู้ไม่เกิน 400,000 ใช้คนค้ำประกัน";
$arrSubOtherInfo["VALUE"] = "2 คน";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo["LABEL"] = "กรณีกู้ไม่เกิน 600,000 ใช้คนค้ำประกัน";
$arrSubOtherInfo["VALUE"] = "3 คน";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo["LABEL"] = "กู้ 400,000 บาท ต้องเป็นสมาชิก สสธท.";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
$arrSubOtherInfo["LABEL"] = "กู้ 400,001 บาท ขึ้นไป ต้องเป็นสมาชิก สสธท. + สส.ชสอ.";
$arrSubOtherInfo["VALUE"] = "";
$arrOtherInfo[] = $arrSubOtherInfo;
?>