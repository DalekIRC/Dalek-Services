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
\\	Title: INFO
//	
\\	Desc: Shows information on a user.
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class ns_info {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_info";
	public $description = "NickServ INFO Command";
	public $author = "Valware";
	public $version = "1.0";
    public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		$help_string = "View information on a user or account";
		$syntax = "INFO [<nick>|<account>]";
		$extended_help = 	"$help_string\n$syntax";

		if (!AddServCmd(
			'ns_info', /* Module name */
			'NickServ', /* Client name */
			'INFO', /* Command */
			'ns_info::cmd_info', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_info($u)
	{
		$ns = $u['target'];
		
		$nick = $u['nick'];
		
		$wp_user = ($nick->IsWordPressUser) ? $nick->wp : NULL;

		
		$parv = explode(" ",$u['msg']); // splittem


		if (count($parv) == 1)
			$parv[1] = $u['nick']->nick;

		$target = new User($parv[1]);


		$wp_target = ($target->IsWordPressUser) ? $target->wp : new WPUser($parv[1]);
		
		if (!$target->IsUser && !$target->IsWordPressUser)
		{
			$ns->notice($nick->uid,"That nick is not online and is not registered.");
			return;
		}
		$extended = (ValidatePermissionsForPath("extended_info", $nick) || (isset($target->wp) && isset($nick->wp) && $target->wp == $nick->wp)) ? 1 : 0;
		if ($target->IsUser)
		{
			$ns->notice($nick->uid,"IRC information about $target->nick");
			$ns->notice($nick->uid," ");
			$loggedin = (IsLoggedIn($target)) ? "logged in as $target->account" : "not logged in.";
			$ns->notice($nick->uid,"$target->nick is $loggedin");
			$ns->notice($nick->uid,"$target->nick is $target->gecos");
			$ns->notice($nick->uid,"$target->nick is currently online.");

			if ($wp_target)
				if ($extended)
					$ns->notice($nick->uid,"Online from: $target->ident@$target->realhost");
				else
					$ns->notice($nick->uid,"Online from: $target->ident@$target->cloak");

			
			$ns->notice($nick->uid," ");
		}
		if (!isset($wp_target->user_login))
			return;
		
		$ns->notice($nick->uid,"Account information about $wp_target->user_login");
		$ns->notice($nick->uid," ");
		if ($wp_user->IsUser)
			if ($extended)
				$ns->notice($nick->uid,"Email addr: $wp_target->user_email");
			
		$ns->notice($nick->uid,"Registered: $wp_target->user_registered");
		$roles = "";
		foreach($wp_target->role_array as $role)
		{
			if (strlen($roles))
				$roles .= ", ";
			$roles .= $role;
			$roles = rtrim($roles,", ");
		}
		$ns->notice($nick->uid,"Permissions: $roles");
		
		$ns->notice($nick->uid,"Number of website posts: ".$wp_target->user_meta->num_posts);
		$ns->notice($nick->uid," ");

		if (function_exists("_is_disabled") && $extended)
				if (_is_disabled($wp_target))
					$ns->notice($nick->uid,"This account has been disabled by an administrator.");

		return;
	}
}
