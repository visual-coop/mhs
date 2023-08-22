<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepositonline')){
		$arrayGroup = array();
		$fetLogDepositOnline = $conmysql->prepare("SELECT 
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
												INNER JOIN gcuserlogin login
												ON login.id_userlogin = trans.id_userlogin
												WHERE trans.trans_flag = '1'
													  AND transfer_mode ='9'
												ORDER BY trans.operate_date DESC");
		$fetLogDepositOnline->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogDepositOnline = $fetLogDepositOnline->fetch(PDO::FETCH_ASSOC)){
			$arrLogDepositOnline = array();
			$arrLogDepositOnline["REF_NO"] = $rowLogDepositOnline["ref_no"];
			$arrLogDepositOnline["MEMBER_NO"] = $rowLogDepositOnline["member_no"];
			$arrLogDepositOnline["CHANNEL"] = $rowLogDepositOnline["channel"];
			$arrLogDepositOnline["DEVICE_NAME"] = $rowLogDepositOnline["device_name"];
			$arrLogDepositOnline["TRANSACTION_TYPE_CODE"] = $rowLogDepositOnline["transaction_type_code"];
			$arrLogDepositOnline["FROM_ACCOUNT"] = $rowLogDepositOnline["from_account"];
			$arrLogDepositOnline["FROM_ACCOUNT_FORMAT"]= $lib->formataccount($rowLogDepositOnline["from_account"],$formatDept);
			$arrLogDepositOnline["DESTINATION_TYPE"] = $rowLogDepositOnline["destination_type"];
			$arrLogDepositOnline["DESTINATION"] = $rowLogDepositOnline["destination"];
			$arrLogDepositOnline["DESTINATION_FORMAT"]= $lib->formataccount($rowLogDepositOnline["destination"],$formatDept);
			$arrLogDepositOnline["TRANSFER_MODE"] = $rowLogDepositOnline["transfer_mode"];
			$arrLogDepositOnline["AMOUNT"] = $rowLogDepositOnline["amount"];
			$arrLogDepositOnline["AMOUNT_FORMAT"] = number_format($rowLogDepositOnline["amount"],2);
			$arrLogDepositOnline["FEE_AMT"] = $rowLogDepositOnline["fee_amt"];
			$arrLogDepositOnline["FEE_AMT_FORMAT"] = number_format($rowLogDepositOnline["fee_amt"],2);
			$arrLogDepositOnline["PENALTY_AMT"] = $rowLogDepositOnline["penalty_amt"];
			$arrLogDepositOnline["PENALTY_AMT_FORMAT"] = number_format( $rowLogDepositOnline["penalty_amt"],2);
			$arrLogDepositOnline["AMOUNT_RECEIVE"] = $rowLogDepositOnline["amount_receive"];
			$arrLogDepositOnline["AMOUNT_RECEIVE_FORMAT"] = number_format($rowLogDepositOnline["amount_receive"],2);
			$arrLogDepositOnline["TRANS_FLAG"] = $rowLogDepositOnline["trans_flag"];
			$arrLogDepositOnline["RESULT_TRANSACTION"] = $rowLogDepositOnline["result_transaction"];
			
			$arrLogDepositOnline["OPERATE_DATE"] =  $lib->convertdate($rowLogDepositOnline["operate_date"],'d m Y',true); 
			
			$arrLogDepositOnline["REF_NO_1"] = $rowLogDepositOnline["ref_no_1"];
			$arrLogDepositOnline["COOP_SLIP_NO"] = $rowLogDepositOnline["coop_slip_no"];
			$arrLogDepositOnline["REF_NO_SOURCE"] = $rowLogDepositOnline["ref_no_source"];
		
			$arrayGroup[] = $arrLogDepositOnline;
		}
		$arrayResult["LOG_DEPOSIT_DATA"] = $arrayGroup;
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