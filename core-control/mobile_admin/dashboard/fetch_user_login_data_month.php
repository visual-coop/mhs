<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		$arrayGroupWeb = array();
		$arrayGroupMobile = array();
		$arrGroupMonth = array();
		$fetchUserlogin = $conmysql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,DATE_FORMAT(login_date,'%m') as MONTH,DATE_FORMAT(login_date,'%Y') as YEAR
			FROM gcuserlogin
				WHERE login_date <= DATE_SUB(login_date,INTERVAL -6 MONTH)
				GROUP BY DATE_FORMAT(login_date,'%m') ORDER BY login_date ASC");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["YEAR"] = $rowUserlogin["YEAR"] + 543;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroup[] = $arrGroupRootUserlogin;
		}
		
		$fetchUserloginWeb = $conmysql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,DATE_FORMAT(login_date,'%m') as MONTH,DATE_FORMAT(login_date,'%Y') as YEAR
			FROM gcuserlogin
				WHERE login_date <= DATE_SUB(login_date,INTERVAL -6 MONTH) AND channel = 'web'
				GROUP BY DATE_FORMAT(login_date,'%m') ORDER BY login_date ASC");
		$fetchUserloginWeb->execute();
		while($rowUserlogin = $fetchUserloginWeb->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["YEAR"] = $rowUserlogin["YEAR"] + 543;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroupWeb[] = $arrGroupRootUserlogin;
		}
		
		$fetchUserloginMobile = $conmysql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,DATE_FORMAT(login_date,'%m') as MONTH,DATE_FORMAT(login_date,'%Y') as YEAR
			FROM gcuserlogin
				WHERE login_date <= DATE_SUB(login_date,INTERVAL -6 MONTH) AND channel = 'mobile_app'
				GROUP BY DATE_FORMAT(login_date,'%m') ORDER BY login_date ASC");
		$fetchUserloginMobile->execute();
		while($rowUserlogin = $fetchUserloginMobile->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["YEAR"] = $rowUserlogin["YEAR"] + 543;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroupMobile[] = $arrGroupRootUserlogin;
		}
					
		$arrayResult["USER_LOGIN_DATA"] = $arrayGroup;
		$arrayResult["USER_LOGIN_DATA_WEB"] = $arrayGroupWeb;
		$arrayResult["USER_LOGIN_DATA_MOBILE"] = $arrayGroupMobile;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>