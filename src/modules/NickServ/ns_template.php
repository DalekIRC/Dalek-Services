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
\\	Title: Templ8 4 Services command!
//	
\\	Desc:	This template is designed to show you how to add a command to
//			a services bot (NickServ, ChanServ, whatever). The bot must
\\			actually be loaded if you want to load a command for it.
//
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/


/* Our class! This is the module itself. It needs to be named the same as the file, without ".php" */
class ns_template {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_template";
	public $description = "A template for adding a command to NickServ";
	public $author = "Valware";
	public $version = "1.0";

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Lets define our command =] */
		define("CMD_NS_EXAMPLE","EXAMPLE");

	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}

	/* This part is the _inititalisation! This is ran when the module has been successfully loaded */
	function __init()
	{
		/* Lets add our command to NickServ =]
		 * This is where we put our help string, syntax, and extended help for the 'HELP' command output.
		 * Just for a kind of 'all-in-one' thing
		 */
		$help_string = "This is just an example command.";
		$syntax = "EXAMPLE";
		$extended_help = "This is just an example command, but with extended help!";

		if (!AddServCmd(
			'ns_template', /* Module name */
			'NickServ', /* Client name */
			CMD_NS_EXAMPLE, /* Command */
			'ns_template::function', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false; /* If something went wrong, we gotta back out and unload the module */

		return true; /* weeee are good */
	}
	
	function function($u)
	{
		/* Grab our target Client object (NickServ) */
		$ns = $u['target'];

		/* Grab our requester User object */
		$nick = $u['nick'];

		/* Tell them it's just an example! */
		$ns->notice($nick->uid,"Hey!","Thanks for the message!","But this is just an example template!","Someone is learning things, I guess!");
	}
}