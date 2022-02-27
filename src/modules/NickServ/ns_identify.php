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
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/


//nickserv privmsg hook		 declare func with our incoming hook array
nickserv::func("privmsg",	 function($u){
	
	// our global for bot $ns and config $nickserv
	global $ns,$nickserv;
	
	$nick = new User($u['nick']); // find 'em
	
	$parv = explode(" ",$u['msg']); // splittem
	
	if ($nickserv['login_method'] !== "default"){ return; }	// default config option
	
	if (strtolower($parv[0]) !== "identify" && strtolower($parv[0]) !== "login" && strtolower($parv[0]) !== "id"){ return; } // our command
	
	if (!isset($parv[1])){ $ns->notice($nick->uid,IRC("MSG_IDENTIFY_SYNTAX")); return; }
	
	// user is logging into account for their nick or notice
	if (isset($parv[2])){
		$account = $parv[1];
		$pass = $parv[2];
	}
	else {
		$account = $nick->nick;
		$pass = $parv[1];
	}
	
	if (!df_verify_userpass($account,$pass)){ $ns->notice($nick->uid,IRC("MSG_IDENTIFAIL")); return; }
	
	if (!df_login($nick->uid,$account)){
		
		//account writing failed for some reason, return;
		$ns->log(IRC("LOG_IDENTIFAIL"));
		$ns->notice($nick->uid,IRC("ERR_IDENTIFAIL"));
		return;
	}
	$ns->log($nick->nick." (".$nick->uid.") ".IRC("LOG_IDENTIFY")." $account"); 
	$ns->svslogin($nick->uid,$account);
	$ns->svs2mode($nick->uid," +r");
	$ns->notice($nick->uid,IRC("MSG_IDENTIFY")." $account");
	
	nickserv::run("identify", array('nick' => $nick, 'account' => $account));
	
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
		$nick = new User($nick);
		$nick = $nick->uid;
		
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

function IsLoggedIn($nick){
	
	global $sql;
	
	$person = new User($nick);
	if (!$person->IsUser){ return false; }
	
	$uid = $person->uid;
	
	$query = "SELECT account FROM dalek_user WHERE UID = '$uid'";
	$result = $sql::query($query);
	
	if (mysqli_num_rows($result) == 0){ return false; }
	
	$row = mysqli_fetch_assoc($result);
	$account = $row['account'];
	mysqli_free_result($result);
	return $account;
}
	

nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"IDENTIFY            ".IRC("HELPCMD_IDENTIFY"));
	
});



nickserv::func("help", function($u){
	
	global $ns;
	
	if ($u['key'] !== "identify"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: IDENTIFY");
	$ns->notice($nick,"Syntax: /msg $ns->nick identify [account] password");
	$ns->notice($nick,"Example: /msg $ns->nick identify Sup3r-S3cur3");
});



hook::func("UID", function($u)
{
	global $ns;
	if (!isset($u['account']))
		return;
	if ($u['account'] == $u['nick'])
		$ns->sendraw(":$ns->nick SVS2MODE ".$u['nick']." +r");
});
