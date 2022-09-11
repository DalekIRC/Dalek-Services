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
\\	Title: LoadMod
//	
\\	Desc: Provides OperServ commands for loading and unloading Dalek IRC modules.
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class os_loadmod {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_loadmod";
	public $description = "Provides loading and unloading of modules";
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
		$cmd = "LOADMOD";
		$help_string = "Loads a particular module";
		$syntax = "$cmd <module>";
		$extended_help = 	"$help_string
							$syntax
							Example: \"$cmd Dictionary/dictionary\"";

		if (!AddServCmd(
			'os_loadmod', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_loadmod::cmd_loadmod', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		$cmd = "UNLOADMOD";
		$help_string = "Unloads a particular module";
		$syntax = "$cmd <module>";
		$extended_help = 	"$help_string
							$syntax
							Example: \"$cmd Dictionary/dictionary\"";

		if (!AddServCmd(
			'os_loadmod', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_loadmod::cmd_unloadmod', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		return true;
	}
	
	public static function cmd_loadmod($u)
	{
		$parv = explode(" ",$u['msg']);

		$nick = $u['nick'];

		if (!ValidatePermissionsForPath("can_load_module", $nick))
		{
			$u['target']->notice($nick->uid,"Permission denied!");
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) attempted to use a restricted command LOADMOD: \"".$u['msg']."\"");
		}
		else
		{
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) used LOADMOD: \"".$u['msg']."\"");
			loadmodule($parv[1]);
		}
	}
	public static function cmd_unloadmod($u)
	{
		$parv = explode(" ",$u['msg']);

		$nick = $u['nick'];

		if (!ValidatePermissionsForPath("can_unload_module", $nick))
		{
			$u['target']->notice($nick->uid,"Permission denied!");
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) attempted to use a restricted command UNLOADMOD: \"".$u['msg']."\"");
		}
		else {
			SVSLog("$nick->nick ($nick->ident@$nick->realhost) used UNLOADMOD: \"".$u['msg']."\"");
			$parv[1] = (strpos($parv[1],"/")) ? explode("/",$parv[1]) : explode("/","nothinglmao/".$parv[1]);
			unloadmodule($parv[1]);
		}
	}
}