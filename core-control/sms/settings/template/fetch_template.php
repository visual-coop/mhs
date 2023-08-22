<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate') || $func->check_permission_core($payload,'sms','managetopic')
		|| $func->check_permission_core($payload,'sms','reportsmssuccess')){
		$arrTemplateGroup = array();
		if(isset($dataComing["id_smstemplate"])){
			$fetchTemplate = $conmysql->prepare("SELECT st.id_smstemplate,st.smstemplate_name,st.smstemplate_body,sq.id_smsquery,sq.sms_query,sq.set_column,
												sq.column_selected,sq.target_field,sq.is_bind_param,sq.condition_target,sq.is_stampflag,sq.stamp_table,sq.where_stamp
												FROM smstemplate st LEFT JOIN smsquery sq ON st.id_smsquery = sq.id_smsquery
												WHERE st.is_use = '1' and st.id_smstemplate = :id_smstemplate");
			$fetchTemplate->execute([':id_smstemplate' => $dataComing["id_smstemplate"]]);
			$rowTemplate = $fetchTemplate->fetch(PDO::FETCH_ASSOC);
			$arrTemplateGroup["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
			$arrTemplateGroup["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
			$arrTemplateGroup["TEMPLATE_BODY"] = $rowTemplate["smstemplate_body"];
			$arrTemplateGroup["ID_SMSQUERY"] = $rowTemplate["id_smsquery"];
			$arrTemplateGroup["SMS_QUERY"] = $rowTemplate["sms_query"];
			$arrTemplateGroup["COLUMN_SELECTED"] = explode(',',$rowTemplate["column_selected"]);
			$arrTemplateGroup["TARGET_FIELD"] = $rowTemplate["target_field"];
			$arrTemplateGroup["CONDITION_TARGET"] = $rowTemplate["condition_target"];
			$arrTemplateGroup["IS_STAMPFLAG"] = $rowTemplate["is_stampflag"];
			$arrTemplateGroup["STAMP_TABLE"] = $rowTemplate["stamp_table"];
			$arrTemplateGroup["SET_COLUMN"] = $rowTemplate["set_column"];
			$arrTemplateGroup["WHERE_STAMP"] = $rowTemplate["where_stamp"];
			$arrTemplateGroup["BIND_PARAM"] = $rowTemplate["is_bind_param"];
		}else{
			$fetchTemplate = $conmysql->prepare("SELECT id_smstemplate,smstemplate_name,smstemplate_body
												FROM smstemplate
												WHERE is_use = '1' ORDER BY id_smstemplate DESC");
			$fetchTemplate->execute();
			while($rowTemplate = $fetchTemplate->fetch(PDO::FETCH_ASSOC)){
				$arrTemplate = array();
				$arrTemplate["ID_TEMPLATE"] = $rowTemplate["id_smstemplate"];
				$arrTemplate["TEMPLATE_NAME"] = $rowTemplate["smstemplate_name"];
				$arrTemplate["TEMPLATE_BODY"] = $rowTemplate["smstemplate_body"];
				$arrTemplateGroup[] = $arrTemplate;
			}
		}
		$arrayResult['TEMPLATE'] = $arrTemplateGroup;
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