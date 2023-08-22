<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['username','password','device_name','unique_id'],$dataComing)){
	$checkPassword = $conmysql->prepare("SELECT cs.section_system,cs.system_assign,cu.password
										FROM coreuser cu LEFT JOIN coresectionsystem cs ON cu.id_section_system = cs.id_section_system
										WHERE cu.username = :username and cu.user_status = '1'");
	$checkPassword->execute([
		':username' => $dataComing["username"]
	]);
	if($checkPassword->rowCount() > 0){
		$rowPassword = $checkPassword->fetch(PDO::FETCH_ASSOC);
		if(password_verify($dataComing["password"], $rowPassword['password'])){
			$arrPayload = array();
			$arrPayload['section_system'] = $rowPassword['section_system'];
			$arrPayload['username'] = $dataComing["username"];
			$arrPayload['exp'] = time() + 21600;
			$access_token = $jwt_token->customPayload($arrPayload, $config["SECRET_KEY_CORE"]);
			if($dataComing["username"] != 'dev@mode'){
				$updateOldUser = $conmysql->prepare("UPDATE coreuserlogin SET is_login = '0' WHERE username = :username");
				$updateOldUser->execute([':username' => $dataComing["username"]]);
			}
			$insertLog = $conmysql->prepare("INSERT INTO coreuserlogin(username,unique_id,device_name,auth_token,logout_date)
											VALUES(:username,:unique_id,:device_name,:token,:logout_date)");
			if($insertLog->execute([
				':username' => $dataComing["username"],
				':unique_id' => $dataComing["unique_id"],
				':device_name' => $dataComing["device_name"],
				':token' => $access_token,
				':logout_date' => date('Y-m-d H:i:s', strtotime('+1 hour'))
			])){
				$arrayResult["SECTION_ASSIGN"] = $rowPassword["system_assign"];
				$arrayResult["USERNAME"] = $dataComing["username"];
				$arrayResult["ACCESS_TOKEN"] = $access_token;
				$arrayResult["RESULT"] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเข้าสู่ระบบได้ กรุณาลองใหม่อีกครั้ง";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE'] = "รหัสผ่านไม่ถูกต้อง";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE'] = "ไม่พบข้อมูลผู้ใช้งานกรุณาตรวจสอบ ชื่อผู้ใช้ / รหัสผ่าน หรือฐานข้อมูล อีกครั้ง";
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
?>