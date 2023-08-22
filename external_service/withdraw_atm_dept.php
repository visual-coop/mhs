<?php
require_once('autoload.php');

if($lib->checkCompleteArgument(['sigma_key'],$payload)){
	$updateBindAcc = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = '1' WHERE sigma_key = :sigma_key");
	if($updateBindAcc->execute([
		':sigma_key' => $payload["sigma_key"]
	])){
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS9006";
		$arrayResult['RESPONSE_MESSAGE'] = "Cannot update status bindaccount";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS9004";
	$arrayResult['RESPONSE_MESSAGE'] = "Payload not complete";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>