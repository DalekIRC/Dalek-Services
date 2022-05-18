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
\\	Title: Ajoin
//	
\\	Desc: Auto-join on identify.
//	Allows you to add/remove to a list of channels you wish to
\\	be autojoined to when you identify with NickServ.
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class os_showmeta {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "os_showmeta";
	public $description = "NickServ AJOIN Command";
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
		operserv::func("privmsg", 'os_showmeta::cmd_smeta');
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_smeta($u)
	{
		global $os;
		$src = new User($u['nick']);
		$parv = explode(" ",$u['msg']);
		if ($parv[0] !== "!meta" || !IsOper($src))
			return;
		
		$nick = new User($parv[1]);
		foreach($nick->meta as $key => $value)
			$os->notice($src->uid,"$key: $value");

		$user = new WPUser($nick->account);
		foreach($user->user_meta as $key => $value)
			$os->notice($src->uid,"$key: $value");


	}
}
