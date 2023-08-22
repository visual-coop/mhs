<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="description" content="Free Web tutorials">
<meta name="keywords" content="HTML,CSS,XML,JavaScript">
<meta name="author" content="John Doe">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css?family=Prompt:400&display=swap" rel="stylesheet" />
<title>ตารางประมาณการชำระ</title>
<style>
* {
	font-family: 'Prompt', sans-serif;
}
body {
	background-color: #E9EDFB;
	margin: 2px;
}
table {
	border-spacing: unset;
	width: 100%;
}
table thead {
	background-color: #FFFFFF;
	box-shadow: 0 4px 15px 0px #00000021;
	border-radius: 5px;
}
table thead th {
	padding: 15px 20px;
	text-align: center;
}

.body-card-table tr {
	background-color: white;
	text-align: center;
}
.body-card-table td {
	padding: 15px 20px;
}
</style>
</head>

<body>
<table>
<thead>
<tr>
<th>งวด</th>
<th>วันที่ชำระ</th>
<th>จำนวนวัน</th>
<th>เงินต้น</th>
<th>ดอกเบี้ย</th>
<th>ยอดชำระ</th>
<th>หนี้คงเหลือ</th>
</tr>
</thead>
<tbody class="body-card-table">
<?php
if(isset($arrPayment)){
for($i = 0;$i < sizeof($arrPayment);$i++){ ?>
<tr>
	<td>
	<?php echo $arrPayment[$i]["PERIOD"];?>
	</td>
	<td>
	<?php echo $arrPayment[$i]["MUST_PAY_DATE"];?>
	</td>
	<td>
	<?php echo $arrPayment[$i]["DAYS"];?>
	</td>
	<td>
	<?php echo $arrPayment[$i]["PRN_AMOUNT"];?>
	</td>
	<td>
	<?php echo $arrPayment[$i]["INTEREST"];?>
	</td>
	<td>
	<?php echo $arrPayment[$i]["PAYMENT_PER_PERIOD"];?>
	</td>
	<td>
	<?php echo $arrPayment[$i]["PRINCIPAL_BALANCE"];?>
	</td>
</tr>
<?php 
}
}
?>

</tbody>
<tfoot>
<tr>
<td colspan="4" style="background-color:#E8E7E7;padding: 5px;text-align: right;font-weight:bold">
รวมดอกเบี้ย
</td>
<td colspan="1" style="background-color:black;padding: 5px;text-align: center;color:white;">
<?php echo number_format($sumInt,2) ?>
</td>
<td colspan="1" style="background-color:#E8E7E7;padding: 5px;text-align: right;font-weight:bold">
รวมชำระ
</td>
<td colspan="1" style="background-color:black;padding: 5px;text-align: center;color:white;">
<?php echo number_format($sumPayment,2) ?>
</td>
</tr>
</tfoot>
</table>
<div style="margin-bottom: 10px;" >
</div>
</body>

</html>