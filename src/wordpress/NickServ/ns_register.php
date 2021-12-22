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
	global $ns,$nickserv,$wpconfig;
	
	if (!($nick = new User($u['nick']))->IsUser){ return; } // find 'em
	
	$parv = explode(" ",$u['msg']); // splittem
	if ($parv[0] !== "register")
		return;
	if ($nickserv['login_method'] !== "wordpress"){ return; }	// default config option
	
	$ns->notice($nick->nick,"You must be invited to register.");
	return;
});


function wp_IsRegUser($user){
	
	global $wpconfig;
	$conn = sqlnew();
	if (!$conn) { return "ERROR"; }
	else {
		$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."users WHERE user_nicename = lower(?)");
		$prep->bind_param("s",$user);
		$prep->execute();
		
		$prep->store_result();
		
		if ($prep->num_rows == 0){ $prep->close(); return false; }
		$prep->close();
		return true;
	}
}