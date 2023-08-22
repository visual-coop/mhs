<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','template_name','template_body'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate')){
		$id_smsquery = null;
		$conmysql->beginTransaction();
		if(isset($dataComing["query_template_spc_"]) && isset($dataComing["column_selected"]) && sizeof($dataComing["column_selected"]) > 0){
			if($dataComing["is_stampflag"] == '1'){
				if(empty($dataComing["condition_target"])){
					$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_selected,target_field,is_stampflag,stamp_table,where_stamp,set_column,create_by)
															VALUES(:sms_query,:column_selected,:target_field,'1',:stamp_table,:where_stamp,:set_column,:username)");
					if($insertSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':stamp_table' => $dataComing["stamp_table"],
						':where_stamp' => $dataComing["where_stamp"]." and 1=1",
						':set_column' => $dataComing["set_column"],
						':username' => $payload["username"]
					])){
						$id_smsquery = $conmysql->lastInsertId();
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_selected,target_field,condition_target,is_stampflag,stamp_table,where_stamp,set_column,is_bind_param,create_by)
															VALUES(:sms_query,:column_selected,:target_field,:condition_target,'1',:stamp_table,:where_stamp,'1',:username)");
					if($insertSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':condition_target' => $dataComing["condition_target"],
						':stamp_table' => $dataComing["stamp_table"],
						':where_stamp' => $dataComing["where_stamp"]." and 1=1",
						':set_column' => $dataComing["set_column"],
						':username' => $payload["username"]
					])){
						$id_smsquery = $conmysql->lastInsertId();
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}
			}else{
				if(empty($dataComing["condition_target"])){
					$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_selected,target_field,create_by)
															VALUES(:sms_query,:column_selected,:target_field,:username)");
					if($insertSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':username' => $payload["username"]
					])){
						$id_smsquery = $conmysql->lastInsertId();
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_selected,target_field,condition_target,is_bind_param,create_by)
															VALUES(:sms_query,:column_selected,:target_field,:condition_target,'1',:username)");
					if($insertSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':condition_target' => $dataComing["condition_target"],
						':username' => $payload["username"]
					])){
						$id_smsquery = $conmysql->lastInsertId();
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}
			}
		}
		$insertTemplate = $conmysql->prepare("INSERT INTO smstemplate(smstemplate_name,smstemplate_body,create_by,id_smsquery) 
												VALUES(:smstemplate_name,:smstemplate_body,:username,:id_smsquery)");
		if($insertTemplate->execute([
			':smstemplate_name' => $dataComing["template_name"],
			':smstemplate_body' => $dataComing["template_body"],
			':username' => $payload["username"],
			':id_smsquery' => $id_smsquery
		])){
			$conmysql->commit();
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มเทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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