<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$fetchUserControl = $conmysql->prepare("SELECT username FROM coreuser where user_status = '1'");
	$fetchUserControl->execute();
	$arrayGroupAll = array();
	while($rowUserControl = $fetchUserControl->fetch()){
		$arrayGroupAll[] = $rowUserControl["username"];
	}
	$arrayResult['USERNAME'] = $arrayGroupAll;
	$arrayResult['RESULT'] = TRUE;
	require_once('../../include/exit_footer.php');
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
?>
