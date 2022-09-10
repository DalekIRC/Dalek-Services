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
\\	Title:		PRIVMSG
//				
\\	Desc:		PRIVMSG redirection		
//
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
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
	 * $nick = User object
	 */
	public static function cmd_privmsg($u)
	{

		if (!isset($u['nick']->nick))
			return;
		$nick = $u['nick'];
		/* if we've got the module loaded for fakelag and
			if they're fakelagged */ 
		if (module_exists("fakelag") && $nick->IsUser && !$nick->IsService && !IsOper($nick))
		{
			if (IsFakeLag($nick))
			{
				if (!isset($u['mtags'][RECYCLED_MESSAGE]))
				{
					add_fake_lag($nick, 1);
				}
				if (!$nick)
					return;
				add_mtag($u['mtags'], RECYCLED_MESSAGE, "true"); // let future us know it's a recycled message lol
				Buffer::add_to_buffer(array_to_mtag($u['mtags'])." ".$u['raw']); // recycle it to the buffer
				return;
			}
			else add_fake_lag($nick, 1);
		}

		/* User may have been killed from fake lag so $nick now has the potential to be NULL,
		   so lets just make sure we're still here */
		if (!$nick)
			return;
		
		update_last($nick->nick);

		/* Check if they have addressed us as nick@host */
		if (strpos($u['dest'],"@") !== false)
		{
			$n = explode("@",$u['dest']);
			$dest = $n[0];
		}
		else { $dest = $u['dest']; }
		
		/* Check if it's actually a channel lol */
		if ($dest[0] == "#")
		{
			/* start and run our channel message hook
			 * IMPORTANT:
			 * using `hook::func(HOOKTYPE_CHANNEL_MESSAGE, x);` will not work if this module is not loaded */
			hook::run(HOOKTYPE_CHANNEL_MESSAGE, $u);
			return;
		}

		$client = NULL;
		/* Bot-check
		 * Here is where we check if we're supporting botz and if so, deal w/ it
		 */
		if (class_exists('Bot'))
			$client = Bot::find_by_uid($dest) ?? Bot::find($dest);
				
			
		/* check if we've got a UID or a nick and return the Client object */

		if (!$client && is_numeric($dest[0]))
			$client = Client::find_by_uid($dest);
		
		else
			$client = Client::find($dest);

		/* shouldn't happen */
		if (!isset($client->nick))
			return;

		/* just shifting them parvs back one space */
		$parv = explode(" ", $u['params']);
		for ($i = 0; isset($parv[$i]); $i++)
			$parv[$i] = (isset($parv[$i + 1])) ? $parv[$i + 1] : NULL;
		$u['params'] = implode(" ",$parv);
		
		
		if (!$nick->uid) // bug
		{
			SVSLog("Bug: User lookup by UID for a command failed - is the database synced properly?");
			return;
		}
		/* Command they wanna perform */
		$c = mb_substr(strtoupper($parv[0]),1);
		
		/* Looking for our command */
		$found = 0;
		$found_elsweyr = 0;
		/* if we have a channel-context then we parrot it back */
		$mtags = (isset($u['mtags'][CHAN_CONTEXT])) ? [CHAN_CONTEXT => $u['mtags'][CHAN_CONTEXT]] : NULL;
		if (!$mtags)
			$mtags = (isset($u['mtags'][CHAN_CONTEXT])) ? [CHAN_CONTEXT => $u['mtags'][CHAN_CONTEXT]] : NULL;

		/* If they're messaging OperServ and they're not an oper, deny them */
		if (!strcasecmp($client->nick,"OperServ") && !IsOper($nick))
		{
			$client->notice_with_mtags($mtags,$nick->uid,"Permission denied!");
			return;
		}
		if (!strcasecmp($c,"help"))
		{
			$helpfound = 0;
			$helpfound_elsweyr = 0;
			$helpfound_elsweyr_user = "";
			if (isset($parv[1]))
			{
				foreach(ServCmd::$list as $cmd)
				{
					if (!strcasecmp($cmd->client,$client->nick) && !strcasecmp($cmd->command,$parv[1]))
					{
						$helpfound = 1;
						$client->notice_with_mtags($mtags,$nick->uid,"Help for command $cmd->command");
						$client->notice_with_mtags($mtags,$nick->uid,$cmd->extended_help,"/msg $client->nick $cmd->syntax");
					}
					if (strcasecmp($cmd->client,$client->nick) && !strcasecmp($cmd->command,$parv[1])) // found elsweyr
						$helpfound_elsweyr = 1;
						$helpfound_elsweyr_user = $cmd->client;
				}
				
				if (!$helpfound && !$helpfound_elsweyr)
					$client->notice_with_mtags($mtags,$nick->uid,"No help available for that command.");

				elseif (!$helpfound && $helpfound_elsweyr && $helpfound_elsweyr_user != "OperServ")
				{
					$client->notice_with_mtags($mtags,$nick->uid, "Unrecognised command: \"".bold($parv[1])."\"",
					"However, that command exists in ".bold($helpfound_elsweyr_user).". Try:",
					"/msg $helpfound_elsweyr_user HELP ".$parv[1]."",
					"If you still think you're in the right place, try:",
					bold("/msg $client->nick HELP"));
				}
				return;
			}
			$found = 1;

			

			$client->notice_with_mtags($mtags,$nick->uid,ul("Help available for $client->nick:"));

			if (empty(ServCmd::$list))
				$client->notice_with_mtags($mtags,$nick->uid,"No commands have been loaded.");
			
			$f = 0;
			foreach(ServCmd::$list as $cmd)
			{
				if (!strcasecmp($cmd->client,$client->nick) || (isset($client->IsBotServBot) && !strcasecmp($cmd->client,"ChanServ")))
				{
					$f = 1;
					$client->notice_with_mtags($mtags,$nick->uid,clean_align($cmd->command).$cmd->help_cmd_entry);
				}
			}
			if (!$f || !isset($client->modinfo))
			{
				$client->notice_with_mtags($mtags,$nick->uid,"No commands have been loaded for this pseudoclient yet.");
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
			$client->notice_with_mtags($mtags,$nick->uid, "Unrecognised command: \"".bold($c)."\"",
			"However, that command exists in ".bold($found_in).". Try:",
			"/msg $found_in HELP $c",
			"If you still think you're in the right place, try:",
			bold("/msg $client->nick HELP"));
		}
		if (!$found && !$found_elsweyr)
		{
			$client->notice_with_mtags($mtags,$nick->uid,	"Unrecognised command: \"".bold($c)."\"",
							"For a list of commands available to you, type:",
							bold("/msg $client->nick HELP"));
		}
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
