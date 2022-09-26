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
\\	Title: Dictionary
//	
\\	Desc: Dictionary for dictionary stuff lmao
//	
\\	
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
include("dictionary.conf");
class Dictionary {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "Dictionary";
	public $description = "Dictionary PseudoClient";
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
		global $Dictionary;
		$bbs = Client::find($Dictionary['nick']);
		$bbs->quit();
	}


	function __init()
	{

		hook::func("connect", 'Dictionary::spawn_client');
			
		if (IsConnected())
			if (!Dictionary::spawn_client())
				return false;

		include "modules.conf";
		return true;
	}

	static function spawn_client()
	{
		global $Dictionary;
		/* spawn client with $dict
		 * You don't need to store this anywhere as it's done automatically
		 * You can find this client from anywhere by finding the global $Dictionary:
		 * $dict = Client::find($Dictionary['nick']);
		 */
		$dict = new Client($Dictionary['nick'],$Dictionary['ident'],$Dictionary['hostmask'],$Dictionary['uid'],$Dictionary['gecos'],'Dictionary');
		if (!$dict)
			return false;
		
		$dict->join("#dalek-support","#dalek-devel");
		return true;
	}


	/* hooking system you can copy and paste to yours with no edits needed */
	private static $actions = array();
	public static function run($hook, $args = array())
	{
		if (!empty(self::$actions[$hook]))
			foreach (self::$actions[$hook] as $f)
				$f($args);
	}

	public static function func($hook, $function)
	{
		self::$actions[$hook][] = $function;
	}
	public static function del($hook, $function)
	{
		for ($i = 0; isset(self::$actions[$hook][$i]); $i++)
			if (self::$actions[$hook][$i] == $function)
				array_splice(self::$actions[$hook],$i);
	}
}