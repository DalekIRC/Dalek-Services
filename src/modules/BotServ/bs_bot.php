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
\\	Title: Ajoin
//	
\\	Desc: Auto-join on identify.
//	Allows you to add/remove to a list of channels you wish to
\\	be autojoined to when you identify with BotServ.
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class bs_bot {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "bs_bot";
	public $description = "BotServ BOT Command";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS dalek_botlist (
			id INT AUTO_INCREMENT NOT NULL,
			bot_nick VARCHAR(255) NOT NULL,
			bot_ident VARCHAR(50) NOT NULL,
			bot_host VARCHAR(255) NOT NULL,
			bot_gecos VARCHAR(255) NOT NULL,
			bot_created_on VARCHAR(255) NOT NULL,
			bot_created_by VARCHAR(255) NOT NULL,
			PRIMARY KEY (id)
		)");
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del("start", 'bs_bot::spawnbots');
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		$help_string = "View, add and modify the list of bots";
		$syntax = "BOT <ADD|DEL|EDIT|LIST> [<nick> <ident> <host> <realname/GECOS>]";
		$extended_help = 	"$help_string\n$syntax\n \n".
							"BOT ADD <nick> <ident> <host> <realname/GECOS>\n".
							"BOT DEL <nick>\n".
							"BOT EDIT <nick> [<ident> [<host> [<naam>]]]\n".
							"BOT LIST\n \n".
							"Examples:\n".
							"BOT ADD ButtServ butts serving.butts.since.2022 Butt Bot\n".
							"BOT DEL DumbServ\n".
							"BOT EDIT LolServ LmaoServ\n \n".
							"Important: You cannot change non-BotServ bot information this way.";

		if (!AddServCmd(
			'bs_bot', /* Module name */
			'BotServ', /* Client name */
			'BOT', /* Command */
			'bs_bot::cmd_bot', /* Command function */
			$help_string, /* Help string */
			$syntax, /* Syntax */
			$extended_help /* Extended help */
		)) return false;

		/*spawn de bots */
		if (IsConnected())
			self::spawnbots();

		hook::func("start", 'bs_bot::spawnbots');
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_bot($u)
	{
		$bs = $u['target'];
		$nick = $u['nick'];
		$account = $nick->account ?? NULL;
		$parv = explode(" ",$u['msg']);
		$cmd = $parv[0] ?? NULL;
		$flag = (isset($parv[1])) ? strtolower($parv[1]) : NULL;
		$botn = (isset($parv[2])) ? $parv[2] : NULL;
		if ($cmd !== "bot") { return; }
		$botI = (isset($parv[3])) ? $parv[3] : NULL;
		$botH = (isset($parv[4])) ? $parv[4] : NULL;
		$botgecos = "";
		for($i = 5; isset($parv[$i]); $i++)
			$botgecos .= $parv[$i]." ";
		$botgecos = rtrim($botgecos," ");
		$botgecos = (!strlen($botgecos)) ? NULL : $botgecos;
		if (!$account){ $bs->notice($nick->nick,IRC("ERR_NOTLOGGEDIN")); return; }
		if ($flag)
		{
			if (!strcasecmp($flag,"add") && $botn)
			{
				if (!$botn || !$botI || !$botH || !$botgecos)
				{
					$bs->notice($nick->uid,"Syntax: BOT ADD <nick> <ident> <host> <realname/GECOS>");
					return;
				}
				if ($lookup = new User($botn) && $lookup->IsUser)
				{
					$bs->notice($nick->nick,"There is already someone online with that nick.");
					return;
				}
				$bs->log("$nick->nick ($nick->ident@$nick->realhost) used BOT ADD to add new bot $botn ($botI@$botH) $botgecos");
				$bot = self::bot_add($botn,$botI,$botH,$botgecos,$nick->nick);
				$bot->log("$bot->nick reporting for duty!");
			}
			elseif ($flag == "del")
			{
				if (!$botn)
				{
					$bs->notice($nick->uid,"Syntax: BOT DEL <bot nick>");
					return;
				}
				if (!$bot = Client::find("$botn"))
				{
					$bs->notice($nick->uid,"Bot '$botn' does not exist.");
					return;
				}
				bs_bot::bot_del($botn);
				$bot->quit("This bot has been deleted");
				$bs->log("$nick->nick ($nick->ident@$nick->realhost) used BOT DEL and deleted $bot->nick from the bot list.");
				
				return;
			}
			elseif ($flag == "edit")
			{
				if (!$botn)
				{
					$bs->notice($nick->uid,"Syntax: BOT <nick> <new nick> [<new ident> [<new host> [<new realname/GECOS>]]]");
					return;
				}
				/* convert 'em cos different params :( */
				$bot_new = $botI;
				$botI = $botH;
				$botH = (isset($parv[5])) ? $parv[5] : NULL;
				$botgecos = "";
				for($i = 6; isset($parv[$i]); $i++)
					$botgecos .= $parv[$i]." ";
				$botgecos = rtrim($botgecos," ");
				$botgecos = (!strlen($botgecos)) ? NULL : $botgecos;

				if (!$botbot = Client::find($botn))
				{
					$bs->notice($nick->uid,"Bot '$botn' does not exist.");
					return;
				}
				$bot = new User($botn);
				$newstr = "";
				$conn = sqlnew();
				if ($bot_new !== $botn)
				{
					$prep = $conn->prepare("UPDATE dalek_botlist SET bot_nick = ? WHERE bot_nick = ?");
					$prep->bind_param("ss",$bot_new,$botn);
					$prep->execute();
					$prep = $conn->prepare("UPDATE dalek_user SET nick = ? WHERE nick = ?");
					$prep->bind_param("ss",$bot_new,$botn);
					$prep->execute();
					S2S(":$bot->uid NICK $bot_new");
					$newstr .= "$bot_new!";
					$newbot = $botbot;
					Bot::del_from_bot_list($botbot);
					$newbot->nick = $botn;
					Bot::add_to_bot_list($newbot);
				}
				else $newstr .= "$botn!";

				if ($botI)
				{
					$prep = $conn->prepare("UPDATE dalek_botlist SET bot_ident = ? WHERE bot_nick = ?");
					$prep->bind_param("ss",$botI,$botn);
					$prep->execute();
					$prep = $conn->prepare("UPDATE dalek_user SET ident = ? WHERE nick = ?");
					$prep->bind_param("ss",$botI,$botn);
					$prep->execute();
					$newstr .= "$botI@";
					S2S(":$bot->uid SETIDENT $botI");
				}
				else $newstr .= "$bot->ident@";
				if ($botH)
				{
					$prep = $conn->prepare("UPDATE dalek_botlist SET bot_host = ? WHERE bot_nick = ?");
					$prep->bind_param("ss",$botH,$botn);
					$prep->execute();
					$prep = $conn->prepare("UPDATE dalek_user SET realhost = ? WHERE nick = ?");
					$prep->bind_param("ss",$botH,$botn);
					$prep->execute();
					$newstr .= "$botH";
					S2S(":$bot->uid SETHOST $botH");
				}
				else $newstr .= "$bot->realhost";

				if ($botgecos)
				{
					$prep = $conn->prepare("UPDATE dalek_botlist SET bot_gecos = ? WHERE bot_nick = ?");
					$prep->bind_param("ss",$botgecos,$botn);
					$prep->execute();
					$prep = $conn->prepare("UPDATE dalek_user SET gecos = ? WHERE nick = ?");
					$prep->bind_param("ss",$botgecos,$botn);
					$prep->execute();
					$newstr .= "$botgecos";
					S2S(":$bot->uid SETNAME $botgecos");
				}
			}
			elseif ($flag == "list")
			{
				foreach(Bot::$botlist as $bot)
					$bs->notice($nick->uid,"$bot->nick");
			}
		}
		else
		{
			$bs->notice($nick->nick,"Syntax: BOT <ADD|DEL|EDIT|LIST> [<nick> <ident> <host> <realname/GECOS>]");
			return;
		}
	}
	
	public static function spawnbots()
	{
		$conn = sqlnew();
		$result = $conn->query("SELECT * FROM dalek_botlist");
		if (!$result || !$result->num_rows)
			return; // no bots to spawn

		while ($row = $result->fetch_assoc())
			new Bot($row['bot_nick'],$row['bot_ident'],$row['bot_host'],NULL,$row['bot_gecos'],'bs_bot');
		
	}
	function bot_add($nick,$ident,$host,$gecos,$from){
		
		$conn = sqlnew();
		if (!$conn) { return false; }

		else {
			$servertime = servertime();
			$prep = $conn->prepare("INSERT INTO dalek_botlist (bot_nick,bot_ident,bot_host,bot_gecos,bot_created_by,bot_created_on) VALUES (?, ?, ?, ?, ?, ?)");
			$prep->bind_param("ssssss",$nick,$ident,$host,$gecos,$from,$servertime);
			$prep->execute();
		}
		$bot = new Bot($nick,$ident,$host,NULL,$gecos,'bs_bot');
		return $bot;
	}
	function bot_del($botn)
	{		
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$prep = $conn->prepare("DELETE FROM dalek_botlist WHERE bot_nick = ?");
			$prep->bind_param("s",$botn);
			$prep->execute();
		}
	}
}


