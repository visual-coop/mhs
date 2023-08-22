<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestformmember')){
		$arrayGroupAll = array();
		$arrayExecute = array();
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrayExecute[':member_no'] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		}
		if(isset($dataComing["member_name"]) && $dataComing["member_name"] != ''){
			$arrName = explode(' ',$dataComing["member_name"]);
			if(isset($arrName[1])){
				$arrayExecute[':member_name'] = '%'.$arrName[0].'%';
				$arrayExecute[':member_surname'] = '%'.$arrName[1].'%';
			}else{
				$arrayExecute[':member_name'] = '%'.$arrName[0].'%';
			}
		}
		if(empty($dataComing["member_no"]) && empty($dataComing["member_name"])){
			$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,
											mb.member_no
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.resign_status = 0".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and mb.member_no = :member_no" : null).
											(isset($dataComing["member_name"]) && $dataComing["member_name"] != '' ? " and (TRIM(mb.memb_name) LIKE :member_name" : null).
											(isset($arrayExecute[':member_surname']) ? " and TRIM(mb.memb_surname) LIKE :member_surname)" : (isset($arrayExecute[':member_name']) ? " OR TRIM(mb.memb_surname) LIKE :member_name)" : null))
											);
		$fetchMember->execute($arrayExecute);
		while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["NAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			$arrayGroup["MEMBER_NO"] = $rowMember["MEMBER_NO"];
			
			$arrayGroup["CREATE_DATE"] = null;
			$arrayGroup["UPDATE_DATE"] = null;
			$arrayGroup["UPDATE_USERNAME"] = null;
			$arrayGroup["IS_ALLOW"] = 0;
			
			$fetchAllow = $conmysql->prepare("SELECT member_no, create_date, update_date, update_username, is_allow
											FROM gcallowmemberreqloan WHERE member_no = :member_no");
			$fetchAllow->execute([
				':member_no' => $rowMember["MEMBER_NO"]
			]);
			
			while($rowAllow = $fetchAllow->fetch(PDO::FETCH_ASSOC)){
				$arrayReport = array();
				$arrayGroup["CREATE_DATE"] = $rowAllow["create_date"] ?? null;
				$arrayGroup["UPDATE_DATE"] = $rowAllow["update_date"] ?? null;
				$arrayGroup["UPDATE_USERNAME"] = $rowAllow["update_username"] ?? null;
				$arrayGroup["IS_ALLOW"] = $rowAllow["is_allow"] ?? 0;
			}
			
			$arrayGroupAll[] = $arrayGroup;
		}
		$arrayResult["USER_ACCOUNT"] = $arrayGroupAll;
		$arrayResult["RESULT"] = TRUE;
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