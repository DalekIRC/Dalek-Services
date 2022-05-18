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
<<<<<<< HEAD
\\	Title:		Class
//				
\\	Desc:		Hook class for Services.
//				
\\				
//				
\\				
=======
\\	Title:		Hook
//				
\\	Desc:		Server hooks. This is the function that
//				is used when calling and running hooks.
\\				
//	Examples:	hook::func("privmsg", function($u){});
\\				hook::run("privmsg", array());
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

<<<<<<< HEAD
// NickServ Class
class nickserv {
=======

class hook {
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a

	private static $actions = array(
		'privmsg' => array(),
		'preconnect' => array(),
<<<<<<< HEAD
<<<<<<<< HEAD:src/hook.php
		'postconnect' => array(),
		'connect' => array(),
		'notice' => array(),
========
        'connect' => array(),
        'notice' => array(),
>>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a:src/NickServ/class.php
=======
		'postconnect' => array(),
		'connect' => array(),
		'notice' => array(),
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
<<<<<<<< HEAD:src/hook.php
=======
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		'batch' => array(),
		'UID' => array(),
		'SID' => array(),
		'SJOIN' => array(),
	);
<<<<<<< HEAD
========
		'batch' => array()
    );
>>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a:src/NickServ/class.php
=======
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a

	public static function run($hook, $args = array())
	{
		if (!empty(self::$actions[$hook]))
			foreach (self::$actions[$hook] as $f)
<<<<<<< HEAD
				$f($args);
			
=======
			{
				if ($f($args) == "HOOK_DENY")
					return;
			}
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
=======

>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
