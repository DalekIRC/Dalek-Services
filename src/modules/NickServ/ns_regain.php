<?php

/*				
//	(C) 2022 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: Regain
//	
\\	Desc: Implements two commands which do the same thing,
//	"REGAIN" and "RECOVER"
\\	Syntax: REGAIN|RECOVER nick password
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/

class ns_regain {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_regain";
	public $description = "NickServ REGAIN and RECOVER Commands";
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
		$help_string_regain = "Regain your account if you cannot use it.";
		$syntax_regain = "REGAIN <nick> [<password>]";
		$extended_help_regain = 	"$help_string_regain\n$syntax_regain";

		$help_string_recover = "Does exactly the same as regain, added for comfort.";
		$syntax_recover = "RECOVER <nick> [<password>]";
		$extended_help_recover = 	"$help_string_recover\n$syntax_recover";

		if (!AddServCmd(
			'ns_regain', /* Module name */
			'NickServ', /* Client name */
			'REGAIN', /* Command */
			'ns_regain::cmd_regain', /* Command function */
			$help_string_regain, /* Help string */
			$syntax_regain, /* Syntax */
			$extended_help_regain /* Extended help */
		)) return false;

		if (!AddServCmd(
			'ns_regain', /* Module name */
			'NickServ', /* Client name */
			'RECOVER', /* Command */
			'ns_regain::cmd_regain', /* Command function */
			$help_string_recover, /* Help string */
			$syntax_recover, /* Syntax */
			$extended_help_recover /* Extended help */
		)) return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_regain($u)
	{
		global $ns,$servertime,$cf;
		$ns = $u['target'];
		
		$nick = $u['nick'];
		$account = $nick->account ?? NULL;
		$parv = explode(" ",$u['msg']);
	
		
		$account = ($account) ? $parv[1] : NULL;
		$password = (isset($parv[2])) ? $parv[2] : NULL;
		
		if (!($nickToRegain = new User($account))->IsUser)
		{
			$ns->notice($nick->uid,IRC("ERR_NICKNOTONLINE"));
			return;
		}
		if ($nickToRegain->uid == $nick->uid)
		{
			$ns->notice($nick->uid,"You are already using that nick.");
			return;
		}
		if (!IsLoggedIn($nick) && !($account = new WPUser($account))->ConfirmPassword($password))
		{
			$ns->notice($nick->uid,IRC("MSG_IDENTFAIL"));
			return;
		}
		
		$ns->log($nickToRegain->nick." (".$nickToRegain->uid.") ".IRC("LOG_REGAIN")." ".$nick->nick." (".$nick->uid.")");
		
		svslogin($nickToRegain->nick, 0, $ns); // log out the target (may kill them in some instances)
		$retain = $nickToRegain->nick;

		/* check if they're online still */
		$nickToRegain = new User($nickToRegain->uid);
		/* if so, change their nick */
		if ($nickToRegain->IsUser)
			$nickToRegain->NewNick("Guest".rand(1111,9999));
		$nick->NewNick($retain);		

		if (!IsLoggedIn($nick))
			svslogin($nick->uid, $account->user_login, $ns);

		$ns->notice($nick->uid,"$account ".IRC("MSG_REGAIN"));
	}
	
}
