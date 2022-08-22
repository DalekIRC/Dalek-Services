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
\\	Title:		Info
//				
\\	Desc:		Provides lookups on a username
//
\\				
//				
\\				Syntax:		INFO <username>
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
	$wp_user = new WPUser($nick->account);

	$parv = explode(" ",$u['msg']); // splittem
	if ($parv[0] !== "info")
		return;
	if ($nickserv['login_method'] !== "wordpress"){ return; }	// default config option
	
	if (count($parv) == 1)
		$parv[1] = $u['nick'];

	$target = new User($parv[1]);

	$lookup = (isset($target->account)) ? $target->account : $parv[1];
	$wp_target = new WPUser($lookup);

	if ($target->IsUser)
	{
		$ns->notice($nick->uid,"IRC information about $target->nick");
		$ns->notice($nick->uid," ");
		$ns->notice($nick->uid,"$target->nick is logged in as $target->account");
		$ns->notice($nick->uid,"$target->nick is $target->gecos");
		$ns->notice($nick->uid,"$target->nick is currently online.");

		if ($wp_user->IsUser)
			if ($wp_user->IsAdmin || $wp_target->user_id == $wp_user->user_id)
				$ns->notice($nick->uid,"Online from: $target->ident@$target->realhost");
			else
				$ns->notice($nick->uid,"Online from: $target->ident@$target->cloak");

		if (function_exists("_is_disabled") && $wp_user->IsAdmin)
			if (_is_disabled($wp_target))
			{
				$ns->notice($nick->uid," ");
				$ns->notice($nick->uid,"\x02This account has been disabled by an administrator.");
			}
		$ns->notice($nick->uid," ");
	}
	if (!$wp_target->IsUser)
		return;
	
	$ns->notice($nick->uid,"Website information about $wp_target->user_login");
	$ns->notice($nick->uid," ");
	if ($wp_user->IsUser)
		if ($wp_user->IsAdmin || $wp_target->user_id == $wp_user->user_id)
			$ns->notice($nick->uid,"Email addr: $wp_target->user_email");
		
	$ns->notice($nick->uid,"Registered: $wp_target->user_registered");
	$ns->notice($nick->uid,"Website role: ".$wp_target->role_array[0]);
	
	$ns->notice($nick->uid,"Number of website posts: ".$wp_target->user_meta->num_posts);
	$ns->notice($nick->uid," ");
	return;
});

nickserv::func("helplist", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress")
		return;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"INFO                ".IRC("HELPCMD_INFO"));
	
});



nickserv::func("help", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress")
		return;
	
	if ($u['key'] !== "info"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: INFO");
	$ns->notice($nick,"Automatically turns your private messaging off when you identify");
	$ns->notice($nick,"Syntax: /msg $ns->nick info <account>");
	$ns->notice($nick,"Example: /msg $ns->nick info Lamer23");
});
