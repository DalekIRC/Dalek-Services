<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\				
//				
\\	Title:		Hook
//				
\\	Desc:		Server hooks. This is the function that
//				is used when calling and running hooks.
\\				
//	Examples:	hook::func("privmsg", function($u){});
\\				hook::run("privmsg", array());
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/


class hook {

	private static $actions = array(
		'privmsg' => array(),
		'preconnect' => array(),
		'postconnect' => array(),
		'connect' => array(),
		'notice' => array(),
		'join' => array(),
		'part' => array(),
		'quit' => array(),
		'ctcp' => array(),
		'ctcpreply' => array(),
		'mode' => array(),
		'kick' => array(),
		'error' => array(),
		'auth' => array(),
		'ping' => array(),
		'numeric' => array(),
		'away' => array(),
		'chghost' => array(),
		'batch' => array(),
		'UID' => array(),
		'SID' => array(),
		'SJOIN' => array(),
	);

	public static function run($hook, $args = array())
	{
		if (!empty(self::$actions[$hook]))
			foreach (self::$actions[$hook] as $f)
				$f($args);
			
	}

	public static function func($hook, $function) {
		self::$actions[$hook][] = $function;
	}
	public static function del($hook, $function) {
		for ($i = 0; isset(self::$actions[$hook][$i]); $i++)
		  if (self::$actions[$hook][$i] == $function)
		  array_splice(self::$actions[$hook],$i);
	}
}

