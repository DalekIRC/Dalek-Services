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
\\	Title:  Cheeky NickServ
//	
\\	Desc:	This module turns NickServ into a very cheeky bot indeed lol
//
\\	Version: 1.0
//				
\\	Author:	Checks Out
//				
*/
 

class ns_cheek {
 
	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_cheek";
	public $description = "Makes NickServ very nice indeed";
	public $author = "Checks Out ✔";
	public $version = "1.0";

	function __init()
	{
		/* Lets add our command to NickServ =]
		 * This is where we add our cheek lol
		 * Just for a kind of 'all-in-one' thing
		 */
		$help_string = "Greet good ol' NickServ.";
		$syntax = "HELLO";
		$extended_help = "Say hello to NickServ!";
 
		return (boolean) AddServCmd(
			'ns_cheek', /* Module name */
			'NickServ', /* Client name */
			'HELLO', /* Command */
			'ns_cheek::be_cheeky', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		);
	}
	
	function be_cheeky($u)
	{
		/* Grab our target Client object (NickServ) */
		$ns = $u['target'];
 
		/* Grab our requester User object */
		$nick = $u['nick'];
 
		/* Tell them it's just an example! */
		$ns->notice($nick->uid,
            "Och aye! Think ya can just say hello to mee, do ya hen!",
            "Well why doncha doo a big FUCK affff!! Ehh!!",
            "Greetin me like tha ya cunt",
            "Away afff wi ye yi fuckin shiter!!!");
	}
}
