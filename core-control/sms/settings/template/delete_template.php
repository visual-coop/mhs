<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_smstemplate'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetemplate')){
		
		$fetchCheckTemplate = $conmysql->prepare("SELECT temp.id_submenu,temp.id_smstemplate,temp.is_use as temp_isuse,sub.menu_status FROM `smstopicmatchtemplate` temp
			JOIN coresubmenu sub On temp.id_submenu = sub.id_submenu
			WHERE temp.is_use = '1' AND sub.menu_status = '1' AND id_smstemplate = :id_smstemplate");
			
		$fetchCheckTemplate->execute([':id_smstemplate' => $dataComing["id_smstemplate"]]);
		if($fetchCheckTemplate->fetch()){
				$arrayResult['RESPONSE'] = "ไม่สามารถลบเทมเพลตได้ เนื่องจากมีการใช้งานอยู่";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
		}else{
			$unuseTemplate = $conmysql->prepare("UPDATE smstemplate SET is_use = '-9' WHERE id_smstemplate = :id_smstemplate");
			if($unuseTemplate->execute([
				':id_smstemplate' => $dataComing["id_smstemplate"]
			])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถลบเทมเพลตได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
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