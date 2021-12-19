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
	
	if ($nickserv['login_method'] !== "wordpress") /* auth via wordpress, aye */
		return;
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
	$user = new WPUser($nick->account);
	if (!$user->ConfirmPassword($pass)){ $ns->notice($nick->uid,IRC("MSG_IDENTIFAIL")); return; } 
	
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

