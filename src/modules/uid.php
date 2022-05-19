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
\\	Title:		UID
//				
\\	Desc:		UID command
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
class uid {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "uid";
	public $description = "Provides UID compatibility";
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
		 * the function is a string reference to this class, the cuid_elmer method (function)
		 * The last param is expected parameter count for the command
		 * (both point to the same function which determines)
        */

		if (!CommandAdd($this->name, 'UID', 'uid::cmd_uid', 0))
			return false;
		
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_uid($u)
    {
			
		$parv = explode(" ",$u['params']);

		$sid = $u['nick']->uid;
		$nick = $parv[0];
		$ts = $parv[2];
		$ident = $parv[3];
		$realhost = $parv[4];
		$uid = $parv[5];
		$account = ($parv[6] == "0") ? false : $parv[6];
		$usermodes = $parv[7];
		$cloak = $parv[9];
		$ipb64 = ($parv[10] !== "*") ? $parv[10] : NULL;
		$ip = inet_ntop(base64_decode($ipb64)) ?? "*";
		if (!$ip){ $ip = ""; }
		$tok = explode(" :",$u['params']);
		$gecos = str_replace($tok[0]." :","",$u['params']);
		hook::run("UID", array(
			"sid" => $sid,
			"nick" =>$nick,
			"timestamp" => $ts,
			"ident" => $ident,
			"realhost" => $realhost,
			"uid" => $uid,
			"account" => $account,
			"usermodes" => $usermodes,
			"cloak" => $cloak,
			"ip" => $ip ?? $ipb64,
			"gecos" => $gecos)
		);
    }
}
