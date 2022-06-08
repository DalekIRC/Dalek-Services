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
\\	Title: Suspend
//	
\\	Desc: Allows staff to suspend an account
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


class ns_suspend {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_suspend";
	public $description = "NickServ SUSPEND - Suspends an account";
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
		$cmd = "SUSPEND";
		$help_string = "Suspends an account";
		$syntax = "$cmd <account>";
		$extended_help = 	"$help_string\n".
							"Limited to Services Admins\n".
							"$syntax";

		if (!AddServCmd(
			'ns_suspend', /* Module name */
			'NickServ', /* Client name */
			$cmd, /* Command */
			'ns_suspend::cmd_suspend', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;
		
		$cmd = "UNSUSPEND";
		$help_string = "Unsuspends an account";
		$syntax = "$cmd <account>";
		$extended_help = 	"$help_string\n".
							"Limited to Services Admins\n".
							"$syntax";

		if (!AddServCmd(
			'ns_suspend', /* Module name */
			'NickServ', /* Client name */
			$cmd, /* Command */
			'ns_suspend::cmd_unsuspend', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		return true;
	}

	
	public static function cmd_suspend($u) : void
	{
		$ns = $u['target'];
		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);

		if (!ValidatePermissionsForPath("can_suspend_account", $nick))
		{
			$ns->notice($nick->uid,"Permission denied!");
			return;
		}
		$target = new WPUser($parv[1]);
		if (!$target->IsUser)
		{
			$ns->notice($nick->uid,"Account \"".$parv[1]."\" is not registered.");
			return;
		}
		if (suspend_account($target))
		{
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) used SUSPEND to suspend account $target->user_login");
			$ns->notice($nick->uid,"Account \"".$parv[1]."\" has been suspended.");
		}
		else
			$ns->notice($nick->uid,"Could not suspend account \"".$parv[1]."\"");
	}
	public static function cmd_unsuspend($u) : void
	{
		$ns = $u['target'];
		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);

		if (!ValidatePermissionsForPath("can_unsuspend_account", $nick))
		{
			$ns->notice($nick->uid,"Permission denied!");
			return;
		}
		$target = new WPUser($parv[1]);
		if (!$target->IsUser)
		{
			$ns->notice($nick->uid,"Account \"".$parv[1]."\" is not registered.");
			return;
		}
		if (unsuspend_account($target))
		{
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) used UNSUSPEND to unsuspend account $target->user_login");
			$ns->notice($nick->uid,"Account \"".$parv[1]."\" has been unsuspended.");
		}
		else
			$ns->notice($nick->uid,"Could not unsuspend account \"".$parv[1]."\"");
	}
}