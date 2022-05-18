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
class cs_info {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_info";
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
			'cs_info', /* Module name */
			'ChanServ', /* Client name */
			'INFO', /* Command */
			'cs_info::cmd_info', /* Command function */
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
		$cs = $u['target'];
		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);
		$chan = new Channel($parv[1]);
	
		if (!$chan->IsReg)
		{
			$cs->notice($nick->uid,"$chan->chan is not registered.");
			return;
		}
		$cs->notice($nick->uid,"$chan->chan is registered to $chan->owner");
		$cs->notice($nick->uid,"$chan->chan was registered on: ".gmdate("Y-m-d\TH:i:s\Z", $chan->RegDate));
		$cs->notice($nick->uid,"Channel email: $chan->email");
		$cs->notice($nick->uid,"Channel URL: $chan->url");
	}
}
