<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataMember = $conoracle->prepare("SELECT TRIM(card_person) as CARD_PERSON FROM mbmembmaster WHERE member_no = :member_no");
		$fetchDataMember->execute([
			':member_no' => $member_no
		]);
		$rowDataMember = $fetchDataMember->fetch(PDO::FETCH_ASSOC);
		if(isset($rowDataMember["CARD_PERSON"])){
			/*$arrGrpAccFee = array();
			$getDepositAcc = $conoracle->prepare("SELECT dp.DEPTACCOUNT_NO,dp.DEPTACCOUNT_NAME,dp.PRNCBAL,dt.DEPTTYPE_DESC 
												FROM dpdeptmaster dp LEFT JOIN dpdepttype dt ON dp.DEPTTYPE_CODE = dt.DEPTTYPE_CODE
												WHERE dp.member_no = :member_no and dp.deptclose_status = '0' and dp.depttype_code = '10'");
			$getDepositAcc->execute([':member_no' => $member_no]);
			while($rowDepAcc = $getDepositAcc->fetch(PDO::FETCH_ASSOC)){
				$arrAccFee = array();
				$arrAccFee['ACCOUNT_NO'] = $lib->formataccount($rowDepAcc["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrAccFee['ACCOUNT_NAME'] = TRIM($rowDepAcc["DEPTACCOUNT_NAME"]);
				$arrAccFee['BALANCE'] = number_format($rowDepAcc["PRNCBAL"],2);
				$arrAccFee['DEPTTYPE_DESC'] = $rowDepAcc["DEPTTYPE_DESC"];
				$arrGrpAccFee[] = $arrAccFee;
			}*/
			$arrayResult['REMARK_PAYFEE'] = $configError["REMARK_PAYFEE"][0][$lang_locale];
			//$arrayResult['ACCOUNT_PAYFEE'] = $arrGrpAccFee;
			
			if($payload["member_no"] == 'ETNMODE3'){
				$arrayResult['CITIZEN_ID'] = '1103653226136';
			}else{
				$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
			}
			$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($arrayResult['CITIZEN_ID']);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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
