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
\\	Title: Regain
//	
\\	Desc: Implements two commands which do the same thing,
//	"REGAIN" and "RECOVER"
\\	Syntax: REGAIN|RECOVER nick password
//	
\\	
//	
\\	Version: 1
//				
\\	Author:	Valware
//				
*/


nickserv::func("privmsg", function($u){
	
	global $ns,$servertime,$cf;
	
	$parv = explode(" ",$u['msg']);
	
	if (!($nick = find_person($u['nick']))){ return; }
	if ($parv[0] !== "recover" && $parv[0] !== "regain") { return; }
	
	$account = (isset($parv[1])) ? $parv[1] : NULL;
	$password = (isset($parv[2])) ? $parv[2] : NULL;
	
	if (!$account || !$password){ $ns->notice($nick['UID'],"Incorrect parameters."); return; }
	
	if (!($nickToRegain = find_person($account))){ $ns->notice($nick['UID'],"Nick is not online."); return; }
	
	if (!df_verify_userpass($account,$password)){ $ns->notice($nick['UID'],"Incorrect credentials."); return; }
	
	$ns->sendraw(":$ns->nick KILL ".$nickToRegain['nick']." :Recovery in progress");
	$ns->sendraw(":".$cf['sid']." SVSNICK ".$nick['UID']." ".$nickToRegain['nick']." $servertime");
	
	df_login($nickToRegain['nick'],$account);
	
	$ns->svslogin($nick['UID'],$account);
	$ns->svs2mode($nick['UID'],"+r");
	$ns->notice($nick['UID'],"$account has been regained. You are now logged in.");
});
nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"REGAIN              Also RECOVER. Recovers, and identifies you to your account.");
	
});
