<?php
require_once('../../../../autoloadConnection.php');

$getMessageQueue = $conmysql->prepare("SELECT send_topic,send_message,destination, 
										FROM smssendahead WHERE is_use = '1' ");
?>