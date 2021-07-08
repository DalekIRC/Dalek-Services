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
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

//nickserv privmsg hook		 declare func with our incoming hook array


nickserv::func("privmsg",	 function($u){
	
	// our global for bot $ns and config $nickserv
	global $ns,$nickserv;
	
	$nick = find_person($u['nick']); // find 'em
	
	$parv = explode(" ",$u['msg']); // splittem
	
	if ($nickserv['login_method'] !== "default"){ return; }	// default config option
	
	if (strtolower($parv[0]) !== "register"){ return; } // our command

	if (df_IsRegUser($nick['nick'])){ $ns->notice($nick['UID'],"You are already registered."); return; }
	
	if (!($password = $parv[1])){ $ns->notice($nick['UID'],"Syntax: /msg $ns->nick register <password> <email>"); return; }
	
	if (!($email = $parv[2])){ $ns->notice($nick['UID'],"Syntax: /msg $ns->nick register <password> <email>"); return; }
	
	if (($createUser = df_create_user($nick['nick'],$password,$email)) !== true){ $ns->notice($nick['UID'],$createUser); return; }
	
	if (!df_login($nick['UID'],$nick['nick'])){
		
		//account writing failed for some reason, return;
		$ns->notice($nick['UID'],"There was an error when logging you in. Please contact staff.");
		return;
	}
	$ns->svslogin($nick['UID'],$nick['nick']);
	$ns->svs2mode($nick['UID']," +r");
	$ns->notice($nick['UID'],"You have now registered under the account ".$nick['nick']);
	
});
	


// default create user function
function df_create_user($user,$password,$email){
	global $sqlip,$sqluser,$sqlpass,$sqldb,$servertime;
	
	if (strlen($password) < 8){ return "That password is too short. Your password must be minimum 8 characters."; }
	
	$tok = explode("@",$email) ?? NULL;
	$tok2 = explode(".",$tok[1]) ?? NULL;
	$error = NULL;
	
	
	
	if (!$tok || !$tok[0] || !$tok[1]) { echo "wan a dem"; $error = 1; }
	elseif (!$tok2[1]){ $error = 1; echo "dis wan"; }
	
	if ($error == 1){ return "That email is not valid. Please enter a valid email."; }
	
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

// check if is registered user using default
function df_IsRegUser($user){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	if (!($nick = find_person($user))){ return false; }
	
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


nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"REGISTER            Register your username.");
	
});



nickserv::func("help", function($u){
	
	global $ns;
	
	if ($u['key'] !== "register"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: REGISTER");
	$ns->notice($nick,"Syntax: /msg $ns->nick register password email");
	$ns->notice($nick,"Example: /msg $ns->nick register Sup3r-S3cur3 yourname@example.com");
});
