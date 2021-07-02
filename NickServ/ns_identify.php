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
\\	Title: Identify
//	
\\	Desc: Provides commands "identify", "id" and "login"
//	to identify to a services account.
\\	
//	Syntax: <identify|id|login> [account] <password>
\\	
//	
\\	Version: 1
//				
\\	Author:	Valware
//				
*/


//nickserv privmsg hook		 declare func with our incoming hook array
nickserv::func("privmsg",	 function($u){
	
	// our global for bot $ns and config $nickserv
	global $ns,$nickserv;
	
	$nick = find_person($u['nick']); // find 'em
	
	$parv = explode(" ",$u['msg']); // splittem
	
	if ($nickserv['login_method'] !== "default"){ return; }	// default config option
	
	if (strtolower($parv[0]) !== "identify" && strtolower($parv[0]) !== "login" && strtolower($parv[0]) !== "id"){ return; } // our command
	
	if (!isset($parv[1])){ $ns->notice($nick['UID'],"Syntax: /msg $ns->nick identify [account] <password>"); return; }
	
	// user is logging into account for their nick or notice
	$account = (isset($parv[2])) ? $parv[1] : $nick['nick'];
	$pass = (isset($parv[2])) ? $parv[2] : $parv[1];
	
	
	if (!df_verify_userpass($account,$pass)){ $ns->notice($nick['UID'],"Identification failed: incorrect credentials"); return; }
	
	if (!df_login($nick['UID'],$account)){
		
		//account writing failed for some reason, return;
		$ns->notice($nick['UID'],"There was an error when logging you in. Please contact staff.");
		return;
	}
	$ns->svslogin($nick['UID'],$account);
	$ns->svs2mode($nick['UID']," +r");
	$ns->notice($nick['UID'],"You are now logged into account $account");
	
});


function df_verify_userpass($user,$pass){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return "ERROR"; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_accounts WHERE display = ?");
		$prep->bind_param("s",$user);
		$prep->execute();
		
		$sResult = $prep->get_result();

		if ($sResult->num_rows == 0 || !isset($sResult)){ $prep->close(); return false; }
		
	
		$result = false;
		
		while ($row = $sResult->fetch_assoc()){
			
			if (password_verify($pass,$row['pass'])){ $result = true; }
		}
		
		$prep->close();
		return $result;
	}
}


function df_login($nick,$account){
	
	
	global $sqlip,$sqluser,$sqlpass,$sqldb,$ns;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return "ERROR"; }
	else {
		
		// lmao
		$nick = find_person($nick);
		$nick = $nick['UID'];
		
		$prep = $conn->prepare("UPDATE dalek_user SET account = ? WHERE UID = ?");
		$prep->bind_param("ss",$account,$nick);
		$prep->execute();
		$prep->close();
		$prep = $conn->prepare("SELECT account FROM dalek_user WHERE UID = ?");
		$prep->bind_param("s",$nick);
		$prep->execute();
		$sResult = $prep->get_result();
		
		$result = false;
		
		while ($row = $sResult->fetch_assoc()){
			
			if (!$row['account'] || $row['account'] != $account){ $result = false; }
			else { $result = true; }
		}
		$prep->close();
	
		return $result;
	}
}