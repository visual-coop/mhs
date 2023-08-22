<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','page_name'],$dataComing)){
	if($func->check_permission_core($payload,'sms',$dataComing["page_name"])){
		$getTemplate = $conmysql->prepare("SELECT stp.id_smstemplate,stp.smstemplate_name,stp.smstemplate_body,sq.id_smsquery,smu.menu_name,sq.is_bind_param,
											sq.condition_target,sq.target_field
											FROM coresubmenu smu INNER JOIN smstopicmatchtemplate smt ON smu.id_submenu = smt.id_submenu
											INNER JOIN smstemplate stp ON smt.id_smstemplate = stp.id_smstemplate 
											LEFT JOIN smsquery sq ON stp.id_smsquery = sq.id_smsquery WHERE smu.page_name = :page_name and smu.menu_status = '1'
											and stp.is_use = '1' and smt.is_use = '1'");
		$getTemplate->execute([':page_name' => $dataComing["page_name"]]);
		if($getTemplate->rowCount() > 0){
			$arrTemplate = array();
			$rowTemplate = $getTemplate->fetch(PDO::FETCH_ASSOC);
			$arrTemplate["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
			$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
			$arrTemplate["TEMPLATE_MESSAGE"] = $rowTemplate["smstemplate_body"];
			$arrTemplate["ID_SMSQUERY"] = $rowTemplate["id_smsquery"];
			$arrTemplate["MENU_NAME"] = $rowTemplate["menu_name"];
			$arrTemplate["BIND_PARAM"] = $rowTemplate["is_bind_param"];
			$arrTemplate["CONDITION_TARGET"] = $rowTemplate["condition_target"];
			$arrTemplate["TARGET_FIELD"] = $rowTemplate["target_field"];
			$arrayResult["DATA"] = $arrTemplate;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../include/exit_footer.php');
		}else{
			http_response_code(204);
			require_once('../../../include/exit_footer.php');
		}
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