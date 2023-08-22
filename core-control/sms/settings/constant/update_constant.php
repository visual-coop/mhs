<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_constant','value_constant'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstant')){
		if(isset($dataComing["value_constant"]) || $dataComing["value_constant"] != ''){
			$updateConstants = $conmysql->prepare("UPDATE smsconstantsystem SET smscs_value = :constant_value
													WHERE id_smscssystem = :id_constant");
			if($updateConstants->execute([
				':constant_value' => $dataComing["value_constant"],
				':id_constant' => $dataComing["id_constant"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถแก้ไขเป็นค่าว่างได้";
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