<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logallowacctransation')){
		$arrayGroup = array();
		$fetLogAllowAccountTransation = $conmysql->prepare("
															SELECT
																alow.id_userallowacctran,
																alow.member_no,
																alow.deptaccount_no,
																alow.limit_transaction_amt,
																alow.create_date,
																cont.dept_type_code,
																cont.allow_transaction
															FROM
																gcuserallowacctransaction alow
															INNER JOIN gcconstantaccountdept cont ON
																alow.id_accountconstant = cont.id_accountconstant");
		$fetLogAllowAccountTransation->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogAllowAccountTransation = $fetLogAllowAccountTransation->fetch(PDO::FETCH_ASSOC)){
			$arrLogAllowTransation = array();
			$arrLogAllowTransation["ID_USERALLOWACCTRAN"] = $rowLogAllowAccountTransation["id_userallowacctran"];
			$arrLogAllowTransation["CREATE_DATE"] = $lib->convertdate( $rowLogAllowAccountTransation["create_date"],'d m Y',true); 
			$arrLogAllowTransation["MEMBER_NO"] = $rowLogAllowAccountTransation["member_no"];
			$arrLogAllowTransation["DEPTACCOUNT_NO"] = $rowLogAllowAccountTransation["deptaccount_no"];
			$arrLogAllowTransation["DEPTACCOUNT_NO_FORMAT"]= $lib->formataccount($rowLogAllowAccountTransation["deptaccount_no"],$formatDept);
			$arrLogAllowTransation["DESTINATION_TYPE"] = $rowLogAllowAccountTransation["destination_type"];
			$arrLogAllowTransation["LIMIT_AMT"] = $rowLogAllowAccountTransation["limit_transaction_amt"];
			$arrLogAllowTransation["LIMIT_AMT_FORMAT"] = number_format($rowLogAllowAccountTransation["limit_transaction_amt"],2);
			$arrLogAllowTransation["DEPT_TYPE_DESC"] = $rowLogAllowAccountTransation["dept_type_desc"];
			$arrLogAllowTransation["ALLOW_TRANSACTION"] = $rowLogAllowAccountTransation["allow_transaction"];
		
			$arrayGroup[] = $arrLogAllowTransation;
		}
		$arrayResult["LOG_ALLOW_ACCOUNT_TRANSATION_DATA"] = $arrayGroup;
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