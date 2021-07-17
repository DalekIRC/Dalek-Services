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
	
	/* TO DO: Make better response for incorrect parameters */
	if (!$account || !$password){ $ns->notice($nick['UID'],"Incorrect parameters."); return; }
	
	if (!($nickToRegain = find_person($account))){ $ns->notice($nick['UID'],IRC("ERR_NICKNOTONLINE")); return; }
	
	if (!df_verify_userpass($account,$password)){ $ns->notice($nick['UID'],IRC("MSG_IDENTFAIL")); return; }
	
	$ns->log($nickToRegain['nick']." (".$nickToRegain['UID'].") ".IRC("LOG_REGAIN")." ".$nick['nick']." (".$nick['uid'].")");
	
	$ns->sendraw(":$ns->uid KILL ".$nickToRegain['nick']." :".IRC("REGAIN_QUITMSG"));
	$ns->sendraw(":".$cf['sid']." SVSNICK ".$nick['UID']." ".$nickToRegain['nick']." $servertime");
	

	df_login($nickToRegain['nick'],$account);
	
	$ns->svslogin($nick['UID'],$account);
	$ns->svs2mode($nick['UID'],"+r");
	$ns->notice($nick['UID'],"$account ".IRC("MSG_REGAIN"));
});
nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"REGAIN              ".IRC("HELPCMD_REGAIN"));
	
});
