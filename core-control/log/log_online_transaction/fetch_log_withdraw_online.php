<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logwithdrawonline')){
		$arrayGroup = array();
		$fetLogTransection = $conmysql->prepare("SELECT
													trans.ref_no,
													trans.member_no,
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
													trans.ref_no_1,
													trans.coop_slip_no,
													trans.ref_no_source,
													login.device_name,
													login.channel
												FROM
													gctransaction trans
												LEFT JOIN gcuserlogin login ON
													login.id_userlogin = trans.id_userlogin
												WHERE
													trans.trans_flag = '-1'
													AND transfer_mode ='9'
												ORDER BY trans.operate_date DESC");
												
		$fetLogTransection->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogTransection = $fetLogTransection->fetch(PDO::FETCH_ASSOC)){
			$arrLogTransection = array();
			$arrLogTransection["REF_NO"] = $rowLogTransection["ref_no"];
			$arrLogTransection["MEMBER_NO"] = $rowLogTransection["member_no"];
			$arrLogTransection["CHANNEL"] = $rowLogTransection["channel"];
			$arrLogTransection["DEVICE_NAME"] = $rowLogTransection["device_name"];
			$arrLogTransection["TRANSACTION_TYPE_CODE"] = $rowLogTransection["transaction_type_code"];
			$arrLogTransection["FROM_ACCOUNT"] = $rowLogTransection["from_account"];
			$arrLogTransection["FROM_ACCOUNT_FORMAT"]= $lib->formataccount($rowLogTransection["from_account"],$formatDept);
			$arrLogTransection["DESTINATION_TYPE"] = $rowLogTransection["destination_type"];
			$arrLogTransection["DESTINATION"] = $rowLogTransection["destination"];
			$arrLogTransection["DESTINATION_FORMAT"]= $lib->formataccount($rowLogTransection["destination"],$formatDept);
			$arrLogTransection["TRANSFER_MODE"] = $rowLogTransection["transfer_mode"];
			$arrLogTransection["AMOUNT"] = $rowLogTransection["amount"];
			$arrLogTransection["AMOUNT_FORMAT"] = number_format($rowLogTransection["amount"],2);
			$arrLogTransection["FEE_AMT"] = $rowLogTransection["fee_amt"];
			$arrLogTransection["FEE_AMT_FORMAT"] = number_format($rowLogTransection["fee_amt"],2);
			$arrLogTransection["PENALTY_AMT"] = $rowLogTransection["penalty_amt"];
			$arrLogTransection["PENALTY_AMT_FORMAT"] = number_format( $rowLogTransection["penalty_amt"],2);
			$arrLogTransection["AMOUNT_RECEIVE"] = $rowLogTransection["amount_receive"];
			$arrLogTransection["AMOUNT_RECEIVE_FORMAT"] = number_format($rowLogTransection["amount_receive"],2);
			$arrLogTransection["TRANS_FLAG"] = $rowLogTransection["trans_flag"];
			$arrLogTransection["RESULT_TRANSACTION"] = $rowLogTransection["result_transaction"];
			
			$arrLogTransection["OPERATE_DATE"] =  $lib->convertdate($rowLogTransection["operate_date"],'d m Y',true); 
			
			$arrLogTransection["REF_NO_1"] = $rowLogTransection["ref_no_1"];
			$arrLogTransection["COOP_SLIP_NO"] = $rowLogTransection["coop_slip_no"];
			$arrLogTransection["REF_NO_SOURCE"] = $rowLogTransection["ref_no_source"];

			$arrayGroup[] = $arrLogTransection;
		}
		$arrayResult["LOG_TRANSECTION_DATA"] = $arrayGroup;
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