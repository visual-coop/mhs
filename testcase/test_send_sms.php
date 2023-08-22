<?php
date_default_timezone_set("Asia/Bangkok");
$cmd = 'SENDMSG';
$from = 'Forest_COOP';
$to = '0820161367';
$code = '45140200001';
$ctype = 'UNICODE';
$text = 'ทดสอบบบบบ';
$data = "CMD=".$cmd."&FROM=".$from."&TO=".$to."&CODE=".$code."&REPORT=Y&CHARGE=Y&CTYPE=".$ctype."&CONTENT=".$text."";
send_sms_ais($data);

function send_sms_ais($url,$SMS_TEXT,$FROM_PHONENO,$PHONE_NUMBER,$SENDER_CODE,$CHARGE){
								//$url = $_REQUEST["URL"];
								//$CONTENT=$SMS_TEXT;
								$CONTENT= unicodeMessageEncode($SMS_TEXT);
								$myvars ="CMD=SENDMSG"."&".
												"FROM=".$FROM_PHONENO."&".
												"TO="."66".substr($PHONE_NUMBER,1)."&".
												"CODE=".$SENDER_CODE."&".
												"REPORT=Y"."&".
												"CHARGE=".$CHARGE."&".
												"CTYPE=LUNICODE"."&".
												"CONTENT=".$CONTENT."&";
								
								//echo "<br/>".$myvars ;				
								$ch = curl_init( $url );
								curl_setopt( $ch, CURLOPT_POST, 1);
								curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
								curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
								curl_setopt( $ch, CURLOPT_HEADER, 0);
								curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt( $ch, CURLOPT_HTTPHEADER,array('Content-Type: text/plain; charset=UTF-8'));
								$response = curl_exec( $ch );
			return  $response ;					
}

?>