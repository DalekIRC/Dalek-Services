<?php
/*				
	(C) 2022 DalekIRC Services
\\				
			pathweb.org
\\				
	GNU GENERAL PUBLIC LICENSE
\\				v3
				
\\				
				
\\	Title:		PRIVMSG
				
\\	Desc:		PRIVMSG redirection
\\				
				
\\				
				
\\	Version:	1
				
\\	Author:		Valware
				
*/

/* class name needs to be the same name as the file */
class privmsg {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "privmsg";
	public $description = "Provides PRIVMSG compatibility";
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
		/* Params: CommandAdd( this module name, command keyword, function, parameter count) */
		/* the function is a string reference to this class, the cmd_elmer method (function) */
		/* The last param is expected parameter count for the command */
		/* (both point to the same function which determines) */

		if (!CommandAdd($this->name, 'PRIVMSG', 'privmsg::cmd_privmsg', 1))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_privmsg($u)
	{
		update_last($u['nick']->nick);

		if (strpos($u['dest'],"@") !== false)
		{
			$n = explode("@",$u['dest']);
			$dest = $n[0];
		}
		else { $dest = $u['dest']; }
		
		if ($dest[0] == "#")
		{
			/* start and run our channel message hook */
			/* IMPORTANT:
			/* using `hook::func("chanmsg", x);` will not work if this module is not loaded */
			hook::run("chanmsg", $u);
			return;
		}
		if (is_numeric($dest[0]))
			$client = Client::find_by_uid($dest);
		
		else
			$client = Client::find($dest);

		/* shouldn't happen */
		if (!isset($client->nick))
			return;

		$parv = explode(" ", $u['params']);
		
		for ($i = 0; isset($parv[$i]); $i++)
			$parv[$i] = (isset($parv[$i + 1])) ? $parv[$i + 1] : NULL;

		$u['params'] = implode(" ",$parv);
		
		$nick = $u['nick'];
		$c = mb_substr(strtoupper($parv[0]),1);
		
		$found = 0;
		$found_elsweyr = 0;

		if ($client->nick == "OperServ" && !IsOper($nick))
		{
			$client->notice($nick->uid,"Permission denied!");
			return;
		}
		if (!strcasecmp($c,"help"))
		{
			if (isset($parv[1]))
			{
				foreach(ServCmd::$list as $cmd)
				{
					if (!strcasecmp($cmd->client,$client->nick) && !strcasecmp($cmd->command,$parv[1]))
					{
						$client->notice($nick->uid,"Help for command $cmd->command");
						$client->notice($nick->uid,$cmd->extended_help,"/msg $client->nick $cmd->syntax");
					}
				}
				return;
			}
			$found = 1;

			

			$client->notice($nick->uid,ul("Help available for $client->nick:"));

			if (empty(ServCmd::$list))
				$client->notice($nick->uid,"No commands have been loaded.");
			
			$f = 0;
			foreach(ServCmd::$list as $cmd)
			{
				if (!strcasecmp($cmd->client,$client->nick))
				{
					$f = 1;
					$client->notice($nick->uid,clean_align($cmd->command).$cmd->help_cmd_entry);
				}
			}
			if (!$f || !isset($client->modinfo))
			{
				$client->notice($nick->uid,"No commands have been loaded for this pseudoclient yet.");
				return;
			}
		}
		foreach(ServCmd::$list as $cmd)
		{
			if (!strcasecmp($cmd->client,$client->nick) && !strcasecmp($c,$cmd->command))
			{
				$found = 1;
				$function = $cmd->function;
				$function(array(
					"mtags" => $u['mtags'],
					"msg" => trim(mb_substr(implode(" ",$parv),1)),
					"parc" => count($parv),
					"target" => $client,
					"nick" => $nick)
				);
			}
			if (strcasecmp($cmd->client,$client->nick) && !strcasecmp($c,$cmd->command))
			{
				if (!strcasecmp($cmd->client,"OperServ"))
					continue;

				$found_elsweyr = 1;
				$found_in = $cmd->client;
			}
		}
		if (!$found && $found_elsweyr)
		{
			$client->notice($nick->uid, "Unrecognised command: \"".bold($c)."\"",
			"However, that command exists in ".bold($found_in).". Try:",
			"/msg $found_in HELP $c",
			"If you still think you're in the right place, try:",
			bold("/msg $client->nick HELP"));
		}
		if (!$found && !$found_elsweyr)
		{
			$client->notice($nick->uid,	"Unrecognised command: \"".bold($c)."\"",
							"For a list of commands available to you, type:",
							bold("/msg $client->nick HELP"));
		}
	}
    private static function update_away(User $nick, $away, $msg)
    {
        $away = ($away) ? "Y" : NULL;
        $conn = sqlnew();
        $prep = $conn->prepare("UPDATE dalek_user SET away = ?, awaymsg = ? WHERE UID = ?");
        $prep->bind_param("sss", $away, $msg, $nick->uid);
        $prep->execute();
    }
}


function generate_random_msgid()
{
	global $servertime;
	$rand = rand(0,$servertime * 5);
	
	$times = mb_substr($rand,-2);
	for ($i = 0; $i <= $times; $i++)
		$rand = md5($rand);
	
	return $rand;
}
