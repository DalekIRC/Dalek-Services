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
\\	Title: Set Email
//	
\\	Desc: Allows a user to update the email address associated
//	with their account.
\\	
//	Syntax: SET EMAIL <new email>
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/



nickserv::func("setcmd", function($u){
	
	global $ns,$nickserv;
	$nick = new User($u['nick']);
	$parv = explode(" ",$u['cmd']);
	if ($parv[0] !== "set"){ return; }

	
	if ($parv[1] !== "email"){ return; }
	if ($nickserv['login_method'] !== "wordpress"){ return; }
	if (!($account = IsLoggedIn($u['nick']))){ $ns->notice($u['UID'],"You must be logged in to use this command."); return; }
	if (!isset($parv[2])){ return; }
	
	$email = $parv[2];
	$account = new WPUser($account);
	
	if ($account->user_email == $email){ $ns->notice($nick->uid,"That is already your email address."); return; }
	
	if (!validate_email($email)){ $ns->notice($nick->uid,IRC("ERR_BADEMAIL")); return ; }
	
	$account->SetEmail($email);
	
	$ns->log($nick->nick." (account: $account->user_login) has updated their email address to be $email");
	$ns->notice($nick->uid,"Your email has been updated to be $email");
	return;
	
	
});

nickserv::func("setlist", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress")
		return;
	if (isset($u['key'])){ return; }
	if (isset($parv[0])){ return; }
	$ns->notice($u['nick'],"EMAIL			   Set the email address associated with your account.");
});
