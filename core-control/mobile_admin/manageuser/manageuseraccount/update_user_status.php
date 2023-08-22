<?php
require_once('../../../autoload.php');


if($lib->checkCompleteArgument(['unique_id','member_no','account_status'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$menuName = "manageuseraccount";
		$list_name = null;
		if($dataComing["account_status"]=='1'){
			$queryString = 'UPDATE gcmemberaccount SET account_status = prev_acc_status,prev_acc_status = :account_status,counter_wrongpass = 0
								WHERE member_no = :member_no';
			if(isset($dataComing["unlock_type"]) && $dataComing["unlock_type"] != ""){
				$list_name =  $dataComing["unlock_type"]."/unlock account";
			}else{
				$list_name =  "unlock account";
			}
		}else{
			$queryString = 'UPDATE gcmemberaccount SET prev_acc_status = account_status,account_status = :account_status,counter_wrongpass = 0
								WHERE member_no = :member_no';
			$list_name = "lock account";
		}
		$updateStatus = $conmysql->prepare($queryString);
		if($updateStatus->execute([
			':account_status' => $dataComing["account_status"],
			':member_no' => $dataComing["member_no"]
		])){
			$arrayStruc = [
				':menu_name' => $menuName,
				':username' => $payload["username"],
				':use_list' => $list_name,
				':details' => $dataComing["member_no"]
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถล็อคหรือปลดล็อคบัญชีได้ กรุณาติดต่อผู้พัฒนา";
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
