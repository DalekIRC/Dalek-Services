<?php

/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: RegainOnSasl
//	
\\	Desc: If set to on, will recover your nick for you if it's online when you sasl with the account
//	
\\	
//	
\\	
//	
\\	Version: 1
//				
\\	Author:	Valware
//				
*/

nickserv::func("setcmd", function($u){
	
	global $ns,$cf;
	
	$parv = explode(" ",$u['cmd']);
	if ($parv[0] !== "set"){ return; }

	if (!($account = IsLoggedIn($u['nick']))){ $ns->notice($u['nick'],"You must be logged in to use this command."); return; }
	if ($parv[1] !== "regainonsasl"){ return; }
	if ($cf['login_method'] !== "default"){ return; }
	if (!isset($parv[2])){ return; }
	if ($parv[2] !== "on" && $parv[2] !== "off"){ return; }
	
	if ($parv[2] == "on"){ 
		if (!RegainOnSasl($account,"on")){
			$ns->notice($account,"REGAINONSASL is already set to 'on'");
			return;
		}
		else {
			$ns->notice($account,"REGAINONSASL is now set to 'on'");
			return;
		}	
	}
	if ($parv[2] == "off"){ 
		if (!RegainOnSasl($account,"off")){
			$ns->notice($account,"REGAINONSASL is already set to 'off'");
			return;
		}
		else {
			$ns->notice($account,"REGAINONSASL is now set to 'off'");
			return;
		}	
	}
});


function RegainOnSasl($account,$option){
	
	global $sql;
	
	$query = "SELECT setting_value FROM dalek_account_settings WHERE account='$account' AND setting_key = 'regainonsasl'";
	$result = $sql::query($query);
	
	if (mysqli_num_rows($result) == 0){
		
		$query = "INSERT INTO dalek_account_settings (account, setting_key, setting_value) VALUES ('$account', 'regainonsasl', '$option')";
		$sql::query($query);
		$return = true;
	}
	
	else {
		
		while($row = mysqli_fetch_assoc($result)){
			$switch = $row['setting_value'];
			
			if (($switch == "on" && $option == "on") || ($switch == "off" && $option == "off")){ $return = false; }
			else {
				$query = "UPDATE dalek_account_settings SET setting_value='$option' WHERE account = '$account' AND setting_key = 'regainonsasl'";
				$sql::query($query);
				$return = true;
			}
		}
	}
	mysqli_free_result($result);
	return $return;
}
function IsRegainOnSasl($account){
	
	global $sql;
	
	$query = "SELECT setting_value FROM dalek_account_settings WHERE account='$account' AND setting_key = 'regainonsasl'";
	$result = $sql::query($query);
	
	if (mysqli_num_rows($result) == 0){ return false; }
	else {
		
		$row = mysqli_fetch_assoc($result);
		if ($row['setting_value'] == "on"){ $return = true; }
		if ($row['setting_value'] == "off"){ $return = false; }
	}
	mysqli_free_result($result);
	return $return;
}
		
		
	
nickserv::func("saslconf", function($u){
	
	global $ns,$recovery,$serv,$cf,$servertime;
	if (!IsRegainOnSasl($u['account'])){ return; }
		
	else {
		if ($person = find_person($u['account'])){ $ns->sendraw(":$ns->nick KILL ".$person['nick']." :Automatic recovery in progress"); }
		$recovery[$u['uid']] = $u['account'];
		if ($recov = find_person($u['uid'])){
			$ns->sendraw(":".$cf['sid']." SVSNICK ".$recov['nick']." ".$recovery[$u['uid']]." $servertime"); $recovery[$u['uid']] = NULL;
		}
	}
});

hook::func("UID", function($u){
	
	global $ns,$recovery,$cf,$servertime;
	if (isset($recovery[$u['uid']])){ $ns->sendraw(":".$cf['sid']." SVSNICK ".$u['nick']." ".$recovery[$u['uid']]." $servertime"); $recovery[$u['uid']] = NULL; }
	return;
});

nickserv::func("setlist", function($u){
	
	global $ns;
	
	if (isset($u['key'])){ return; }
	if (isset($parv[0])){ return; }
	$ns->notice($u['nick'],"REGAINONSASL        Automatically regain your nick when you identify through SASL");
});