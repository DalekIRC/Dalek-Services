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
\\	Title: Logout
//	
\\	Desc: Log yourself out of NickServ
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
require_module("SASL");

class ns_identify {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_identify";
	public $description = "NickServ IDENTIFY command to trigger SASL";
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
		hook::del(HOOKTYPE_WELCOME, "ns_identify::must_identify");
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		$cmd = "IDENTIFY";
		$help_string = "Initiates a request to identify using SASL";
		$syntax = "$cmd [plain|external]";
		$extended_help = 	"$help_string\n".
							"Note: This command does not accept a password.\n".
							"Instead, it asks your client to start performing\n".
							"a SASL. So please make sure you have setup your\n".
							"password in your client.\n".
							"$syntax";

		if (!AddServCmd(
			'ns_identify', /* Module name */
			'NickServ', /* Client name */
			$cmd, /* Command */
			'ns_identify::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;
		
		hook::func(HOOKTYPE_WELCOME, "ns_identify::must_identify");

		return true;
	}

	
	public static function cmd($u) : void
	{
		$ns = $u['target'];
		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);
			
		
		if (isset($parv[1])) // Let 'em know for future reference
		{
			if (strcasecmp($parv[1],"plain") && strcasecmp($parv[1],"external"))
			{
				$ns->notice(
					$nick->uid,
					"It looks like you provided a password. It has been ignored.",
					"For more information, type '/msg $ns->nick HELP IDENTIFY'"
				);
				return;
			}

			$mech = strtoupper($parv[1]);
		}
		else // just assume they're doing external really
			$mech = "EXTERNAL";

		$extra = ($mech == "EXTERNAL") ? $nick->meta->certfp : "";
		$s = ($mech == "EXTERNAL") ? "S" : "C";
		new IRC_SASL($nick->server,$nick->uid,"H",$nick->ip,$nick->ip);
		new IRC_SASL($nick->server,$nick->uid,$s,$mech,$extra);
		
	}

	public static function must_identify($u)
	{
		$nick = new User($u['uid']);
		if ($nick->IsUser && !IsLoggedIn($nick) && IsRegUser($nick->nick))
		{
			$ns = Client::find("NickServ");
			$ns->notice($nick->uid,"That nick is registered. If it's yours, please authenticate for it using SASL.");

			Events::Add(servertime() + 120, 1, NULL, 'identify_timeout', array($nick->nick), $err = NULL, 'ns_identify');
		}
	}
	public static function identify_timeout($nick)
	{
		if (($user = new User($nick))->IsUser)
		{
			if (!IsLoggedIn($user) || $user->account != $user->nick)
				$user->NewNick("Guest".rand(11111,99999));

			Client::find("NickServ")->notice($user->uid,"You may not use that nick.");
		}
	}
}
