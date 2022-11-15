<?php

/*				
//	(C) 2022 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		Fake lag
//				
\\	Desc:		This is a countermeasure for flooding
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

class fakelag {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "fakelag";
	public $description = "Fake lag (anti-flood mechanism)";
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
		/* Let's check the config */
		__FakeLag::config_check();
		hook::func(HOOKTYPE_REHASH, '__FakeLag::config_check');
		return true;
	}
}


class __FakeLag {

	public static $list = [];
	public static $active = 0;

	
	/**Our config checking */
	public static function config_check()
	{
		if (isset(Conf::$settings['security settings']['fakelag']) && Conf::$settings['security settings']['fakelag']['active'] == "yes")
		{
			self::$active = 1;
			if (!isset(Conf::$settings['security settings']['fakelag']['limit']) || !is_numeric(Conf::$settings['security settings']['fakelag']['limit']))
				Conf::$settings['security settings']['fakelag']['limit'] = 10;
		}
		else self::$active = 0;
	}

	function __construct(User $nick, int $seconds)
	{
		$this->uid = $nick->uid;
		$this->lag_until = servertime() + $seconds;
		self::$list[] = $this;
	}
	static function find(User &$nick)
	{
		$s_nick = []; // list of users who were possibly killed in the next function

		/* Null the user object */
		if (self::cleanup($s_nick) && in_array($nick->uid,$s_nick))
		{
			$nick = NULL;
			return;
		}

		foreach (self::$list as &$user)
			if ($user && $user->uid == $nick->uid)
				return $user;
		return false;
	}
	static function cleanup(array &$s_nick = []) : int
	{
		$killed = 0; // if the user has been killed
		foreach(self::$list as $key => $item)
		{
			if ($item->lag_until <= servertime())
				unset(self::$list[$key]);

			elseif ($item->lag_until - servertime() >= Conf::$settings['security settings']['fakelag']['limit'])
			{
				$qmsg = "Your connection has been exterminated: Flood";
				S2S("KILL $item->uid :$qmsg");
						$s_nick[] = $item->uid;

				quit::cmd_quit(array("nick" => new User($item->uid), "params" => $qmsg));
				$killed++;
			}
		}
		return $killed;
	}
	static function add_fake_lag(User $nick, int $seconds)
	{
		$found = 0;
		foreach(self::$list as &$user)
		{
			if ($user && $user->uid = $nick->uid)
			{
				$found++;
				$user->lag_until += $seconds;
			}
		}
		if (!$found)
			new __FakeLag($nick,$seconds);

		self::$list = array_values(self::$list);
	}
}

if (!function_exists('IsFakeLag'))
{
	function IsFakeLag(User $nick)
	{
		if (!__FakeLag::$active)
			return 0;
		return (__FakeLag::find($nick)) ? 1 : 0;
	}
}
if (!function_exists('add_fake_lag'))
{
	function add_fake_lag(User &$nick, int $seconds)
	{
		if (!__FakeLag::$active)
			return;
		__FakeLag::add_fake_lag($nick,$seconds);
	}
}
