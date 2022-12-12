<?php
/*				
//	(C) 2022 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		MOTD
//				
\\	Desc:		MOTD command
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class info2whois {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "info2whois";
	public $description = "Provides MOTD compatibility";
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
		/* Params: CommandAdd( this module name, command keyword, function, parameter count)
		 * the function is a string reference to this class, the cmd_elmer method (function)
		 * The last param is expected parameter count for the command
		 * (both point to the same function which determines)
		*/

		hook::func(HOOKTYPE_WELCOME, 'info2whois::hook');

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function hook($u)
	{
		$uid = $u['uid'];
		if (($nick = new User($uid))->IsUser)
			if (IsServiceBot($nick) || (!IsLoggedIn($nick))) // only for normal users who are logged in
				return;

		elseif (is_null($u['account']))
				return;

		$wpuser = new WPUser($u['account']);
		if (!$wpuser->IsUser)
			return; // shouldn't happen

		specialwhois::send_swhois($uid, "regdate", "registered on $wpuser->user_registered");
		specialwhois::send_swhois($uid, "numwebp", "has made " . $wpuser->user_meta->num_posts . " website posts");
		if ($wpuser->IsAdmin)
			specialwhois::send_swhois($uid, "staff", "is a member of staff on this network.");
		
	}
}
