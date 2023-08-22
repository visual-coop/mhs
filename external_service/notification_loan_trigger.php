<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$fetchDataSTM = $conoracle->prepare("SELECT lut.loanitemtype_desc,lcn.loancontract_no,lcn.OPERATE_DATE,lcm.member_no,lcn.seq_no,
									lcn.principal_payment,lcn.interest_payment,lcn.principal_balance
									from lncontstatement lcn LEFT JOIN lncontmaster lcm ON lcn.loancontract_no = lcm.loancontract_no
									LEFT JOIN lnucfloanitemtype lut ON lcn.loanitemtype_code = lut.loanitemtype_code
									WHERE lcn.operate_date >= (SYSDATE - 1) and lcn.sync_notify_flag = '0' ");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	$arrToken = $func->getFCMToken('person',$rowSTM["MEMBER_NO"]);
	$templateMessage = $func->getTemplateSystem('LoanInfo',1);
	foreach($arrToken["LIST_SEND"] as $dest){
		$dataMerge = array();
		$dataMerge["LOANCONTRACT_NO"] = $rowSTM["LOANCONTRACT_NO"];
		$dataMerge["PRINCIPAL_PAYMENT"] = number_format($rowSTM["PRINCIPAL_PAYMENT"],2);
		$dataMerge["INTEREST_PAYMENT"] = number_format($rowSTM["INTEREST_PAYMENT"],2);
		$dataMerge["PRINCIPAL_BALANCE"] = number_format($rowSTM["PRINCIPAL_BALANCE"],2);
		$dataMerge["ITEMTYPE_DESC"] = $rowSTM["LOANITEMTYPE_DESC"];
		$dataMerge["DATETIME"] = isset($rowSTM["OPERATE_DATE"]) && $rowSTM["OPERATE_DATE"] != '' ? 
		$lib->convertdate($rowSTM["OPERATE_DATE"],'D m Y',true) : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
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
				$updateSyncFlag = $conoracle->prepare("UPDATE lncontstatement SET sync_notify_flag = '1' WHERE loancontract_no = :loancontract_no and seq_no = :seq_no");
				$updateSyncFlag->execute([
					':loancontract_no' => $rowSTM["LOANCONTRACT_NO"],
					':seq_no' => $rowSTM["SEQ_NO"]
				]);
			}else{
				$lib->addLogtoTxt($arrPayloadNotify,'sync_noti_loan');
			}
		}
	}
}
?>