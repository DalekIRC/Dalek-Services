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
\\	Title: OperServ RAW
//	
\\	Desc: Sends RAW commands over IRC
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class os_raw {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_raw";
	public $description = "Sends RAW commands over IRC";
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
		$cmd = "RAW";
		$help_string = "Sends RAW commands over IRC. ".bold("WARNING: This can and will cause desyncs");
		$syntax = "$cmd";
		$extended_help = 	"$help_string\nMust have oper or above.";

		if (!AddServCmd(
			'os_raw', /* Module name */
			'OperServ', /* Client name */
			$cmd, /* Command */
			'os_raw::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		return true;
	}
	
	function cmd($u)
	{
		/* we just assume that they are allowed to do this based on if they can message OperServ */
		/* want it better? make it better */
		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);
		$parv[0] = NULL;
		$u['msg'] = implode(" ",$parv);
		S2S($msg = trim($u['msg']));
		SVSLog(bold("RAW:")." $nick->nick ($nick->ident@$nick->realhost) sent RAW: $msg");
	}
}