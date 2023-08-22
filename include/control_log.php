<?php

namespace ControlLog;

use Connection\connection;

class insertLog {
	private $con;
		
		function __construct() {
			$connection = new connection();
			$this->con = $connection->connecttomysql();
		}
		
		public function writeLog($type_log,$log_struc,$is_catch=false) {
			if($type_log == 'use_application'){
				$this->logUseApplication($log_struc);
			}else if($type_log == 'bindaccount'){
				$this->logBindAccount($log_struc,$is_catch);
			}else if($type_log == 'unbindaccount'){
				$this->logUnBindAccount($log_struc,$is_catch);
			}else if($type_log == 'deposittrans'){
				$this->logDepositTransfer($log_struc);
			}else if($type_log == 'withdrawtrans'){
				$this->logWithdrawTransfer($log_struc);
			}else if($type_log == 'transferinside'){
				$this->logTransferInsideCoop($log_struc);
			}else if($type_log == 'manageuser'){
				$this->logManageUserAccount($log_struc);
			}else if($type_log == 'editadmincontrol'){
				$this->logEditAdminControl($log_struc);
			}else if($type_log == 'lockaccount'){
				$this->logLockAccount($log_struc);
			}else if($type_log == 'errorusage'){
				$this->logErrorUsage($log_struc);
			}else if($type_log == 'editsms'){
				$this->logEditSMS($log_struc);
			}else if($type_log == 'editinfo'){
				$this->logEditInfo($log_struc);
			}
		}
		
		private function logUseApplication($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO loguseapplication(member_no,id_userlogin,access_date,ip_address) 
												VALUES(:member_no,:id_userlogin,NOW(),:ip_address)");
			$insertLog->execute($log_struc);
		}
		
