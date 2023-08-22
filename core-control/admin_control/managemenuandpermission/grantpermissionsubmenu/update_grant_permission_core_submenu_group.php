<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','id_coremenu','status_permission'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$getIdCoreMenu = $conmysql->prepare("SELECT id_coremenu FROM coremenu WHERE id_coremenu =:id_coremenu ");
		$getIdCoreMenu->execute([
			':id_coremenu' => $dataComing["id_coremenu"]
		]);
		if($getIdCoreMenu->rowCount() > 0){
			$rowidcoremenu = $getIdCoreMenu->fetch(PDO::FETCH_ASSOC);
			$checkPermissionCoremenu = $conmysql->prepare("SELECT id_permission_menu FROM corepermissionmenu 
															WHERE username = :username  and id_coremenu = :id_coremenu");
			$checkPermissionCoremenu->execute([
				':username' => $dataComing["username"],
				':id_coremenu' => $rowidcoremenu["id_coremenu"]
			]);
			if($checkPermissionCoremenu->rowCount() > 0){
				$conmysql->beginTransaction();
				$updatePermitCoreMenu = $conmysql->prepare("UPDATE corepermissionmenu SET
																							is_use = :status_permission
																							WHERE id_coremenu = :id_coremenu AND  username = :username ");
				$updatePermitCoreMenu->execute([
					':id_coremenu' => $rowidcoremenu["id_coremenu"],
					':username' => $dataComing["username"],
					':status_permission' => $dataComing["status_permission"],
				]);
				$row_Permiss_submenu = $checkPermissionCoremenu->fetch(PDO::FETCH_ASSOC);
				$checkSubmenu = $conmysql->prepare("SELECT id_submenu FROM coresubmenu 
						WHERE id_coremenu = :id_coremenu  AND id_menuparent != '0' AND  menu_status ='1'
						ORDER BY id_submenu ASC");
				$checkSubmenu->execute([
					':id_coremenu' => $dataComing["id_coremenu"]
				]);
				$arrayGroupChkSubMenu = array();
				while($rowCheckSubmenu = $checkSubmenu->fetch(PDO::FETCH_ASSOC)){
					$arraycheckSubmenu = $rowCheckSubmenu["id_submenu"];
					$arrayGroupChkSubMenu[] = $arraycheckSubmenu;
				}
				$checkPermissSubmenu = $conmysql->prepare("SELECT id_submenu
																					FROM corepermissionsubmenu
																					WHERE id_permission_menu = :id_permission_menu
																					ORDER BY id_submenu ASC");
				$checkPermissSubmenu->execute([
				    ':id_permission_menu' => $row_Permiss_submenu["id_permission_menu"]
				]);
					$arrayGroupChkPermissSubMenu = array();
					while($rowCheckSubmenu = $checkPermissSubmenu->fetch(PDO::FETCH_ASSOC)){
						$arraycheckPermissSubmenu = $rowCheckSubmenu["id_submenu"];
					    $arrayGroupChkPermissSubMenu[] = $arraycheckPermissSubmenu;
					}
					
					if($arrayGroupChkPermissSubMenu !== $arrayGroupChkSubMenu){
						$bulk_insert = array();
						$not_menu = array_diff($arrayGroupChkSubMenu,$arrayGroupChkPermissSubMenu);
						foreach($not_menu as $value_diff){
							$bulk_insert[] = "(".$value_diff.",".$row_Permiss_submenu["id_permission_menu"].",'".$dataComing["status_permission"]."')";
						}
						$insertSubMenuPermit = $conmysql->prepare("INSERT INTO corepermissionsubmenu(id_submenu,id_permission_menu,is_use)
																VALUES".implode(',',$bulk_insert));
						if($insertSubMenuPermit->execute()){
							$conmysql->commit();
							$arrayStruc = [
								':menu_name' => "permissionmenu",
								':username' => $payload["username"],
								':use_list' => "change permission menu",
								':details' => 'insert permission group id '.$row_Permiss_submenu["id_permission_menu"].' to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
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
						$UpdateSubmenuPermit = $conmysql->prepare("UPDATE corepermissionsubmenu SET is_use = :status_permission
																	WHERE id_permission_menu = :id_permission_menu");
						if($UpdateSubmenuPermit->execute([
							':status_permission' => $dataComing["status_permission"],
							':id_permission_menu' => $row_Permiss_submenu["id_permission_menu"],
						])){
							$conmysql->commit();
							$arrayStruc = [
								':menu_name' => "permissionmenu",
								':username' => $payload["username"],
								':use_list' => "change permission menu",
								':details' => 'change permission group id '.$row_Permiss_submenu["id_permission_menu"].' to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
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
					}
			}else{
				$conmysql->beginTransaction();
				$insertPermitCoreMenu = $conmysql->prepare("INSERT INTO corepermissionmenu(id_coremenu,username,is_use)
																						VALUES(:id_coremenu,:username,:status_permission)");
				if($insertPermitCoreMenu->execute([
					':id_coremenu' => $rowidcoremenu["id_coremenu"],
					':username' => $dataComing["username"],
					':status_permission' => $dataComing["status_permission"]
				])){
					$checkSubmenu = $conmysql->prepare("SELECT id_permission_menu FROM corepermissionmenu
																			WHERE username = :username AND id_coremenu = :id_coremenu");
					$checkSubmenu->execute([
						':id_coremenu' => $dataComing["id_coremenu"],
						':username' => $dataComing["username"]
					]);
					$idSubMenu = $checkSubmenu->fetch(PDO::FETCH_ASSOC);
					$checkSubmenu = $conmysql->prepare("SELECT id_submenu FROM coresubmenu 
																			WHERE id_coremenu =:id_coremenu  AND id_menuparent != '0' AND  menu_status ='1'
																			ORDER BY id_submenu ASC");
					$checkSubmenu->execute([
						':id_coremenu' => $dataComing["id_coremenu"]
					]);
					$arrayGroupChkSubMenu = array();
					while($rowCheckSubmenu = $checkSubmenu->fetch(PDO::FETCH_ASSOC)){
						$arraycheckSubmenu = $rowCheckSubmenu["id_submenu"];
						$arrayGroupChkSubMenu[] = $arraycheckSubmenu;
					}
					$bulk_insert = array();
					foreach($arrayGroupChkSubMenu as $id_sub){
						$bulk_insert[] = "(".$id_sub.",".$idSubMenu["id_permission_menu"].",'".$dataComing["status_permission"]."')";
					}
					$insertSubMenuPermit = $conmysql->prepare("INSERT INTO corepermissionsubmenu(id_submenu,id_permission_menu,is_use)
																				VALUES".implode(',',$bulk_insert));
					if($insertSubMenuPermit->execute()){
						$conmysql->commit();
						$arrayStruc = [
							':menu_name' => "permissionmenu",
							':username' => $payload["username"],
							':use_list' => "change permission menu",
							':details' => 'insert permission group id '.$idSubMenu["id_permission_menu"].' to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
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
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่พบเมนูหลักของระบบ";
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