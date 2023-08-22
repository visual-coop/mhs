<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constanttypeloan')){
		$arrayGroup = array();
		$arrayLoanCheckGrp = array();
		$fetchLoanTypeCheck = $conmysql->prepare("SELECT LOANTYPE_CODE,IS_CREDITLOAN,IS_LOANREQUEST FROM gcconstanttypeloan");
		$fetchLoanTypeCheck->execute();
		while($rowLoantypeCheck = $fetchLoanTypeCheck->fetch(PDO::FETCH_ASSOC)){
			$arrayLoanCheck = $rowLoantypeCheck;
			$arrayLoanCheckGrp[] = $arrayLoanCheck;
		}
		$fetchLoantype = $conoracle->prepare("SELECT LOANTYPE_CODE,LOANTYPE_DESC FROM LNLOANTYPE ORDER BY LOANTYPE_CODE ASC");
		$fetchLoantype->execute();
		while($rowLoantype = $fetchLoantype->fetch(PDO::FETCH_ASSOC)){
			$arrayLoantype = array();
			if(array_search($rowLoantype["LOANTYPE_CODE"],array_column($arrayLoanCheckGrp,'LOANTYPE_CODE')) === False){
				$arrayLoantype["IS_CREDITLOAN"] = "0";
				$arrayLoantype["IS_LOANREQUEST"] = "0";
			}else{
				$arrayLoantype["IS_CREDITLOAN"] = $arrayLoanCheckGrp[array_search($rowLoantype["LOANTYPE_CODE"],array_column($arrayLoanCheckGrp,'LOANTYPE_CODE'))]["IS_CREDITLOAN"];
				$arrayLoantype["IS_LOANREQUEST"] = $arrayLoanCheckGrp[array_search($rowLoantype["LOANTYPE_CODE"],array_column($arrayLoanCheckGrp,'LOANTYPE_CODE'))]["IS_LOANREQUEST"];
			}
			$arrayLoantype["LOANTYPE_CODE"] = $rowLoantype["LOANTYPE_CODE"];
			$arrayLoantype["LOANTYPE_DESC"] = $rowLoantype["LOANTYPE_DESC"];
			$arrayGroup[] = $arrayLoantype;
		}
		$arrayResult["LOAN_TYPE"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>