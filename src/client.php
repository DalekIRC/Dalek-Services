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
\\	Title:		Client
//				
\\	Desc:		Client class to initialise a service.
//				
\\				
//	Example:	$yourBot = new Client($nick,$ident,$hostmask,$uid,$gecos);
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/


class Client {
	
	static $list = array();

	function __construct($nick,$ident,$hostmask,$uid = NULL, $gecos ,$modinfo = NULL)
	{
		global $servertime;
		$this->nick = $nick;
		$this->uid = $uid = generate_uid($nick);
		$this->modinfo = $modinfo;
		$this->cmds = NULL;
		S2S("UID $nick 0 $servertime $ident $hostmask $uid 0 +oiqS * * * :$gecos");
		$array = array(
			'nick' => $nick,
			'timestamp' => $servertime,
			'ident' => $ident,
			'realhost' => $hostmask,
			'uid' => $uid,
			'usermodes' => "+oiqS",
			'cloak' => $hostmask,
			'ip' => "",
			'sid' => Conf::$settings['info']['SID'],
			'ipb64' => "",
			'gecos' => $gecos);
		hook::run("UID", $array);
		self::add_to_client_list($this);

		if (isset(Conf::$settings['log']['channel'])) {
		  $this->join(Conf::$settings['log']['channel']);
		}
	}
	function __destruct()
	{
		$this->quit("Unloaded");
	}
	function sendraw($string)
	{
		// Declare de globals;
		global $socket;
		echo "[SEND] $string\n";
		fputs($socket, ircstrip($string)."\n");
		
	}
	function quit($msg = 'Connection closed')
	{
		global $sql;
		$quitstr = ":$this->uid QUIT :$msg";
		S2S($quitstr);
		self::del_from_client_list($this);
		$sql->user_delete($this->uid);
	}
	function msg($dest, ...$strings)
	{
		$mtags = generate_new_mtags($this);
		Filter::StringArray($this->nick,$t,$strings);

		foreach($strings as $string)
			S2S(":$this->uid PRIVMSG $dest :$string");
		
	}
	function msg_with_mtags($mtags, $dest, $string)
	{
		$new_mtags = generate_new_mtags($this);
		duplicate_mtags($mtags, $new_mtags);

		Filter::StringArray($this->nick,$mtags,$strings);

		$mtags_to_send = array_to_mtag($mtags);

		foreach($strings as $string)
		{
			/* We switched from <lf> to \n, so convert */
			$string = str_replace("<lf>", "\n",$string);

			$tok = array();
			if (strpos($string,"\n") !== false)
				$tok = explode("\n",$string);
			
			else
				$tok[0] = $string;

			for ($i = 0; isset($tok[$i]); $i++)		
				S2S("$mtags_to_send :$this->uid PRIVMSG $dest :".$tok[$i]);
		}
	}
	function tagmsg($mtags, $dest)
	{
		$new_mtags = generate_new_mtags($this);
		duplicate_mtags($mtags, $new_mtags);

		Filter::StringArray($this->nick,$mtags,$strings);

		$mtags_to_send = array_to_mtag($mtags);

		S2S("$mtags_to_send :$this->uid TAGMSG $dest");
	}
	function log($string)
	{
		if (isset(Conf::$settings['log']['channel'])) {
			$this->msg(Conf::$settings['log']['channel'],$string);
		}
	}
		
