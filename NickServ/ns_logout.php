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
\\	Title: Logout
//	
\\	Desc: Log yourself out of NickServ
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

nickserv::func("privmsg", function($u){
	
	global $ns,$sql;
	
	$parv = explode(" ",$u['msg']);
	
	$cmd = $parv[0];

	if ($cmd !== "logout"){ return; }
	
	$nick = find_person($u['nick']);
	
	if (!IsLoggedIn($nick['UID'])){ $ns->notice($nick['UID'],IRC("ERR_NOTLOGGEDIN")); return; }
	
	$account = $nick['account'];
	
	$query = "UPDATE dalek_user SET account=NULL WHERE UID='".$nick['UID']."'";
	$sql::query($query);
	$ns->svslogin($nick['UID'],"0");
	$ns->svs2mode($nick['UID'],"-r");
	$ns->log($nick['nick']." (".$nick['UID'].") ".IRC("LOG_LOGGEDOUT")." $account"); 
	$ns->notice($nick['UID'],IRC("MSG_LOGGEDOUT"));
});


nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"LOGOUT              ".IRC("HELPCMD_LOGOUT"));
	
});