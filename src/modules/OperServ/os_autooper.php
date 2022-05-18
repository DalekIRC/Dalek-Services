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
\\	Title: Dictionarahhhh
//	
\\	Desc: Give a dictionary lookup thingamajig
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class os_autooper {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_autooper";
	public $description = "Automatically gives services operators oper";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		hook::del("auth", 'os_autooper::auth');
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
		hook::func("auth", 'os_autooper::auth');

		return true;
	}
	
	function auth($u)
	{
		global $cf;

		if (!isset($cf['auto_oper']) || $cf['auto_oper'] !== "on")
			return;
			
		$nick = new User($u['nick']);
		$wp = $nick->wp;

		if (in_array("administrator",$wp->role_array) || in_array("irc_admin",$wp->role_array))
			S2S("SVSO $nick->uid $nick->nick netadmin-with-override - - +bcdfkoqsBOS valware.uk");

		elseif (in_array("irc_oper",$wp->role_array))
			S2S("SVSO $nick->uid $nick->nick globop - - - -");
		
		elseif (in_array("irc_helper",$wp->role_array))
			S2S("SVSO $nick->uid $nick->nick locop +h - - -");

	}
}