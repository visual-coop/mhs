<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id','rootmenu'],$dataComing)){
	if($func->check_permission_core($payload,$dataComing["rootmenu"],null,$conmysql)){
		if($payload["section_system"] == "root" || $payload["section_system"] == "root_test"){
			$arrayGroup = array();
			$fetchMenu = $conmysql->prepare("SELECT css.menu_name,css.page_name,css.id_submenu FROM coresubmenu css LEFT JOIN coremenu cm 
											ON css.id_coremenu = cm.id_coremenu
											WHERE css.id_menuparent = 0 and cm.root_path = :rootmenu and css.menu_status <> '-9' ORDER BY cm.coremenu_order,css.menu_order ASC");
			$fetchMenu->execute([':rootmenu' => $dataComing["rootmenu"]]);
			while($rowMenu = $fetchMenu->fetch()){
				$arrGroupRootMenu = array();
				$arrGroupRootMenu["ROOT_MENU_NAME"] = $rowMenu["menu_name"];
				$arrGroupRootMenu["ROOT_PATH"] = $rowMenu["page_name"];
				$fetchSubMenu = $conmysql->prepare("SELECT menu_name,page_name FROM coresubmenu
													WHERE id_menuparent = :id_submenu and menu_status <> '-9'
													ORDER BY menu_order ASC");
				$fetchSubMenu->execute([
					':id_submenu' => $rowMenu["id_submenu"]
				]);
				while($rowSubMenu = $fetchSubMenu->fetch()){
					if(isset($rowSubMenu["menu_name"])){
						$arrayGroupSubMenu = array();
						$arrayGroupSubMenu["SUB_MENU_NAME"] = $rowSubMenu["menu_name"];
						$arrayGroupSubMenu["SUB_PAGE_NAME"] = '/'.$dataComing["rootmenu"].'/'.$rowMenu["page_name"].'/'.$rowSubMenu["page_name"];
						($arrGroupRootMenu["SUB_MENU"])[] = $arrayGroupSubMenu;
					}
				}
				if(isset($arrGroupRootMenu["SUB_MENU"])){
					$arrayGroup[] = $arrGroupRootMenu;
				}
			}
			$arrayResult["SUB_MENU"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayGroup = array();
			$fetchMenu = $conmysql->prepare("SELECT css.menu_name,css.page_name,css.id_submenu FROM coresubmenu css LEFT JOIN coremenu cm 
											ON css.id_coremenu = cm.id_coremenu and cm.coremenu_status = '1'
											WHERE css.id_menuparent = 0 and cm.root_path = :rootmenu and css.menu_status = '1' ORDER BY cm.coremenu_order,css.menu_order ASC");
			$fetchMenu->execute([':rootmenu' => $dataComing["rootmenu"]]);
			while($rowMenu = $fetchMenu->fetch()){
				$arrGroupRootMenu = array();
				$arrGroupRootMenu["ROOT_MENU_NAME"] = $rowMenu["menu_name"];
				$arrGroupRootMenu["ROOT_PATH"] = $rowMenu["page_name"];
				$fetchSubMenu = $conmysql->prepare("SELECT csm.menu_name,csm.page_name FROM coresubmenu csm LEFT JOIN corepermissionsubmenu cpsm 
													ON csm.id_submenu = cpsm.id_submenu and cpsm.is_use = '1'
													LEFT JOIN corepermissionmenu cpm ON cpsm.id_permission_menu = cpm.id_permission_menu and cpm.is_use = '1'
													LEFT JOIN coremenu cm ON cpm.id_coremenu = cm.id_coremenu and cm.coremenu_status = '1'
													WHERE csm.menu_status = '1' and csm.id_menuparent = :id_submenu and cpm.username = :username 
													and csm.id_coremenu = cm.id_coremenu
													ORDER BY cm.coremenu_order,csm.menu_order ASC");
				$fetchSubMenu->execute([
					':id_submenu' => $rowMenu["id_submenu"],
					':username' => $payload["username"]
				]);
				while($rowSubMenu = $fetchSubMenu->fetch()){
					if(isset($rowSubMenu["menu_name"])){
						$arrayGroupSubMenu = array();
						$arrayGroupSubMenu["SUB_MENU_NAME"] = $rowSubMenu["menu_name"];
						$arrayGroupSubMenu["SUB_PAGE_NAME"] = '/'.$dataComing["rootmenu"].'/'.$rowMenu["page_name"].'/'.$rowSubMenu["page_name"];
						($arrGroupRootMenu["SUB_MENU"])[] = $arrayGroupSubMenu;
					}
				}
				if(isset($arrGroupRootMenu["SUB_MENU"])){
					$arrayGroup[] = $arrGroupRootMenu;
				}
			}
			$arrayResult["SUB_MENU"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			require_once('../../include/exit_footer.php');
		}
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