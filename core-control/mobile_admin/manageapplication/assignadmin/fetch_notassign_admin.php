<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		$arrayNotAdmin = array();
		$fetchNotAdmin = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE user_type = '0'");
		$fetchNotAdmin->execute();
		while($rowAdmin = $fetchNotAdmin->fetch(PDO::FETCH_ASSOC)){
			$arrayNotAdmin[] = $rowAdmin["member_no"];
		}
		$arrayResult['NOT_ADMIN'] = $arrayNotAdmin;
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
