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
\\	Title: bbServ
//	
\\	Desc: bbServ for bbPress (bbForums plugin for WordPress)
//	
\\	
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
include("bbserv.conf");
class bbserv {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "bbserv";
	public $description = "bbServ for bbForums PseudoClient";
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
		global $bbserv;
		$bbs = Client::find($bbserv['nick']);
		$bbs->quit();
	}


	function __init()
	{

		hook::func("connect", 'bbserv::spawn_client');
			
		if (IsConnected())
			if (!bbserv::spawn_client())
				return false;

		return true;
	}

	function spawn_client()
	{
		global $bbserv,$bbs,$cf;
			
		// spawn client with $bbs
		$bbs = new Client($bbserv['nick'],$bbserv['ident'],$bbserv['hostmask'],$bbserv['uid'],$bbserv['gecos'],'bbserv');
		if (!$bbs)
			return false;
		
		$bbs->join("#dalek-support","#dalek-devel");
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