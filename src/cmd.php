<?php


class cmd {

	static $commands = array();

	public static function run($cmd, $args = array())
	{
		if (isset(self::$commands[$cmd]))
		{
			$f = self::$commands[$cmd]['func'];
			$f($args);
		}
	}

	public static function func($cmd, $function)
	{
		self::$commands[$cmd]['func'] = $function;
	}
	public static function setparc($cmd, $parc)
	{
		self::$commands[$cmd]['parc'] = $parc;
	}
	public static function setmod($cmd, $mod)
	{
		self::$commands[$cmd]['mod'] = $mod;
	}
	public static function del($cmd, $function)
	{
		foreach (self::$commands[$cmd] as $c)
			if ($c['func'] == $function)
			{
				self::$commands[$cmd] = NULL;
				unset(self::$commands[$cmd]);
			}
	}
}

hook::func("raw", function($u)
{
	global $os,$cf;
	$parv = explode(" ", $u['string']);
	if (is_numeric($parv[1]))
		return;
	
	/* one of those commands without a 'sender', spoof it as us? and retokenise */
	if ($parv[0][0] !== ":")
	{
		$u['string'] = ":".$cf['sid']." ".$u['string'];
		$parv = explode(" ", $u['string']);
	}
	$user = new User(mb_substr($parv[0],1));
	$str = strtolower($parv[1]);
	if (!isset(cmd::$commands[$str]))
	{
		if ($str == "privmsg")
		{
			$token = explode(" ",$u['string']);
			$nick = mb_substr($token[0],1);
			$dest = $parv[2];
			$string = ($p = explode(" :",$u['string'])) ? $p[1] : "";
			// lowercase the command item
			$string = str_replace($token[0],strtolower($token[0]),$string);
			hook::run("privmsg", array(
				"nick" => $nick,
				"dest" => $dest,
				"parv" => $string)
			);
			update_last($nick);
			return;
		}
		printf("421 $user->nick ".$parv[1]." :Unknown command\n");
		//if (isset($os))$os->log("WARNING: $user->nick used unknown command: ".$parv[1]);
		return;
	}
	$params = mb_substr($u['string'], strlen($parv[0]) + strlen($parv[1]) + 2);
	cmd::run($str, array('nick' => $user,
		'cmd' => $str,
		'params' => ltrim($params," :"),
		'parc' => cmd::$commands[$str]['parc']));
});

class Command {

	function __construct($modname,$cmd,$func,$parc)
	{
		if ($this->command_exists($cmd))
		{
			$this->success = false;
		}
		else $this->register_new_command($modname,$cmd,$func,$parc);
	}
	function command_exists($cmd)
	{
		if (in_array($cmd,cmd::$commands))
			return true;
		return false;
	}
	function register_new_command($modname,$cmd,$func,$parc)
	{
		global $commands;
		cmd::func(strtolower($cmd),$func);
		cmd::setparc(strtolower($cmd),$parc);
		cmd::setmod(strtolower($cmd),$modname);
		$this->success = true;
	}
}

function CommandAdd($modname, $cmd, $func, $parc) : bool
{
	if (!module_exists($modname))
	{
		return false;
	}

	$cmd = new Command($modname, $cmd, $func, $parc);
	if (!$cmd->success)
		return false;

	return true;
}