<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InsureInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchinSureInfo = $conoracle->prepare("SELECT ist.INSURETYPE_DESC,isit.INSITEMTYPE_DESC,isit.SIGN_FLAG,issm.PREMIUM_PAYMENT
												FROM insinsuremaster ism LEFT JOIN insinsuretype ist ON ism.insuretype_code = ist.insuretype_code
												LEFT JOIN insinsurestatement issm ON ism.insurance_no = issm.insurance_no
												LEFT JOIN insucfinsitemtype isit ON issm.insitemtype_code = isit.insitemtype_code
												WHERE ism.insurance_status = '1' and ism.member_no = :member_no ORDER BY issm.SEQ_NO DESC");
		$fetchinSureInfo->execute([
			':member_no' => $member_no
		]);
		$arrGroupAllIns = array();
		while($rowInsure = $fetchinSureInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayInsure = array();
			$arrGroupIns = array();
			$arrayInsure["PAYMENT"] = number_format($rowInsure["PREMIUM_PAYMENT"],2);
			$arrayInsure["SIGN_FLAG"] = $rowInsure["SIGN_FLAG"];
			$arrayInsure["INS_STM_TYPE"] = $rowInsure["INSITEMTYPE_DESC"];
			$arrGroupIns["INS_TYPE"] = $rowInsure["INSURETYPE_DESC"];
			if(array_search($rowInsure["INSURETYPE_DESC"],array_column($arrGroupAllIns,'INS_TYPE')) === False){
				($arrGroupIns['STATEMENT'])[] = $arrayInsure;
				$arrGroupAllIns[] = $arrGroupIns;
			}else{
				($arrGroupAllIns[array_search($rowInsure["INSURETYPE_DESC"],array_column($arrGroupAllIns,'INS_TYPE'))]["STATEMENT"])[] = $arrayInsure;
			}
		}
		$arrayResult['INSURE'] = $arrGroupAllIns;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
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
	require_once('../../include/exit_footer.php');
	
}
?>