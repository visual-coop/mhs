<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT id_bankconstant, transaction_cycle, max_numof_deposit, max_numof_withdraw, min_deposit, max_deposit, min_withdraw, max_withdraw FROM gcbankconstant");
		$fetchConstant->execute();
		while($rowAccount = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_BANKCONSTANT"] = $rowAccount["id_bankconstant"];
			$arrConstans["TRANSACTION_CYCLE"] = $rowAccount["transaction_cycle"];
			$arrConstans["MAX_NUMOF_DEPOSIT"] = $rowAccount["max_numof_deposit"];
			$arrConstans["MAX_NUMOF_WITHDRAW"] = $rowAccount["max_numof_withdraw"];
			$arrConstans["MIN_DEPOSIT"] = $rowAccount["min_deposit"];
			$arrConstans["MAX_DEPOSIT"] = $rowAccount["max_deposit"];
			$arrConstans["MIN_WITHDRAW"] = $rowAccount["min_withdraw"];
			$arrConstans["MAX_WITHDRAW"] = $rowAccount["max_withdraw"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["BANK_CONSTANT"] = $arrayGroup;
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