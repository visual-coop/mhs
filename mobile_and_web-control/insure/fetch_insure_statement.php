<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','insure_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InsureStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchinSureSTM = $conoracle->prepare("SELECT int.INSITEMTYPE_DESC,ins.PREMIUM_PAYMENT,ins.OPERATE_DATE,ins.REF_SLIPNO
												FROM insinsurestatement ins LEFT JOIN insucfinsitemtype int ON ins.INSITEMTYPE_CODE = int.INSITEMTYPE_CODE
												WHERE ins.insurance_no = :insure_no ORDER BY ins.SEQ_NO DESC");
		$fetchinSureSTM->execute([
			':insure_no' => $dataComing["insure_no"]
		]);
		$arrGroupInsSTM = array();
		while($rowInsure = $fetchinSureSTM->fetch(PDO::FETCH_ASSOC)){
			$arrayInsure = array();
			$arrayInsure["PAYMENT"] = number_format($rowInsure["PREMIUM_PAYMENT"],2);
			$arrayInsure["INSURE_DATE"] = $lib->convertdate($rowInsure["OPERATE_DATE"],'D m Y');
			$arrayInsure["INSURESTM_TYPE"] = $rowInsure["INSITEMTYPE_DESC"];
			$arrayInsure["SLIP_NO"] = $rowInsure["REF_SLIPNO"];
			$arrGroupInsSTM[] = $arrayInsure;
		}
		$arrayResult['INSURE_STM'] = $arrGroupInsSTM;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>