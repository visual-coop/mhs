<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestformmember')){
		$fetchAllow = $conmysql->prepare("SELECT member_no, create_date, update_date, update_username, is_allow
											FROM gcallowmemberreqloan WHERE is_allow = '1'");
		$fetchAllow->execute();
		
		$arrayGroupMemb = array();
		
		while($rowAllow = $fetchAllow->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["CREATE_DATE"] = $rowAllow["create_date"] ?? null;
			$arrayGroup["UPDATE_DATE"] = $rowAllow["update_date"] ?? null;
			$arrayGroup["UPDATE_USERNAME"] = $rowAllow["update_username"] ?? null;
			$arrayGroup["IS_ALLOW"] = $rowAllow["is_allow"] ?? 0;
			
			$member_no = strtolower($lib->mb_str_pad($rowAllow["member_no"]));
			$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,
										mb.member_no
										FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
										WHERE mb.resign_status = 0 and mb.member_no = :member_no");
			$fetchMember->execute([
				':member_no' => $member_no
			]);
			
			while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
				$arrayGroup["NAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
				$arrayGroup["MEMBER_NO"] = $rowMember["MEMBER_NO"];
			}
			$arrayGroupMemb[] = $arrayGroup;
		}
		
		$arrayResult["USER_ACCOUNT"] = $arrayGroupMemb;
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