		private function logBindAccount($log_struc,$is_catch){
			if($log_struc[":bind_status"] == '-9'){
				if($log_struc[":query_flag"] == '-9'){
					$insertLog = $this->con->prepare("INSERT INTO logbindaccount(member_no,id_userlogin,bind_status,mobile_no,
														response_code,response_message,coop_account_no,data_bind_error,query_error,query_flag) 
														VALUES(:member_no,:id_userlogin,:bind_status,:mobile_no,:response_code,:response_message,:coop_account_no
														,:data_bind_error,:query_error,:query_flag)");
				}else{
					if($is_catch){
						$insertLog = $this->con->prepare("INSERT INTO logbindaccount(member_no,id_userlogin,bind_status,mobile_no
															,response_code,response_message,query_flag) 
															VALUES(:member_no,:id_userlogin,:bind_status,:mobile_no,:response_code,:response_message,:query_flag)");
					}else{
						$insertLog = $this->con->prepare("INSERT INTO logbindaccount(member_no,id_userlogin,bind_status,mobile_no
															,response_code,response_message,coop_account_no,query_flag) 
															VALUES(:member_no,:id_userlogin,:bind_status,:mobile_no,:response_code,:response_message,:coop_account_no,:query_flag)");
					}
				}
			}else{
				$insertLog = $this->con->prepare("INSERT INTO logbindaccount(member_no,id_userlogin,bind_status,mobile_no,coop_account_no) 
													VALUES(:member_no,:id_userlogin,:bind_status,:mobile_no,:coop_account_no)");
			}
			$insertLog->execute($log_struc);
		}
		
		private function logUnBindAccount($log_struc,$is_catch){
			if($log_struc[":unbind_status"] == '-9'){
				if($log_struc[":query_flag"] == '-9'){
					$insertLog = $this->con->prepare("INSERT INTO logunbindaccount(member_no,id_userlogin,unbind_status,
														response_code,response_message,id_bindaccount,data_unbind_error,query_error,query_flag) 
														VALUES(:member_no,:id_userlogin,:unbind_status,:response_code,:response_message,:id_bindaccount
														,:data_bind_error,:query_error,'-9')");
				}else{
					if($is_catch){
						$insertLog = $this->con->prepare("INSERT INTO logunbindaccount(member_no,id_userlogin,unbind_status
															,response_code,response_message,query_flag) 
															VALUES(:member_no,:id_userlogin,:unbind_status,:response_code,:response_message,:query_flag)");
					}else{
						$insertLog = $this->con->prepare("INSERT INTO logunbindaccount(member_no,id_userlogin,unbind_status
															,response_code,response_message,id_bindaccount,query_flag) 
															VALUES(:member_no,:id_userlogin,:unbind_status,:response_code,:response_message,:id_bindaccount,:query_flag)");
					}
				}
			}else{
				$insertLog = $this->con->prepare("INSERT INTO logunbindaccount(member_no,id_userlogin,unbind_status,id_bindaccount) 
													VALUES(:member_no,:id_userlogin,:unbind_status,:id_bindaccount)");
			}
			$insertLog->execute($log_struc);
		}
		private function logDepositTransfer($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO logdepttransbankerror(member_no,id_userlogin,transaction_date,sigma_key,amt_transfer
												,response_code,response_message) 
												VALUES(:member_no,:id_userlogin,:operate_date,:sigma_key,:amt_transfer,:response_code,:response_message)");
			$insertLog->execute($log_struc);
		}
		private function logWithdrawTransfer($log_struc){
			if(isset($log_struc[":fee_amt"])){
				$insertLog = $this->con->prepare("INSERT INTO logwithdrawtransbankerror(member_no,id_userlogin,transaction_date,amt_transfer,penalty_amt,fee_amt,deptaccount_no
												,response_code,response_message) 
												VALUES(:member_no,:id_userlogin,:operate_date,:amt_transfer,:penalty_amt,:fee_amt,:deptaccount_no,:response_code,:response_message)");
			}else{
				$insertLog = $this->con->prepare("INSERT INTO logwithdrawtransbankerror(member_no,id_userlogin,transaction_date,amt_transfer,deptaccount_no
												,response_code,response_message) 
												VALUES(:member_no,:id_userlogin,:operate_date,:amt_transfer,:deptaccount_no,:response_code,:response_message)");
			}
			$insertLog->execute($log_struc);
		}
		private function logTransferInsideCoop($log_struc){
			if(isset($log_struc[":penalty_amt"])){
				$insertLog = $this->con->prepare("INSERT INTO logtransferinsidecoop(member_no,id_userlogin,transaction_date,deptaccount_no,amt_transfer,penalty_amt,type_request,transfer_flag
													,destination,response_code,response_message) 
													VALUES(:member_no,:id_userlogin,:operate_date,:deptaccount_no,:amt_transfer,:penalty_amt,:type_request,:transfer_flag,
													:destination,:response_code,:response_message)");
			}else{
				$insertLog = $this->con->prepare("INSERT INTO logtransferinsidecoop(member_no,id_userlogin,transaction_date,deptaccount_no,amt_transfer,type_request,transfer_flag
													,destination,response_code,response_message) 
													VALUES(:member_no,:id_userlogin,:operate_date,:deptaccount_no,:amt_transfer,:type_request,:transfer_flag,
													:destination,:response_code,:response_message)");

			}
			$insertLog->execute($log_struc);
		}
		private function logManageUserAccount($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO logeditmobileadmin(menu_name,username,use_list,details) 
												VALUES(:menu_name,:username,:use_list,:details)");
			$insertLog->execute($log_struc);
		}
		private function logEditAdminControl($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO logeditadmincontrol(menu_name,username,use_list,details) 
												VALUES(:menu_name,:username,:use_list,:details)");
			$insertLog->execute($log_struc);
		}
		private function logLockAccount($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO loglockaccount(member_no,device_name,unique_id) 
												VALUES(:member_no,:device_name,:unique_id)");
			$insertLog->execute($log_struc);
		}
		private function logErrorUsage($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO logerrorusageapplication(error_menu,error_code,error_desc,error_device) 
												VALUES(:error_menu,:error_code,:error_desc,:error_device)");
			$insertLog->execute($log_struc);
		}
		private function logEditSMS($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO logeditsms(menu_name,username,use_list,details) 
												VALUES(:menu_name,:username,:use_list,:details)");
			$insertLog->execute($log_struc);
		}
		private function logEditInfo($log_struc){
			$insertLog = $this->con->prepare("INSERT INTO logchangeinfo(member_no,old_data,new_data,data_type,id_userlogin) 
												VALUES(:member_no,:old_data,:new_data,:data_type,:id_userlogin)");
			$insertLog->execute($log_struc);
		}
}
?>