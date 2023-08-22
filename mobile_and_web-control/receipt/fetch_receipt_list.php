<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$limit_period = $func->getConstant('limit_kpmonth');
		$arrayGroupPeriod = array();
		$getPeriodKP = $conoracle->prepare("SELECT * from ((
															SELECT KPSLIP_NO,RECV_PERIOD,KEEPING_STATUS,RECEIPT_DATE,RECEIPT_NO,RECEIVE_AMT
															from kpmastreceive where member_no = :member_no
														) ORDER BY recv_period DESC) where rownum <= :limit_period");
		$getPeriodKP->execute([
				':member_no' => $member_no,
				':limit_period' => $limit_period
		]);
		while($rowPeriod = $getPeriodKP->fetch(PDO::FETCH_ASSOC)){
			$arrKpmonth = array();
			$arrKpmonth["PERIOD"] = $rowPeriod["RECV_PERIOD"];
			$arrKpmonth["MONTH_RECEIVE"] = $lib->convertperiodkp(TRIM($rowPeriod["RECV_PERIOD"]));
			$getKPDetail = $conoracle->prepare("SELECT NVL(SUM(kpd.ITEM_PAYMENT * kut.sign_flag),0) as ITEM_PAYMENT 
													FROM kpmastreceivedet kpd
													LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													where kpd.member_no = :member_no and kpd.kpslip_no = :kpslip_no");
			$getKPDetail->execute([
				':member_no' => $member_no,
				':kpslip_no' => $rowPeriod["KPSLIP_NO"]
			]);
			$rowKPDetali = $getKPDetail->fetch(PDO::FETCH_ASSOC);
			$arrKpmonth["SLIP_NO"] = $rowPeriod["RECEIPT_NO"];
			$arrKpmonth["SLIP_DATE"] = $lib->convertdate($rowPeriod["RECEIPT_DATE"],'d m Y');
			if(isset($rowPeriod["RECEIVE_AMT"]) && $rowPeriod["RECEIVE_AMT"] != ""){
				$arrKpmonth["RECEIVE_AMT"] = number_format($rowPeriod["RECEIVE_AMT"],2);
			}else{
				$arrKpmonth["RECEIVE_AMT"] = number_format($rowKPDetali["ITEM_PAYMENT"],2);
			}
			if($rowPeriod["KEEPING_STATUS"] == '-99' || $rowPeriod["KEEPING_STATUS"] == '-9'){
				$arrKpmonth["IS_CANCEL"] = TRUE;
			}else{
				$arrKpmonth["IS_CANCEL"] = FALSE;
			}
			$arrayGroupPeriod[] = $arrKpmonth;
		}
		$arrayResult['KEEPING_LIST'] = $arrayGroupPeriod;
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