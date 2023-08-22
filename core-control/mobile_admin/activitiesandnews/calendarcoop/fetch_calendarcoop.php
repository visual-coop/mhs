<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','calendarcoop')){
		$arrayGroup = array();
		
		$fetchCalendar= $conmysql->prepare("SELECT id_task,task_topic,task_detail,start_date,end_date,event_start_time,event_end_time,is_settime,is_notify,is_notify_before,event_html
												FROM gctaskevent");
		$fetchCalendar->execute();
		while($rowCalendar = $fetchCalendar->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_TASK"] = $rowCalendar["id_task"];
			$arrConstans["TASK_TOPIC"] = $rowCalendar["task_topic"];
			$arrConstans["TASK_DETAIL"] = $rowCalendar["task_detail"];
			$arrConstans["EVENT_HTML"] = $rowCalendar["event_html"];
			$arrConstans["START_DATE"] = $rowCalendar["start_date"];
			$arrConstans["END_DATE"] = $rowCalendar["end_date"];
			$arrConstans["START_TIME"] = $rowCalendar["event_start_time"];
			$arrConstans["END_TIME"] = $rowCalendar["event_end_time"];
			$arrConstans["IS_SETTIME"] = $rowCalendar["is_settime"] == 1 ? true : false;
			$arrConstans["IS_NOTIFY"] = $rowCalendar["is_notify"] == 1 ? true : false;
			$arrConstans["IS_NOTIFY_BEFORE"] = $rowCalendar["is_notify_before"] == 1 ? true : false;
			$arrayGroup[] = $arrConstans;
		}
		
		$arrayResult["EVENT_DATA"] = $arrayGroup;
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