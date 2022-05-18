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
\\	Title:		Class
//				
\\	Desc:		Hook class for Services.
//				
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// NickServ Class
class nickserv {

	private static $actions = array(
		'privmsg' => array(),
		'preconnect' => array(),
<<<<<<<< HEAD:src/hook.php
		'postconnect' => array(),
		'connect' => array(),
		'notice' => array(),
========
        'connect' => array(),
        'notice' => array(),
>>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a:src/NickServ/class.php
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
<<<<<<<< HEAD:src/hook.php
		'batch' => array(),
		'UID' => array(),
		'SID' => array(),
		'SJOIN' => array(),
	);
========
		'batch' => array()
    );
>>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a:src/NickServ/class.php

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
