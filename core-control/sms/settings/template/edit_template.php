<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','template_name','template_body','id_template'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate')){
		$conmysql->beginTransaction();
		if(isset($dataComing["id_smsquery"]) && $dataComing["id_smsquery"] != ""){
			if(isset($dataComing["query_template_spc_"]) && isset($dataComing["column_selected"])){
				if(empty($dataComing["condition_target"])){
					$updateSmsQuery = $conmysql->prepare("UPDATE smsquery SET sms_query = :sms_query,column_selected = :column_selected,
															target_field = :target_field,is_stampflag = :is_stampflag,stamp_table = :stamp_table,where_stamp = :where_stamp,
															set_column = :set_column,is_bind_param = '0',condition_target = null WHERE id_smsquery = :id_smsquery");
					if($updateSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':is_stampflag' => $dataComing["is_stampflag"],
						':stamp_table' => $dataComing["stamp_table"],
						':where_stamp' => $dataComing["where_stamp"],
						':set_column' => $dataComing["set_column"],
						':id_smsquery' => $dataComing["id_smsquery"]
					])){}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$updateSmsQuery = $conmysql->prepare("UPDATE smsquery SET sms_query = :sms_query,column_selected = :column_selected,is_stampflag = :is_stampflag,
															stamp_table = :stamp_table,where_stamp = :where_stamp,set_column = :set_column,
															target_field = :target_field,is_bind_param = '1',condition_target = :condition_target WHERE id_smsquery = :id_smsquery");
					if($updateSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':is_stampflag' => $dataComing["is_stampflag"],
						':stamp_table' => $dataComing["stamp_table"],
						':where_stamp' => $dataComing["where_stamp"],
						':set_column' => $dataComing["set_column"],
						':condition_target' => $dataComing["condition_target"],
						':id_smsquery' => $dataComing["id_smsquery"]
					])){}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}
			}else{
				$updateSmsQuery = $conmysql->prepare("UPDATE smsquery SET sms_query = null,column_selected = null,
														target_field = null,is_bind_param = '0',condition_target = null WHERE id_smsquery = :id_smsquery");
				if($updateSmsQuery->execute([
					':id_smsquery' => $dataComing["id_smsquery"]
				])){}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}
			$editTemplate = $conmysql->prepare("UPDATE smstemplate SET smstemplate_name = :smstemplate_name,smstemplate_body = :smstemplate_body
											WHERE id_smstemplate = :id_smstemplate");
			if($editTemplate->execute([
				':smstemplate_name' => $dataComing["template_name"],
				':smstemplate_body' => $dataComing["template_body"],
				':id_smstemplate' => $dataComing["id_template"]
			])){
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			if(isset($dataComing["query_template_spc_"]) && $dataComing["query_template_spc_"] != "" && isset($dataComing["column_selected"])){
				if(empty($dataComing["condition_target"]) || $dataComing["condition_target"] == ""){
					$insertSmsQuery = $conmysql->prepare("INSERT INTO smsquery(sms_query,column_selected,target_field,create_by)
															VALUES(:sms_query,:column_selected,:target_field,:username)");
					if($insertSmsQuery->execute([
						':sms_query' => $dataComing["query_template_spc_"],
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':username' => $payload["username"]
					])){
						$id_smsquery = $conmysql->lastInsertId();
						$editTemplate = $conmysql->prepare("UPDATE smstemplate SET smstemplate_name = :smstemplate_name,smstemplate_body = :smstemplate_body,id_smsquery = :id_smsquery
														WHERE id_smstemplate = :id_smstemplate");
						if($editTemplate->execute([
							':smstemplate_name' => $dataComing["template_name"],
							':smstemplate_body' => $dataComing["template_body"],
							':id_smstemplate' => $dataComing["id_template"],
							':id_smsquery' => $id_smsquery
						])){
							$conmysql->commit();
							$arrayResult['RESULT'] = TRUE;
							require_once('../../../../include/exit_footer.php');
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
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
						':sms_query' => $query,
						':column_selected' => implode(',',$dataComing["column_selected"]),
						':target_field' => $dataComing["target_field"],
						':condition_target' => $dataComing["condition_target"],
						':username' => $payload["username"]
					])){
						$id_smsquery = $conmysql->lastInsertId();
						$editTemplate = $conmysql->prepare("UPDATE smstemplate SET smstemplate_name = :smstemplate_name,smstemplate_body = :smstemplate_body,id_smsquery = :id_smsquery
														WHERE id_smstemplate = :id_smstemplate");
						if($editTemplate->execute([
							':smstemplate_name' => $dataComing["template_name"],
							':smstemplate_body' => $dataComing["template_body"],
							':id_smstemplate' => $dataComing["id_template"],
							':id_smsquery' => $id_smsquery
						])){
							$conmysql->commit();
							$arrayResult['RESULT'] = TRUE;
							require_once('../../../../include/exit_footer.php');
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มคิวรี่เทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}
			}else{
				$editTemplate = $conmysql->prepare("UPDATE smstemplate SET smstemplate_name = :smstemplate_name,smstemplate_body = :smstemplate_body
														WHERE id_smstemplate = :id_smstemplate");
				if($editTemplate->execute([
					':smstemplate_name' => $dataComing["template_name"],
					':smstemplate_body' => $dataComing["template_body"],
					':id_smstemplate' => $dataComing["id_template"]
				])){
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}
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