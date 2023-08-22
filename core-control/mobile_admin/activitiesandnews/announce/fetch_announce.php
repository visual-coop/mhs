<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','announce')){
		$arrayExecute = array();
		$arrayGroup = array();
		
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute["start_date"] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute["end_date"] = $dataComing["end_date"];
		}
		$dateNow = date('YmdHis');
		$fetchAnnounce = $conmysql->prepare("SELECT id_announce,
													announce_cover,
													announce_title,
													announce_detail,
													announce_html,
													effect_date,
													due_date,
													is_show_between_due,
													is_update,
													priority,
													username,
													flag_granted,
													is_check,
													check_text,
													accept_text,
													cancel_text,
													date_format(effect_date,'%Y%m%d%H%i%s') AS effect_date_check,
													date_format(due_date,'%Y%m%d%H%i%s') AS due_date_check
											 FROM gcannounce
											 WHERE id_announce <> '-1' and effect_date IS NOT NULL
													".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and date_format(effect_date,'%Y-%m-%d') <= :start_date" : null)."
													".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(due_date,'%Y-%m-%d') >= :end_date" : null). " ORDER BY effect_date DESC LIMIT 20");
		$fetchAnnounce->execute($arrayExecute);		
		while($rowAnnounce = $fetchAnnounce->fetch(PDO::FETCH_ASSOC)){
			$arrGroupAnnounce = array();
			$arrGroupAnnounce["ID_ANNOUNCE"] = $rowAnnounce["id_announce"];
			$arrGroupAnnounce["ANNOUNCE_COVER"] = $rowAnnounce["announce_cover"];
			$arrGroupAnnounce["ANNOUNCE_TITLE"] = $rowAnnounce["announce_title"];
			$arrGroupAnnounce["ANNOUNCE_DETAIL"] = $rowAnnounce["announce_detail"];
			$arrGroupAnnounce["ANNOUNCE_HTML"] = $rowAnnounce["announce_html"];
			$arrGroupAnnounce["PRIORITY"] = $rowAnnounce["priority"];
			$arrGroupAnnounce["USERNAME"] = $rowAnnounce["username"];
			$arrGroupAnnounce["IS_CHECK"] = $rowAnnounce["is_check"];
			$arrGroupAnnounce["CHECK_TEXT"] = $rowAnnounce["check_text"];
			$arrGroupAnnounce["ACCEPT_TEXT"] = $rowAnnounce["accept_text"];
			$arrGroupAnnounce["CANCEL_TEXT"] = $rowAnnounce["cancel_text"];
			$arrGroupAnnounce["FLAG_GRANTED"] = $rowAnnounce["flag_granted"];	
			$arrGroupAnnounce["EFFECT_DATE"] = $rowAnnounce["effect_date"];		
			$arrGroupAnnounce["DUE_DATE"] = $rowAnnounce["due_date"];	
			$arrGroupAnnounce["DUE_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["due_date"],'d m Y',true); 
			$arrGroupAnnounce["IS_SHOW_BETWEEN_DUE"] = $rowAnnounce["is_show_between_due"];
			$arrGroupAnnounce["IS_UPDATE"] = $rowAnnounce["is_update"];
			$arrGroupAnnounce["EFFECT_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["effect_date"],'d m Y',true); 
						
			if(isset($rowAnnounce["effect_date"]) && (($rowAnnounce["effect_date_check"] <= $dateNow && $dateNow <= $rowAnnounce["due_date_check"]) || ($rowAnnounce["priority"] == 'high' || $rowAnnounce["priority"] == 'ask'))){
					$arrGroupAnnounce["ACTIVE"] = "now";
			}else if(isset($rowAnnounce["effect_date"]) && $rowAnnounce["effect_date_check"] > $dateNow){
					$arrGroupAnnounce["ACTIVE"] = "future"; 
 			}else{
				$arrGroupAnnounce["ACTIVE"] = "actived"; 
			}
			
			$arrayGroup[] = $arrGroupAnnounce;
		}
		$arrayResult["ANNOUNCE_DATA"] = $arrayGroup;

		$arrayResult["RESULT"] = TRUE;
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

