<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','status_permission'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroupCoreMenu = array();
		$arrayBulkInsert = array();
		$conmysql->beginTransaction();
		$insertSubMenuPermit = $conmysql->prepare("DELETE FROM corepermissionmenu WHERE username = :username");
		if($insertSubMenuPermit->execute([
			':username' => $dataComing["username"]
		])){
			$fetchCoreMenu = $conmysql->prepare("SELECT id_coremenu FROM coremenu WHERE coremenu_status = '1'");
			$fetchCoreMenu->execute();
			while($row_coreMenu = $fetchCoreMenu->fetch(PDO::FETCH_ASSOC)){
				$arr_coreMenu = array();
				$arr_coreMenu = $row_coreMenu["id_coremenu"];
				$arrayGroupCoreMenu[] = $arr_coreMenu;
			}
			$bulkInsert = array();
			foreach($arrayGroupCoreMenu as $coreMenu_id){
				$bulkInsert[] = "('".$coreMenu_id."','".$dataComing["username"]."','".$dataComing["status_permission"]."')";
			}
			$insertPermitCoreMenu = $conmysql->prepare("INSERT INTO corepermissionmenu(id_coremenu,username,is_use)
																			VALUES".implode(',',$bulkInsert));
			if($insertPermitCoreMenu->execute()){
				foreach($arrayGroupCoreMenu as $coreMenu_id){
					$checkSubmenu = $conmysql->prepare("SELECT id_permission_menu 
																			FROM corepermissionmenu 
																			WHERE username = :username  AND id_coremenu = :id_coremenu");
					$checkSubmenu->execute([
						':username' => $dataComing["username"],
						':id_coremenu' =>  $coreMenu_id
					]);	
					$rowSubMenuid = $checkSubmenu->fetch(PDO::FETCH_ASSOC);
					$fetchSubMenu_id = $conmysql->prepare("SELECT id_submenu FROM coresubmenu 
																				WHERE  id_menuparent <> '0' AND  menu_status ='1' AND id_coremenu = :id_coremenu ");
					$fetchSubMenu_id->execute([':id_coremenu' =>  $coreMenu_id]);
					while($row_subMenu = $fetchSubMenu_id->fetch(PDO::FETCH_ASSOC)){
						$arrayBulkInsert[] = "('".$row_subMenu["id_submenu"]."','".$rowSubMenuid["id_permission_menu"]."','".$dataComing["status_permission"]."')";
					}											
				}
				$insert_permiss_subcore = $conmysql->prepare("INSERT INTO corepermissionsubmenu (id_submenu,id_permission_menu,is_use)
																					VALUES".implode(',',$arrayBulkInsert));
				if($insert_permiss_subcore->execute()){
					$conmysql->commit();
					$arrayStruc = [
						':menu_name' => "permissionmenu",
						':username' => $payload["username"],
						':use_list' => "change permission menu",
						':details' => 'change permission all to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
					];
					$log->writeLog('editadmincontrol',$arrayStruc);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
					
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
			
		}
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