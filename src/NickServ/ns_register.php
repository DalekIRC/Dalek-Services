<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\
//
\\	Title:		Register
//				
\\	Desc:		Provides command 'register' to allow a nick
//				to register their username.
\\				
//				
\\				Syntax:		register <password> <email>
//				
\\	Version:	1.1
//				
\\	Author:		Valware
//				
*/

//nickserv privmsg hook		 declare func with our incoming hook array


nickserv::func("privmsg",	 function($u){
	
	// our global for bot $ns and config $nickserv
	global $ns,$nickserv;
	
	if (!($nick = new User($u['nick']))->IsUser){ return; } // find 'em
	
	$parv = explode(" ",$u['msg']); // splittem
	
	if ($nickserv['login_method'] !== "default"){ return; }	// default config option
	
	if (strtolower($parv[0]) !== "register"){ return; } // our command

	if (df_IsRegUser($nick->nick)){ $ns->notice($nick->uid,IRC("ERR_ALREADYREG")); return; }
	
	if (!($password = $parv[1])){ $ns->notice($nick->uid,"Syntax: /msg $ns->nick register <password> <email>"); return; }
	
	if (!($email = $parv[2])){ $ns->notice($nick->uid,"Syntax: /msg $ns->nick register <password> <email>"); return; }
	
	if (($createUser = df_create_user($nick->nick,$password,$email)) !== true){ $ns->notice($nick->uid,$createUser); return; }
	
	if (!df_login($nick->uid,$nick->nick)){
		
		//account writing failed for some reason, return;
		$ns->notice($nick->uid,IRC("ERR_IDENTIFAIL"));
		return;
	}
	$ns->log("REGISTER: ".$nick->nick." (".$nick->uid.") ".IRC("LOG_REGISTER")." ".$nick->nick);
	$ns->svslogin($nick->uid,$nick->nick);
	$ns->svs2mode($nick->uid," +r");
	$ns->notice($nick->uid,IRC("MSG_REGISTER")." ".$nick->nick);
	
});
	


// default create user function
function df_create_user($user,$password,$email){
	global $sqlip,$sqluser,$sqlpass,$sqldb,$servertime;
	
	if (strlen($password) < 8){ return IRC("ERR_PASSTOOSHORT"); }
	
	if (!validate_email($email)){ return IRC("ERR_BADEMAIL"); }
	
	$password = password_hash($password, PASSWORD_DEFAULT);
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return "ERROR"; }
	else {
		$prep = $conn->prepare("INSERT INTO dalek_accounts (
			timestamp,
			display,
			email,
			pass
		) VALUES (
			?,
			?,
			?,
			?
		)");
		$prep->bind_param("ssss",$servertime,$user,$email,$password);
		$prep->execute();
		$prep->close();
	}
	return true;
}

function validate_email($email){
	$tok = explode("@",$email) ?? NULL;
	$tok2 = explode(".",$tok[1]) ?? NULL;
	$error = NULL;
	
	if (!isset($tok) || !isset($tok[0]) || !isset($tok[1])) { $error = 1; }
	elseif (!$tok2[1]){ $error = 1; }
	
	if (!$error){ return true; }
	else { return false; }
}

// check if is registered user using default
function df_IsRegUser($user){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return "ERROR"; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_accounts WHERE display = ?");
		$prep->bind_param("s",$user);
		$prep->execute();
		
		$prep->store_result();
		
		if ($prep->num_rows == 0){ $prep->close(); return false; }
		$prep->close();
		return true;
	}
}

function df_AccountDetails($account){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_accounts WHERE display = ?");
		$prep->bind_param("s",$account);
		$prep->execute();
		$check = $prep->get_result();
		
		if ($check->num_rows == 0){ $prep->close(); return false; }
		$row = $check->fetch_assoc();
		$prep->close();
		return $row;
	}
}

nickserv::func("helplist", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "default")
		return;
	$nick = $u['nick'];
	
	$ns->notice($nick,"REGISTER            ".IRC("HELPCMD_REGISTER"));
	
});



nickserv::func("help", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "default")
		return;
	if ($u['key'] !== "register"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: REGISTER");
	$ns->notice($nick,"Syntax: /msg $ns->nick register password email");
	$ns->notice($nick,"Example: /msg $ns->nick register Sup3r-S3cur3 yourname@example.com");
});
