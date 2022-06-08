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
\\	Title: BugServ
//	
\\	Desc: BugServ for BugServ stuff lmao
//	
\\	
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
include("bugserv.conf");
class BugServ {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "BugServ";
	public $description = "BugServ PseudoClient";
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
		global $BugServ;
		$bbs = Client::find($BugServ['nick']);
		$bbs->quit();
	}


	function __init()
	{

		hook::func("connect", 'BugServ::spawn_client');
			
		if (IsConnected())
			if (!BugServ::spawn_client())
				return false;

		include "modules.conf";
		return true;
	}

	function spawn_client()
	{
		global $BugServ,$cf;
		/* spawn client with $bbs
		 * You don't need to store this anywhere as it's done automatically
		 * You can find this client from anywhere by finding the global $BugServ:
		 * $bgs = Client::find($BugServ['nick']);
		 */
		$bgs = new Client($BugServ['nick'],$BugServ['ident'],$BugServ['hostmask'],$BugServ['uid'],$BugServ['gecos'],'BugServ');
		if (!$bgs)
			return false;
		
		$bgs->join("#dalek-support","#dalek-devel");
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