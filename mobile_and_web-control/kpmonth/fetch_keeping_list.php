<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentMonthlyInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$limit_period = $func->getConstant('limit_kpmonth');
		$dateshow_kpmonth = $func->getConstant('dateshow_kpmonth');
		$keep_forward = $func->getConstant('process_keep_forward');
		$dateNow = date('d');
		$arrayGroupPeriod = array();
		$this_period = (date('Y') + 543).date('m');
		if($keep_forward == '1'){
			$getMaxRecv = $conoracle->prepare("SELECT max(recv_period) as MAX_RECV_PERIOD FROM kptempreceive WHERE rownum <= 1");
			$getMaxRecv->execute();
			$rowMaxRecv = $getMaxRecv->fetch(PDO::FETCH_ASSOC);
			$max_recv = (int) substr($rowMaxRecv["MAX_RECV_PERIOD"],4);
			$thisMonth = date("m");
			if($max_recv > $thisMonth){
				$getPeriodKP = $conoracle->prepare("SELECT * from ((
															select recv_period from kpmastreceive where member_no = :member_no
														) ORDER BY recv_period DESC) where rownum <= :limit_period");
			}else{
				if($dateNow >= $dateshow_kpmonth){
					$getPeriodKP = $conoracle->prepare("SELECT * from ((
															select recv_period from kpmastreceive where member_no = :member_no
														UNION  
															select recv_period  from kptempreceive where member_no = :member_no
														) ORDER BY recv_period DESC) where rownum <= :limit_period");
				}else{
					$getPeriodKP = $conoracle->prepare("SELECT * from ((
															select recv_period from kpmastreceive where member_no = :member_no
														) ORDER BY recv_period DESC) where rownum <= :limit_period");
				}
			}
		}else{
			if($dateNow >= $dateshow_kpmonth){
				$getPeriodKP = $conoracle->prepare("SELECT * from ((
														select recv_period from kpmastreceive where member_no = :member_no
													UNION  
														select recv_period  from kptempreceive where member_no = :member_no
													) ORDER BY recv_period DESC) where rownum <= :limit_period");
			}else{
				$getPeriodKP = $conoracle->prepare("SELECT * from ((
														select recv_period from kpmastreceive where member_no = :member_no
													) ORDER BY recv_period DESC) where rownum <= :limit_period");
			}
		}
		$getPeriodKP->execute([
				':member_no' => $member_no,
				':limit_period' => $limit_period
		]);
		while($rowPeriod = $getPeriodKP->fetch(PDO::FETCH_ASSOC)){
			$arrKpmonth = array();
			$arrKpmonth["PERIOD"] = $rowPeriod["RECV_PERIOD"];
			$arrKpmonth["MONTH_RECEIVE"] = $lib->convertperiodkp($rowPeriod["RECV_PERIOD"]);
			$getKPDetail = $conoracle->prepare("select * from (
													(select kpr.RECEIPT_NO,NVL(sum_item.ITEM_PAYMENT,kpr.RECEIVE_AMT) as RECEIVE_AMT from kpmastreceive kpr,(SELECT NVL(SUM(kpd.ITEM_PAYMENT * kut.sign_flag),0) as ITEM_PAYMENT FROM kpmastreceivedet kpd
													LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													where kpd.member_no = :member_no and kpd.recv_period = :recv_period) sum_item
													where kpr.member_no = :member_no and kpr.recv_period = :recv_period )
												UNION
													(select kpr.RECEIPT_NO,NVL(sum_item.ITEM_PAYMENT,kpr.RECEIVE_AMT) as RECEIVE_AMT from kptempreceive kpr,(SELECT NVL(SUM(kpd.ITEM_PAYMENT * kut.sign_flag),0) as ITEM_PAYMENT FROM kptempreceivedet kpd
													LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													where kpd.member_no = :member_no and kpd.recv_period = :recv_period) sum_item
													where kpr.member_no = :member_no and kpr.recv_period = :recv_period )
												)");
			$getKPDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $rowPeriod["RECV_PERIOD"]
			]);
			$rowKPDetali = $getKPDetail->fetch(PDO::FETCH_ASSOC);
			$arrKpmonth["SLIP_NO"] = $rowKPDetali["RECEIPT_NO"];
			$arrKpmonth["RECEIVE_AMT"] = number_format($rowKPDetali["RECEIVE_AMT"],2);
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