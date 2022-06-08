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

// BotServ Class
class botserv {

    private static $actions = array(
        'privmsg' => array(),
		'preconnect' => array(),
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
		'batch' => array()
    );

    public static function run($hook, $args = array()) {
        if (!empty(self::$actions[$hook])) {
            foreach (self::$actions[$hook] as $f) {
                $f($args);
            }
        }
    }

    public static function func($hook, $function) {
        self::$actions[$hook][] = $function;
    }

}
