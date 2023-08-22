<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InsureInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchinSureInfo = $conoracle->prepare("SELECT ism.INSMAIN_NO,ISM.PROTECT_AMT,ISM.INSURE_AMT,INM.AGENT_DESC as COMPANY_NAME
												,ISC.INSTYPE_DESC,INM.PROTECTEND_DATE,INM.PROTECTSTART_DATE
												FROM isinsuremaster ism LEFT JOIN isinsuremain inm ON ism.INSMAIN_NO = inm.INSMAIN_NO
												LEFT JOIN iscfinstype isc ON ism.INSTYPE_CODE = isc.INSTYPE_CODE
												WHERE ism.member_no = :member_no and ism.insure_status = '1' 
												and trunc(sysdate) BETWEEN trunc(ism.PROTECTSTART_DATE) and trunc(ism.PROTECTEND_DATE)");
		$fetchinSureInfo->execute([
			':member_no' => $member_no
		]);
		$arrGroupAllIns = array();
		while($rowInsure = $fetchinSureInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayInsure = array();
			$arrayInsure["INSURE_NO"] = $rowInsure["INSMAIN_NO"];
			$arrayInsure["PREMIUM_AMT"] = number_format($rowInsure["INSURE_AMT"],2);
			$arrayInsure["PROTECT_AMT"] = number_format($rowInsure["PROTECT_AMT"],2);
			$arrayInsure["STARTSAFE_DATE"] = $lib->convertdate($rowInsure["PROTECTSTART_DATE"],'D m Y');
			$arrayInsure["ENDSAFE_DATE"] = $lib->convertdate($rowInsure["PROTECTEND_DATE"],'D m Y');
			$arrayInsure["INSURE_TYPE"] = $rowInsure["INSTYPE_DESC"];
			$arrayInsure["COMPANY_NAME"] = $rowInsure["COMPANY_NAME"];
			$arrayInsure["IS_STM"] = FALSE;
			$arrGroupAllIns[] = $arrayInsure;
		}
		$arrayResult['INSURE'] = $arrGroupAllIns;
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