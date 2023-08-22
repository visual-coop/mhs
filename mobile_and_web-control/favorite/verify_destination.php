<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','destination','flag_trans'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FavoriteAccount')){
		if($dataComing["flag_trans"] == "TRN"){
			$checkAccDes = $conoracle->prepare("SELECT deptaccount_no FROM dpdeptmaster WHERE deptaccount_no = :destination");
			$checkAccDes->execute([':destination' => $dataComing["destination"]]);
			$rowAccDes = $checkAccDes->fetch(PDO::FETCH_ASSOC);
			if(isset($rowAccDes["deptaccount_no"])){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0047";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>