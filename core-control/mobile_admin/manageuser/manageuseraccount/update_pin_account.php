<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		
		$repassword = $conmysql->prepare("UPDATE gcmemberaccount SET pin = null
										WHERE member_no = :member_no");
		if($repassword->execute([
				':member_no' => $dataComing["member_no"]
		])){
			$arrayStruc = [
				':menu_name' => "manageuseraccount",
				':username' => $payload["username"],
				':use_list' => "reset PIN",
				':details' => $dataComing["member_no"]
			];
			
			$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult["RESULT"] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถรีเซ็ต PIN ได้ กรุณาติดต่อผู้พัฒนา";
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