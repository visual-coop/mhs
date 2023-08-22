<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$fetchDataGuarantee = $conoracle->prepare("SELECT mp.prename_desc || mb.memb_name || ' ' || mb.memb_surname as FULL_NAME,
										lcc.LOANCONTRACT_NO,lcc.seq_no,
										 lcc.REF_COLLNO, lcm.startcont_date as STARTCONT_DATE,lt.loantype_desc as LOAN_TYPE,lcm.loanapprove_amt as AMOUNT
										FROM lncontcoll lcc 
										LEFT JOIN lncontmaster lcm ON lcc.loancontract_no = lcm.loancontract_no and lcc.coop_id = lcm.coop_id
										LEFT JOIN lnloantype lt ON lcm.loantype_code = lt.loantype_code
										LEFT JOIN mbmembmaster mb ON lcm.member_no = mb.member_no
										LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
										WHERE lcm.startcont_date >= (sysdate - 1) and lcc.sync_notify_flag = '0' and lcc.coll_status = '1' and lcm.contract_status = '1' and lcc.loancolltype_code = '01' ");
$fetchDataGuarantee->execute();
while($rowGuarantee = $fetchDataGuarantee->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowGuarantee["REF_COLLNO"]);
	$templateMessage = $func->getTemplateSystem('GuaranteeInfo',1);
	foreach($arrToken["LIST_SEND"] as $dest){
		$dataMerge = array();
		$dataMerge["LOANCONTRACT_NO"] = $rowGuarantee["LOANCONTRACT_NO"];
		$dataMerge["AMOUNT"] = number_format($rowGuarantee["AMOUNT"],2);
		$dataMerge["FULL_NAME"] = $rowGuarantee["FULL_NAME"];
		$dataMerge["LOAN_TYPE"] = $rowGuarantee["LOAN_TYPE"];
		$dataMerge["APPROVE_DATE"] = isset($rowGuarantee["STARTCONT_DATE"]) && $rowGuarantee["STARTCONT_DATE"] != '' ? 
		$lib->convertdate($rowGuarantee["STARTCONT_DATE"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
		$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
		$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
		$arrPayloadNotify["MEMBER_NO"] = array($dest["MEMBER_NO"]);
		$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
		$arrMessage["BODY"] = $message_endpoint["BODY"];
		$arrMessage["PATH_IMAGE"] = null;
		$arrPayloadNotify["PAYLOAD"] = $arrMessage;
		$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
		if($func->insertHistory($arrPayloadNotify,'2')){
			if($lib->sendNotify($arrPayloadNotify,"person")){
				$updateSyncFlag = $conoracle->prepare("UPDATE lncontcoll SET sync_notify_flag = '1' WHERE loancontract_no = :loancontract_no and seq_no = :seq_no and ref_collno = :ref_collno");
				$updateSyncFlag->execute([
					':loancontract_no' => $rowGuarantee["LOANCONTRACT_NO"],
					':seq_no' => $rowGuarantee["SEQ_NO"],
					':ref_collno' => $rowGuarantee["REF_COLLNO"]
				]);
			}else{
				$lib->addLogtoTxt($arrPayloadNotify,'sync_noti_loan');
			}
		}
	}
}
?>