<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		

		$arrGroupMonth = array();
		$fetchUserlogin = $conmysql->prepare("SELECT
												DATE_FORMAT(login_date, '%m') AS MONTH,
												DATE_FORMAT(login_date, '%Y') AS YEAR,
												IFNULL((
													SELECT COUNT(member_no) as C_MEM_LOGIN 
													FROM gcuserlogin 
													WHERE
														DATE_FORMAT(login_date, '%m') = MONTH 
														and is_login = '1'  AND channel = 'web'
													GROUP BY DATE_FORMAT(login_date, '%m')),0) as C_MEM_LOGIN_WEB,
												IFNULL((
													SELECT COUNT(member_no) as C_MEM_LOGIN 
													FROM gcuserlogin 
													WHERE
														DATE_FORMAT(login_date, '%m') = MONTH 
														and is_login = '1'  AND channel = 'mobile_app'
													GROUP BY DATE_FORMAT(login_date, '%m')),0) as C_MEM_LOGIN_MOBILE,
												IFNULL((
													SELECT COUNT(member_no) as C_MEM_LOGIN 
													FROM gcuserlogin 
													WHERE
														DATE_FORMAT(login_date, '%m') = MONTH 
														and is_login = '1' 
													GROUP BY DATE_FORMAT(login_date, '%m')),0) as C_MEM_LOGIN,
												IFNULL((
													SELECT COUNT(member_no) as C_MEM_LOGOUT FROM gcuserlogin 
													WHERE
														DATE_FORMAT(login_date, '%m') = MONTH and is_login <> '1' 
													GROUP BY DATE_FORMAT(login_date, '%m')),0) as C_MEM_LOGOUT,
												IFNULL((
													SELECT COUNT(member_no) as C_MEM_LOGOUT FROM gcuserlogin 
													WHERE
														DATE_FORMAT(login_date, '%m') = MONTH and is_login <> '1'  AND channel = 'web'
													GROUP BY DATE_FORMAT(login_date, '%m')),0) as C_MEM_LOGOUT_WEB,
												IFNULL((
													SELECT COUNT(member_no) as C_MEM_LOGOUT FROM gcuserlogin 
													WHERE
														DATE_FORMAT(login_date, '%m') = MONTH and is_login <> '1'  AND channel = 'mobile_app'
													GROUP BY DATE_FORMAT(login_date, '%m')),0) as C_MEM_LOGOUT_MOBILE
											FROM
												gcuserlogin
											WHERE
												login_date <= DATE_SUB(login_date, INTERVAL -6 MONTH)
											GROUP BY
												DATE_FORMAT(login_date, '%m')
											ORDER BY login_date ASC");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["YEAR"] = $rowUserlogin["YEAR"]+543;
			$arrGroupRootUserlogin["C_MEM_LOGIN"] = $rowUserlogin["C_MEM_LOGIN"];
			$arrGroupRootUserlogin["C_MEM_LOGIN_WEB"] = $rowUserlogin["C_MEM_LOGIN_WEB"];
			$arrGroupRootUserlogin["C_MEM_LOGIN_MOBILE"] = $rowUserlogin["C_MEM_LOGIN_MOBILE"];
			$arrGroupRootUserlogin["C_MEM_LOGOUT"] = $rowUserlogin["C_MEM_LOGOUT"];
			$arrGroupRootUserlogin["C_MEM_LOGOUT_WEB"] = $rowUserlogin["C_MEM_LOGOUT_WEB"];
			$arrGroupRootUserlogin["C_MEM_LOGOUT_MOBILE"] = $rowUserlogin["C_MEM_LOGOUT_MOBILE"];
			$arrayGroup[] = $arrGroupRootUserlogin;
		}
					
		$arrayResult["USER_LOGIN_LOGOUT_DATA"] = $arrayGroup;
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