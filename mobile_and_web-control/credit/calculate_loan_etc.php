<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
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
?>