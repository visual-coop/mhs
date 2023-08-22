<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead')){
		$arrGroupSendAhead = array();
		if(isset($dataComing["id_sendahead"])){
			$fetchGroup = $conmysql->prepare("SELECT id_sendahead,send_topic,send_message,destination,
												send_date,send_platform,send_image,create_by,is_import
												FROM smssendahead WHERE is_use = '1' and id_sendahead = :id_sendahead");
			$fetchGroup->execute([':id_sendahead' => $dataComing["id_sendahead"]]);
			while($rowSendAhead = $fetchGroup->fetch(PDO::FETCH_ASSOC)){
				$arrGroupSendAhead["ID_SENDAHEAD"] = $rowSendAhead["id_sendahead"];
				$arrGroupSendAhead["SEND_TOPIC"] = $rowSendAhead["send_topic"];
				$arrGroupSendAhead["SEND_MESSAGE"] = $rowSendAhead["send_message"];
				$arrGroupSendAhead["SEND_DATE"] = $lib->convertdate($rowSendAhead["send_date"],'D m Y H i s',true);
				$arrGroupSendAhead["SEND_DATE_NOT_FORMAT"] = $rowSendAhead["send_date"];
				if($rowSendAhead["is_import"] == '0'){
					$arrGroupSendAhead["DESTINATION"] = explode(',',$rowSendAhead["destination"]);
				}else{
					$arrGroupSendAhead["DESTINATION"] = json_decode($rowSendAhead["destination"]);
				}
				$arrGroupSendAhead["IS_IMPORT"] = $rowSendAhead["is_import"];
				$arrGroupSendAhead["SEND_PLATFORM"] = $rowSendAhead["send_platform"];
				$arrGroupSendAhead["CREATE_BY"] = $rowSendAhead["create_by"];
				$arrGroupSendAhead["SEND_IMAGE"] = isset($rowSendAhead["send_image"]) ? $config["URL_SERVICE"].$rowSendAhead["send_image"] : null;
			}
		}else{
			$fetchSendAhead = $conmysql->prepare("SELECT id_sendahead,send_message,destination,send_date,create_by,is_import
													FROM smssendahead WHERE is_use = '1'");
			$fetchSendAhead->execute();
			while($rowSendAhead = $fetchSendAhead->fetch(PDO::FETCH_ASSOC)){
				$arrSendAhead = array();
				$arrSendAhead["ID_SENDAHEAD"] = $rowSendAhead["id_sendahead"];
				$arrSendAhead["SEND_MESSAGE"] = $rowSendAhead["send_message"];
				$arrSendAhead["CREATE_BY"] = $rowSendAhead["create_by"];
				$arrSendAhead["SEND_DATE"] = $lib->convertdate($rowSendAhead["send_date"],'D m Y H i s',true);
				if($rowSendAhead["is_import"] == '0'){
					$arrSendAhead["DESTINATION"] = explode(',',$rowSendAhead["destination"]);
				}else{
					$arrSendAhead["DESTINATION"] = json_decode($rowSendAhead["destination"]);
				}
				$arrSendAhead["IS_IMPORT"] = $rowSendAhead["is_import"];
				$arrGroupSendAhead[] = $arrSendAhead;
			}
		}
		$arrayResult['SEND_AHEAD'] = $arrGroupSendAhead;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
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