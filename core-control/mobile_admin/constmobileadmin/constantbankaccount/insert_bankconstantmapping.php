<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantbankaccount')){
		$updateConstants = $conmysql->prepare("INSERT INTO gcbankconstantmapping
		(bank_code, id_bankconstant)
		VALUES (:bank_code, :id_bankconstant)");
		if($updateConstants->execute([
			':bank_code' => $dataComing["bank_code"],
			':id_bankconstant' => $dataComing["id_bankconstant"]
		])){
			$arrayStruc = [
					':menu_name' => "constantbankaccount",
					':username' => $payload["username"],
					':use_list' =>"insert gcbankconstantmapping",
					':details' => "bank_code => ".$dataComing["bank_code"].
								" id_bankconstant => ".$dataComing["id_bankconstant"]
			];
			$log->writeLog('manageuser',$arrayStruc);
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มค่าคงที่ได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>