<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','blacklistdevice')){
		$arrayGroup = array();
		$fetchUserAccount = $conmysql->prepare("SELECT gdb.id_blacklist,gdb.member_no,gdb.type_blacklist,gdb.blacklist_date,
											(SELECT device_name from gctoken WHERE id_token = gdb.new_id_token) AS new_device,
											(SELECT device_name from gctoken WHERE id_token = gdb.old_id_token) AS old_device
											FROM gcdeviceblacklist gdb
											WHERE gdb.is_blacklist = '1'");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["ID_BLACKLIST"] = $rowUserlogin["id_blacklist"];
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupUserAcount["TYPE_BLACKLIST"] = $rowUserlogin["type_blacklist"];
			$arrGroupUserAcount["BLACKLIST_DATE"] = $lib->convertdate($rowUserlogin["blacklist_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["NEW_DEVICE"] = $rowUserlogin["new_device"];
			$arrGroupUserAcount["OLD_DEVICE"] = $rowUserlogin["old_device"];
			
			if($rowUserlogin["type_blacklist"] == "1"){
				$arrGroupUserAcount["TYPE_BLACKLIST_CODE"] = "Root";
			}else if($rowUserlogin["type_blacklist"] == "0"){
				$arrGroupUserAcount["TYPE_BLACKLIST_CODE"] = "เข้าสู่ระบบด้วยเครื่องใหม่";
			}else{
				$arrGroupUserAcount["TYPE_BLACKLIST_CODE"] = "-";
			}
			
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["BLACKLIST_DEVICE"] = $arrayGroup;
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