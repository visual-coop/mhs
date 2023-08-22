<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_template','topic_name'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic') && is_numeric($dataComing["id_template"])){
		$conmysql->beginTransaction();
		$page_name = $lib->randomText('all',6);
		$insertSmsMenu = $conmysql->prepare("INSERT INTO coresubmenu(menu_name,page_name,menu_order,create_by,id_menuparent,id_coremenu)
												VALUES(:topic_name,:page_name,1,:username,8,1)");
		if($insertSmsMenu->execute([
			':topic_name' => $dataComing["topic_name"],
			':page_name'=> $page_name,
			':username' => $payload["username"]
		])){
			$id_submenu = $conmysql->lastInsertId();
			$insertTopicMatch = $conmysql->prepare("INSERT INTO smstopicmatchtemplate(id_submenu,id_smstemplate) 
													VALUES(:id_submenu,:id_smstemplate)");
			if($insertTopicMatch->execute([
				':id_submenu' => $id_submenu,
				':id_smstemplate' => $dataComing["id_template"]
			])){
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเชื่อมเทมเพลตกับหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
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