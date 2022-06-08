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
\\	Title: Shutdown
//	
\\	Desc: Shuts down services properly
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class os_shutdown {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_shutdown";
	public $description = "Shuts down Dalek Services";
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
		$cmd = "SHUTDOWN";
		$help_string = "Shuts down Dalek Services";
		$syntax = "$cmd";
		$extended_help = 	"Shuts down Dalek Services.\nMust have Services Admin privileges or above.";

		if (!AddServCmd(
			'os_shutdown', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_shutdown::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		return true;
	}
	
	function cmd($u)
	{
		$parv = explode(" ",$u['msg']);

		$nick = $u['nick'];

		if (!ValidatePermissionsForPath("can_shutdown", $nick))
		{
			Client::find("OperServ")->notice($nick->uid,"Permission denied!");
			return;
		}

		foreach(Client::$list as $client)
			$client->quit("Shutting down (Issued by $nick->nick)");
		
		return shell_exec("cd ~/Dalek && ./dalek stop");
	}
}