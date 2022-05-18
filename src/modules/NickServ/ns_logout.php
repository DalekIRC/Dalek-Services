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
<<<<<<< HEAD
class ns_logout {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_logout";
	public $description = "NickServ LOGOUT Command";
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
		$cmd = "LOGOUT";
		$help_string = "Logs you out of your account.";
		$syntax = "$cmd";
		$extended_help = 	"$help_string
								If you have permission to do so, this command can log
								somebody else out with an extra parameter.
								 
								$syntax";

		if (!AddServCmd(
			'ns_logout', /* Module name */
			'NickServ', /* Client name */
			$cmd, /* Command */
			'ns_logout::cmd', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;
		
		return true;
	}

	public static function UserLogout(User $nick)
	{
		$ns = Client::find("NickServ");
		$conn = sqlnew();
		$account = $nick->account;
		
		$conn->query = "UPDATE dalek_user SET account=NULL WHERE UID='".$nick->uid."'";
		$ns->svslogin($nick->uid,"0");
		$ns->svs2mode($nick->uid,"-r");
		$ns->log($nick->nick." (".$nick->uid.") ".IRC("LOG_LOGGEDOUT")." $account"); 
		$ns->notice($nick->uid,IRC("MSG_LOGGEDOUT"));
	}
	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd($u) : void
	{
		$ns = $u['target'];

		$nick = $u['nick'];
		$parv = explode(" ",$u['msg']);
			
		if (!IsLoggedIn($nick))
		{
			$ns->notice($nick->uid,"You must be logged in to perform that command.");
			return;
		}

		if (isset($parv[1]))
		{
			$target = new User($parv[1]);
			if (!ValidatePermissionsForPath("can_logout", $nick, $target, NULL, NULL));
			{
				$ns->notice($nick->nick,"Permission denied.");
				return;
			}
			SVSLog("$nick->nick used LOGOUT to force logout $target->nick");
			ns_logout::UserLogout($target);
			$target->account = NULL;
		}

		else {
			SVSLog(("$nick->nick used LOGOUT."));
			ns_logout::UserLogout($nick);
			$nick->account = NULL;
		}
	}
}
=======

nickserv::func("privmsg", function($u){
	
	global $ns,$sql;
	
	$parv = explode(" ",$u['msg']);
	
	$cmd = $parv[0];

	if ($cmd !== "logout"){ return; }
	
	$nick = new User($u['nick']);
	
	if (!IsLoggedIn($nick->uid)){ $ns->notice($nick->uid,IRC("ERR_NOTLOGGEDIN")); return; }
	
	UserLogout($nick);
});

function UserLogout(User $nick)
{
	global $ns;
	$conn = sqlnew();
	$account = $nick->account;
	
	$conn->query = "UPDATE dalek_user SET account=NULL WHERE UID='".$nick->uid."'";
	$ns->svslogin($nick->uid,"0");
	$ns->svs2mode($nick->uid,"-r");
	$ns->log($nick->nick." (".$nick->uid.") ".IRC("LOG_LOGGEDOUT")." $account"); 
	$ns->notice($nick->uid,IRC("MSG_LOGGEDOUT"));
}
nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"LOGOUT              ".IRC("HELPCMD_LOGOUT"));
	
});
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