class Bot extends Client
{
	public static $botlist = [];
	
	function __construct($nick,$ident,$hostmask,$uid = NULL, $gecos ,$modinfo = NULL)
	{
		Client::__construct($nick,$ident,$hostmask,$uid = NULL, $gecos ,$modinfo = NULL);
		$this->IsBotServBot = true;
		self::add_to_bot_list($this);
	}
	function quit($reason = "Connection closed")
	{
		self::del_from_bot_list($this);
		Client::quit($reason);
	}
	static function add_to_bot_list($client)
	{
		self::$botlist[] = $client;
	}
	static function del_from_bot_list($ourclient)
	{
		foreach(self::$botlist as $i => $client)
			if ($client == $ourclient)
			{
				self::$botlist[$i] = NULL;
				unset(self::$botlist[$i]);
			}
	}
	static function edit_bot_in_botlist($name,$newname)
	{
		$l = &Bot::$list;
		foreach($l as $i => &$client)
		{
			if (!strcasecmp($client->nick,$name))
				$client->name = $newname;
		}
	}
	static function find($user)
	{
		$client = NULL;
		foreach(self::$botlist as $i => $client)
		{
			if (strtolower($client->nick) == strtolower($user) || $client->uid == $user)
					return $client;

		}
		return false;
	}
	static function find_by_uid($uid)
	{
		$client = NULL;
		foreach(self::$botlist as $client)
		{
			if (strtolower($client->uid) == strtolower($uid))
				return $client;
		}
		return false;
	}
}