<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logtransfer')){
		$arrayGroup = array();
		$fetLogTranfer = $conmysql->prepare("SELECT 
													trans.ref_no,trans.member_no,
													trans.transaction_type_code,
													trans.from_account,
													trans.destination_type,
													trans.destination,
													trans.transfer_mode,
													trans.amount,
													trans.fee_amt,
													trans.penalty_amt,
													trans.amount_receive,
													trans.trans_flag,
													trans.operate_date,
													trans.result_transaction,
													trans.ref_no_1,trans.coop_slip_no,
													trans.ref_no_source,
													login.device_name,login.channel	
												FROM gctransaction trans
												LEFT JOIN gcuserlogin login
												ON login.id_userlogin = trans.id_userlogin
												WHERE trans.transfer_mode !='9'
												ORDER BY trans.operate_date DESC");
		$fetLogTranfer->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogTransfer = $fetLogTranfer->fetch(PDO::FETCH_ASSOC)){
			$arrLogTransfer = array();
			$arrLogTransfer["REF_NO"] = $rowLogTransfer["ref_no"];
			$arrLogTransfer["MEMBER_NO"] = $rowLogTransfer["member_no"];
			$arrLogTransfer["CHANNEL"] = $rowLogTransfer["channel"];
			$arrLogTransfer["DEVICE_NAME"] = $rowLogTransfer["device_name"];
			$arrLogTransfer["TRANSACTION_TYPE_CODE"] = $rowLogTransfer["transaction_type_code"];
			$arrLogTransfer["FROM_ACCOUNT"] = $rowLogTransfer["from_account"];
			$arrLogTransfer["FROM_ACCOUNT_FORMAT"]= $lib->formataccount($rowLogTransfer["from_account"],$formatDept);
			$arrLogTransfer["DESTINATION_TYPE"] = $rowLogTransfer["destination_type"];
			$arrLogTransfer["DESTINATION"] = $rowLogTransfer["destination"];
			$arrLogTransfer["DESTINATION_FORMAT"]= $lib->formataccount($rowLogTransfer["destination"],$formatDept);
			$arrLogTransfer["TRANSFER_MODE"] = $rowLogTransfer["transfer_mode"];
			$arrLogTransfer["AMOUNT"] = $rowLogTransfer["amount"];
			$arrLogTransfer["AMOUNT_FORMAT"] = number_format($rowLogTransfer["amount"],2);
			$arrLogTransfer["FEE_AMT"] = $rowLogTransfer["fee_amt"];
			$arrLogTransfer["FEE_AMT_FORMAT"] = number_format($rowLogTransfer["fee_amt"],2);
			$arrLogTransfer["PENALTY_AMT"] = $rowLogTransfer["penalty_amt"];
			$arrLogTransfer["PENALTY_AMT_FORMAT"] = number_format( $rowLogTransfer["penalty_amt"],2);
			$arrLogTransfer["AMOUNT_RECEIVE"] = $rowLogTransfer["amount_receive"];
			$arrLogTransfer["AMOUNT_RECEIVE_FORMAT"] = number_format($rowLogTransfer["amount_receive"],2);
			$arrLogTransfer["TRANS_FLAG"] = $rowLogTransfer["trans_flag"];
			$arrLogTransfer["RESULT_TRANSACTION"] = $rowLogTransfer["result_transaction"];
			
			$arrLogTransfer["OPERATE_DATE"] =  $lib->convertdate($rowLogTransfer["operate_date"],'d m Y',true); 
			
			$arrLogTransfer["REF_NO_1"] = $rowLogTransfer["ref_no_1"];
			$arrLogTransfer["COOP_SLIP_NO"] = $rowLogTransfer["coop_slip_no"];
			$arrLogTransfer["REF_NO_SOURCE"] = $rowLogTransfer["ref_no_source"];
		
			$arrayGroup[] = $arrLogTransfer;
		}
		$arrayResult["LOG_TRANSFER_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>