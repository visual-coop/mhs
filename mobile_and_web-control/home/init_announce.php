<?php
$anonymous = '';
$skip_autoload = true;
require_once('../autoload.php');

if(!$anonymous){
	$flag_granted = 'anonymous';
}else{
	$flag_granted = 'member';
}
$arrGroupAnn = array();

if(isset($dataComing["firsttime"]) && $dataComing["firsttime"] == '1'){
	$firstapp = '1';
}else{
	$firstapp = '-1';
}
$fetchAnn = $conmysql->prepare("SELECT priority,announce_cover,announce_title,announce_detail,announce_html,effect_date,id_announce,flag_granted,due_date,is_check,check_text,accept_text,cancel_text
												FROM gcannounce 
												WHERE effect_date IS NOT NULL and 
												((CASE WHEN priority = 'high' OR priority = 'ask'
												THEN 
													DATE_FORMAT(effect_date,'%Y-%m-%d %H:%i:%s') <= DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s')
												ELSE   
													DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s') BETWEEN DATE_FORMAT(effect_date,'%Y-%m-%d %H:%i:%s') AND DATE_FORMAT(due_date,'%Y-%m-%d %H:%i:%s')
												END ) OR first_time = :first_time) and flag_granted <> :flag_granted");
$fetchAnn->execute([
	':first_time' => $firstapp,
	':flag_granted' => $flag_granted
]);
while($rowAnn = $fetchAnn->fetch(PDO::FETCH_ASSOC)){
	$checkAcceptAnn = $conmysql->prepare("SELECT id_accept_ann FROM logacceptannounce WHERE member_no = :member_no and id_announce = :id_announce");
	$checkAcceptAnn->execute([
		':member_no' => $payload["member_no"],
		':id_announce' => $rowAnn["id_announce"]
	]);
	if($checkAcceptAnn->rowCount() == 0){
			$arrAnn = array();
			$arrAnn["FLAG_GRANTED"] = $rowAnn["flag_granted"];
			$arrAnn["PRIORITY"] = $rowAnn["priority"];
			$arrAnn["ID_ANNOUNCE"] = $rowAnn["id_announce"];
			$arrAnn["EFFECT_DATE"] = $rowAnn["effect_date"];
			$arrAnn["END_DATE"] = $rowAnn["due_date"];
			$arrAnn["ANNOUNCE_COVER"] = $rowAnn["announce_cover"];
			$arrAnn["ANNOUNCE_TITLE"] = $rowAnn["announce_title"];
			$arrAnn["ANNOUNCE_DETAIL"] = $rowAnn["announce_detail"];
			$arrAnn["IS_CHECK"] = $rowAnn["is_check"];
			$arrAnn["CHECK_TEXT"] = $rowAnn["check_text"];
			$arrAnn["ACCEPT_TEXT"] = $rowAnn["accept_text"];
			$arrAnn["CANCEL_TEXT"] = $rowAnn["cancel_text"];
			$arrAnn["ANNOUNCE_HTML"] = $rowAnn["announce_html"];
			$arrGroupAnn[] = $arrAnn;
	}
}
$arrayResult['ANNOUNCE'] = $arrGroupAnn;
$arrayResult['RESULT'] = TRUE;
require_once('../../include/exit_footer.php');
?>