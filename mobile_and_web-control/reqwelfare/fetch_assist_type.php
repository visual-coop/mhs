<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAssistGrp = array();
		$fetchCateGrp = $conoracle->prepare("SELECT MEMBCAT_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$fetchCateGrp->execute([':member_no' => $member_no]);
		$rowCateGrp = $fetchCateGrp->fetch(PDO::FETCH_ASSOC);
		$fetchAssistType = $conmysql->prepare("SELECT welfare_type_code,welfare_type_desc FROM gcconstantwelfare WHERE 
												(member_cate_code = :membcat_code OR member_cate_code = 'AL') and is_use = '1'");
		$fetchAssistType->execute([':membcat_code' => $rowCateGrp["MEMBCAT_CODE"]]);
		while($rowAssistType = $fetchAssistType->fetch(PDO::FETCH_ASSOC)){
			$arrAssist = array();
			$arrAssist["WELFARE_CODE"] = $rowAssistType["welfare_type_code"];
			$arrAssist["WELFARE_DESC"] = $rowAssistType["welfare_type_desc"];
			$arrAssistGrp[] = $arrAssist;
		}
		$arrayResult['WELFARE_TYPE'] = $arrAssistGrp;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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