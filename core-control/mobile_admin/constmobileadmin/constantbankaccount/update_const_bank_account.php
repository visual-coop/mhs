<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','bank_code','bank_name','bank_short_name','bank_format_account','bank_format_account_hide','id_palette'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantbankaccount')){
		$updateConstants = $conmysql->prepare("UPDATE csbankdisplay 
											   SET bank_name = :bank_name,
												   bank_short_name = :bank_short_name,
												   bank_format_account = :bank_format_account,
												   bank_format_account_hide = :bank_format_account_hide,
												   id_palette = :id_palette,
												   fee_deposit = :fee_deposit,
												   fee_withdraw = :fee_withdraw
											   WHERE bank_code = :bank_code");
		if($updateConstants->execute([
			':bank_code' => $dataComing["bank_code"],
			':bank_name' => $dataComing["bank_name"],
			':bank_short_name' => $dataComing["bank_short_name"],
			':bank_format_account' => $dataComing["bank_format_account"],
			':bank_format_account_hide' => $dataComing["bank_format_account_hide"],
			':id_palette' => $dataComing["id_palette"],
			':fee_deposit' => $dataComing["fee_deposit"],
			':fee_withdraw' => $dataComing["fee_withdraw"]
		])){
			$arrayStruc = [
					':menu_name' => "constantbankaccount",
					':username' => $payload["username"],
					':use_list' =>"update csbankdisplay",
					':details' => "bank_code => ".$dataComing["bank_code"].
								" bank_name => ".$dataComing["bank_name"].
								" bank_short_name => ".$dataComing["bank_short_name"].
								" bank_format_account => ".$dataComing["bank_format_account"].
								" bank_format_account_hide => ".$dataComing["bank_format_account_hide"].
								" id_palette => ".$dataComing["id_palette"].
								" fee_deposit => ".$dataComing["fee_deposit"].
								" fee_withdraw => ".$dataComing["fee_withdraw"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ประเภทบัญชีเงินฝากนี้ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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