	function join(...$dests)
	{
		global $sql,$servertime;
		foreach($dests as $dest)
		{
			$chan = new Channel($dest);

			if ($chan->HasUser($this->uid))
				return;
			$timestamp = (isset($chan->timestamp)) ? $chan->timestamp : $servertime;
			$modes = (isset($chan->modes)) ? $chan->modes : "";
			S2S("SJOIN $timestamp $dest $modes :~@".$this->uid);
			$sql->insert_ison($dest,$this->uid);
		}
	}
	function part($dest,$reason = "")
	{
		global $sql;
		
		$chan = new Channel($dest);
		if (!$chan){ return; }
		if (!$chan->HasUser($this->uid))
			return;
		S2S(":$this->uid PART $dest :$reason");
		$sql->delete_ison($dest,$this->uid);
	}
	function notice($dest, ...$strings)
	{
		$mtags = generate_new_mtags();
		foreach ($strings as $string)
			$this->notice_with_mtags($mtags, $dest, $string);
	}
	function notice_with_mtags(array $mtags = NULL, $dest, ...$strings)
	{
		$new_mtags = generate_new_mtags($this);
		duplicate_mtags($mtags, $new_mtags);

		Filter::StringArray($this->nick,$mtags,$strings);

		$mtags_to_send = array_to_mtag($mtags);
		
		foreach($strings as $string)
		{
			/* We switched from <lf> to \n, so convert */
			$string = str_replace("<lf>", "\n",$string);
			
			$tok = array();
			if (strpos($string,"\n") !== false)
				$tok = explode("\n",$string);
			
			else
				$tok[0] = $string;

			for ($i = 0; isset($tok[$i]); $i++)		
				S2S("$mtags_to_send :$this->uid NOTICE $dest :".$tok[$i]);
		}
	}
	function mode($dest,$string)
	{
		if ($dest[0] == "#")
		{
			$chan = new Channel($dest);
			$tok = explode(" ",$string);
			if (isset($tok[1]))
			{
				$params = rparv($string);
				MeatballFactory($chan,$tok[0],$params,$this->nick);
			}
			else
			{
				$params = "";
				MeatballFactory($chan,$string,$params,$this->nick);
			}
		}
			
		S2S(":$this->uid MODE $dest $string");
	}
	function svs2mode($nick,$string){
		$nick = new User($nick);
		if (!$nick->IsUser)
			return;
		
		$nick->SetMode("$string");
		S2S(":$this->uid SVS2MODE $nick->uid $string");
	}
	function svslogin($uid,$account)
	{
		svslogin($uid, $account, $this->uid);
	}
	function up(Channel $chan, User $user)
	{
		$user = new User($user->uid);
		$chan = new Channel($chan->chan);
		$access = ChanAccessAsInt($chan,$user);
		if (!$access)
			return false;

		if ($access == 1)
			$this->mode($chan->chan,"+v $user->nick");

		elseif ($access == 2)
			$this->mode($chan->chan,"+h $user->nick");

		elseif ($access == 3)
			$this->mode($chan->chan,"+o $user->nick");

		elseif ($access == 4)
			$this->mode($chan->chan,"+ao $user->nick $user->nick");

		elseif ($access == 5)
			$this->mode($chan->chan,"+qo $user->nick $user->nick");
		
		return true;
	}
	function kill(User $user, $message = "No reason")
	{
		S2S(":$this->uid KILL $user->uid :$message");
		$user->exit();
	}
	function down(Channel $chan, User $user)
	{
		$access = ChanAccessAsInt($chan,$user);
		if (!$access)
			return false;

		if ($access == 1)
			$this->mode($chan->chan,"-v $user->nick");

		elseif ($access == 2)
			$this->mode($chan->chan,"-h $user->nick");

		elseif ($access == 3)
			$this->mode($chan->chan,"-o $user->nick");

		elseif ($access == 4)
			$this->mode($chan->chan,"-ao $user->nick $user->nick");

		elseif ($access == 5)
			$this->mode($chan->chan,"-qo $user->nick $user->nick");
		
		return true;
	}
	function kick($chan,$nick,$reason = '')
	{
		S2S(":$this->uid KICK $chan $nick :$reason");
		do_part($chan,$nick);
	}

	
	

	function add_to_client_list($client)
	{
		self::$list[] = $client;
	}
	function del_from_client_list($ourclient)
	{
		foreach(self::$list as $i => $client)
			if ($client == $ourclient)
			{
				self::$list[$i] = NULL;
				unset(self::$list[$i]);
			}
	}
	static function find($user)
	{
		$client = NULL;
		foreach(self::$list as $i => $client)
		{
			if (strtolower($client->nick) == strtolower($user) || $client->uid == $user)
					return $client;

		}
		return false;
	}
	static function find_by_uid($uid)
	{
		$client = NULL;
		foreach(self::$list as $client)
		{
			if (strtolower($client->uid) == strtolower($uid))
				return $client;
		}
		return false;
	}
}

function generate_uid($str)
{
	return Conf::$settings['info']['SID'].strtoupper(mb_substr(md5($str),0,6));
}
