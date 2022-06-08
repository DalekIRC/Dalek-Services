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
		global $servertime,$cf;
		
		$this->nick = $nick;
		$this->uid = $uid = generate_uid($nick);
		$this->modinfo = $modinfo;
		$this->cmds = NULL;
		S2S("UID $nick 0 $servertime $ident $hostmask $uid 0 +oiqS * * * :$gecos");
		
		hook::run("UID", array(
			'nick' => $nick,
			'timestamp' => $servertime,
			'ident' => $ident,
			'realhost' => $hostmask,
			'uid' => $uid,
			'usermodes' => "+oiqS",
			'cloak' => $hostmask,
			'ip' => "",
			'sid' => $cf['sid'],
			'ipb64' => "",
			'gecos' => $gecos)
		);
		self::add_to_client_list($this);
		
		$this->join($cf['logchan']);
		
		
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
		foreach($strings as $string)
			S2S(":$this->uid PRIVMSG $dest :$string");
		
	}
	function log($string){
		global $cf;
		
		$this->msg($cf['logchan'],$string);
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
			S2S("SJOIN $timestamp $dest :~".$this->uid);
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
		foreach ($strings as $string)
			$this->notice_with_mtags(NULL, $dest, $string);
	}
	function notice_with_mtags(array $mtags = NULL, $dest, ...$strings)
	{
		$uid = $this->uid;
		$mtags_to_send = NULL;
		if ($mtags)
		{
			$mtags_to_send = "@";
			foreach ($mtags as $mkey => $mval)
				$mtags_to_send .= $mkey."=".$mval.";";

			$mtags_to_send = rtrim($mtags_to_send,";");
			$mtags_to_send .= " ";
		}

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
				S2S("$mtags_to_send:$uid NOTICE $dest :".$tok[$i]);
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
		if (!$nick->IsUser){ return; }
		
		$nick->SetMode("$string");
		S2S(":$this->uid SVS2MODE $nick->uid $string");
	}
	function svslogin($uid,$account)
	{
		S2S(":$this->uid SVSLOGIN * $uid $account");
	}
	function up(Channel $chan, User $user)
	{
		$access = ChanAccessAsInt($chan,$user);
		if (!$access)
			return;

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
	global $cf;
	return $cf['sid'].strtoupper(mb_substr(md5($str),0,6));
}