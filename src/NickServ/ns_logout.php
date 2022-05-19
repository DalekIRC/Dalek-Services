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
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/

nickserv::func("privmsg", function($u){
	
	global $ns,$sql;
	
	$parv = explode(" ",$u['msg']);
	
	$cmd = $parv[0];

	if ($cmd !== "logout"){ return; }
	
	$nick = new User($u['nick']);
	
	if (!IsLoggedIn($nick->uid)){ $ns->notice($nick->uid,IRC("ERR_NOTLOGGEDIN")); return; }
	
	UserLogout($nick);
});

function UserLogout(User $nick)
{
	global $ns;
	$conn = sqlnew();
	$account = $nick->account;
	
	$conn->query = "UPDATE dalek_user SET account=NULL WHERE UID='".$nick->uid."'";
	$ns->svslogin($nick->uid,"0");
	$ns->svs2mode($nick->uid,"-r");
	$ns->log($nick->nick." (".$nick->uid.") ".IRC("LOG_LOGGEDOUT")." $account"); 
	$ns->notice($nick->uid,IRC("MSG_LOGGEDOUT"));
}
nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"LOGOUT              ".IRC("HELPCMD_LOGOUT"));
	
});