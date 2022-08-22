<?php
/*
 *	(C) 2022 DalekIRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: Implements !fantasy commands
 *
 * 
 *	Version: 1
*/

class cs_fantasy {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "cs_fantasy";
	public $description = "ChanServ fantasy commands";
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
		hook::del("chanmsg", 'cs_fantasy::cmd');

	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		hook::func("chanmsg", 'cs_fantasy::cmd');
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd($u)
	{
		global $cf;
		$prefix = $cf['cmdprefix'] ?? "!";
		
		$parv = explode(" ",$u['params']);
		if ($parv[1][1] != $prefix) // not a fantasy command
			return;
		
		if ($parv[2][0] != "#") // they didn't specify the channel which is supposed to be the case
		{
			$u['mtags'][CHAN_CONTEXT] = $u['dest'];
			self::include_channel($u['dest'],$parv);
		}
		$parv[0] = "ChanServ";
		$u['params'] = implode(" ",$parv);
		var_dump($u['params']);
		cmd::run("privmsg", $u);
		
	}
	static function include_channel($chan, &$parv)
	{		
		for ($i = count($parv); $i >= 2; $i--)
		{
			echo "$i\n";
			$parv[$i] = $parv[$i - 1];
		}
	}
}

