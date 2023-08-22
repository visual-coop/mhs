<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms',null)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		$fetchSMSsystemSend = $conmysql->prepare("SELECT
													COUNT(MEMBER_NO) AS C_NAME,
													DATE_FORMAT(receive_date, '%m') AS MONTH
												FROM
													gchistory
												WHERE
													receive_date <= DATE_SUB(receive_date, INTERVAL -6 MONTH)
												GROUP BY
													DATE_FORMAT(receive_date, '%m')");
		$fetchSMSsystemSend->execute();
		while($rowSMSsendSytem = $fetchSMSsystemSend->fetch(PDO::FETCH_ASSOC)){
			$arrGroupSystemSendSMS = array();
			$arrGroupSystemSendSMS["MONTH"] = $rowSMSsendSytem["MONTH"];;
			$arrGroupSystemSendSMS["AMT"] = $rowSMSsendSytem["C_NAME"];
			$arrayGroup[] = $arrGroupSystemSendSMS;
		}
					
		$arrayResult["SYSTEM_SEND_SMS_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>