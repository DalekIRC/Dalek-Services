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
\\	Title: OperServ Rehash
//	
\\	Desc: Provides OperServ commands for rehashing the configuration
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/

requires_module("rehash");
class os_rehash {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_rehash";
	public $description = "Provides rehashing of the services configuration file";
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
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}


	function __init()
	{
		$cmd = "REHASH";
		$help_string = "Rehash services configuration file";
		$syntax = "$cmd";
		$extended_help = 	"$help_string
							$syntax
							Example: /msg OperServ REHASH";

		if (!AddServCmd(
			'os_rehash', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_rehash::cmd_rehash', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

	
		return true;
	}
	
	function cmd_rehash($u)
	{
		$parv = explode(" ",$u['msg']);

		$nick = $u['nick'];
		if (!ValidatePermissionsForPath("can_rehash", $nick))
		{
			sendnotice($nick->uid, $u['target'], NULL, "Permission denied!");
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) attempted to use a restricted command REHASH");
			return;
		}
		rehash::cmd_rehash($u);
	}
}