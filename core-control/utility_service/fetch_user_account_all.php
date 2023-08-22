<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$arrayMember = array();
		$member_acc = array();
		$arrayExecute = array();
		
		if(isset($dataComing["search_value"]) && $dataComing["search_value"] != ''){
			$arrName = explode(' ',$dataComing["search_value"]);
			if(isset($arrName[1])){
				$arrayExecute[':search_value'] = '%'.$arrName[0].'%';
				$arrayExecute[':search_value_1'] = '%'.$arrName[1].'%';
			}else{
				$arrayExecute[':search_value'] = '%'.$arrName[0].'%';
			}
		}
		
		$fetchDataOra = $conoracle->prepare("SELECT mb.sms_mobilephone as MEM_TELMOBILE,mb.MEMBER_NO,mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME 
											FROM mbmembmaster mb
											LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code											
											WHERE mb.resign_status = '0' 
											AND (mb.member_no LIKE :search_value".
											(isset($arrayExecute[':search_value_1']) ? " OR (trim(mb.memb_name) LIKE :search_value AND trim(mb.memb_surname) LIKE :search_value_1)" 
											: " OR (trim(mb.memb_name) LIKE :search_value OR trim(mb.memb_surname) LIKE :search_value)").
											")");
		$fetchDataOra->execute($arrayExecute);
		while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
				$arrayMT = array();
				$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
				$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
				$arrayMT["MEMBER_FULLNAME"] = $rowDataOra["PRENAME_DESC"].$rowDataOra["MEMB_NAME"]." ".$rowDataOra["MEMB_SURNAME"];
				$arrayMember[] = $rowDataOra["MEMBER_NO"];
				$member_acc[] = $arrayMT;
		}
		
		$arrayResult["MEMBER_ACC"] = $member_acc;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
?>