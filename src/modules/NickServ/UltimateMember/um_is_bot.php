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
\\	Title: Bots
//	
\\	Desc:  Marks bots as such
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class um_is_bot {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "um_is_bot";
	public $description = "Sets users registered as an IRC bot with +B usermode";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Silence is golden */
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del("auth", 'um_is_bot::botcheck');
		hook::del("UID", 'um_is_bot::botcheck');
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init() : bool
	{
		hook::func("UID", 'um_is_bot::botcheck');
		hook::func("auth", 'um_is_bot::botcheck');

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = UID string
	 */
	public static function botcheck($u)
	{
		if ($u['account'] == "*" || $u['account'] == "0")
			return;

		$wpuser = new WPUser($u['account']);
		$nick = new User($u['uid']);

		if (!isset($wpuser->user_meta->robot))
			return false;

		$stuff = unserialize($wpuser->user_meta->robot);
		foreach($stuff as $s)
			if ($s == "IRC Bot")
			{
				S2S("SVS2MODE $nick->nick +B");
				return true;
			}
			return false;
	}
}