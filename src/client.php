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
	
<<<<<<< HEAD
	static $list = array();

	function __construct($nick,$ident,$hostmask,$uid = NULL, $gecos ,$modinfo = NULL)
=======
	function __construct($nick,$ident,$hostmask,$uid,$gecos)
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	{
		global $servertime,$cf;
		
		$this->nick = $nick;
<<<<<<< HEAD
		$this->uid = $uid = generate_uid($nick);
		$this->modinfo = $modinfo;
		$this->cmds = NULL;
		S2S("UID $nick 0 $servertime $ident $hostmask $uid 0 +oiqS * * * :$gecos");
=======
		$this->uid = $uid;


		$this->sendraw("UID $nick 0 $servertime $ident $hostmask $uid $nick +oiqS * * * :$gecos");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		
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
<<<<<<< HEAD
		self::add_to_client_list($this);
		$this->user = new User($this->nick);
		$this->join($cf['logchan']);
		
		
	}
	function __destruct()
	{
		$me = new User($this->nick);
		$me->exit();
	}
=======
		
		
	}
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	function sendraw($string)
	{
		// Declare de globals;
		global $socket;
		echo "[SEND] $string\n";
		fputs($socket, ircstrip($string)."\n");
		
	}
<<<<<<< HEAD
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
		$nick = new User($this->uid);

		foreach($strings as $string)
		{	
			if (function_exists('IsElmer') && IsElmer($nick))
				$string = preg_match('[rl]','w',$string);
			S2S(":$this->uid PRIVMSG $dest :$string");
		}
=======
	function msg($dest,$string)
	{
		$nick = new User($this->nick);
		if (function_exists('IsElmer') && IsElmer($nick))
			$string = preg_match('[rl]','w',$string);
		$this->sendraw(":$this->uid PRIVMSG $dest :$string");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	}
	function log($string){
		global $cf;
		
		$this->msg($cf['logchan'],$string);
	}
		
<<<<<<< HEAD
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
=======
	function join($dest)
	{
		global $sql,$servertime;
		
		$chan = new Channel($dest);

		if ($chan->HasUser($this->uid))
			return;
		$timestamp = (isset($chan->timestamp)) ? $chan->timestamp : $servertime;
		$this->sendraw("SJOIN $timestamp $dest :~".$this->uid);
		$sql->insert_ison($dest,$this->uid);
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	}
	function part($dest)
	{
		global $sql;
		
		$chan = new Channel($dest);
		if (!$chan){ return; }
		if (!$chan->HasUser($this->uid))
			return;
<<<<<<< HEAD
		S2S("SJOIN $chan->timestamp $dest :~".$this->uid);
		$sql->delete_ison($dest,$this->uid);
	}
	function notice($dest, ...$strings)
	{
		$nick = new User($this->nick);
		$uid = $this->uid;

		foreach($strings as $string)
		{
			/* TO DO: Move this into an actual part of a filter system... */
			if (function_exists('IsElmer') && IsElmer($nick))
				$string = str_replace(array('r','R','l','L'),array('w','W','w','W'),$string);

			/* We switched from <lf> to \n, so convert */
			$string = str_replace("<lf>", "\n",$string);

			$tok = array();
			if (strpos($string,"\n") !== false)
				$tok = explode("\n",$string);
			
			else
				$tok[0] = $string;

			for ($i = 0; isset($tok[$i]); $i++)		
				S2S(":$uid NOTICE $dest :".$tok[$i]);
		}
	}
	function notice_with_mtags(array $mtags = NULL, $dest, ...$strings)
	{
		$nick = new User($this->nick);
		$uid = $this->uid;
		
		if ($mtags)
		{
			$mtags_to_send = "@";
			foreach ($mtags as $mkey => $mval)
				$mtags_to_send .= $mkey."=".$mval.";";

			$mtags_to_send = rtrim($mtags_to_send,";");

		}

		foreach($strings as $string)
		{
			/* TO DO: Move this into an actual part of a filter system... */
			if (function_exists('IsElmer') && IsElmer($nick))
				$string = str_replace(array('r','R','l','L'),array('w','W','w','W'),$string);

			/* We switched from <lf> to \n, so convert */
			$string = str_replace("<lf>", "\n",$string);

			$tok = array();
			if (strpos($string,"\n") !== false)
				$tok = explode("\n",$string);
			
			else
				$tok[0] = $string;

			for ($i = 0; isset($tok[$i]); $i++)		
				S2S("$mtags_to_send :$uid NOTICE $dest :".$tok[$i]);
=======
		$this->sendraw("SJOIN $chan->timestamp $dest :~".$this->uid);
		$sql->delete_ison($dest,$this->uid);
	}
	function notice($dest,$string)
	{
		$nick = new User($this->nick);
		if (function_exists('IsElmer') && IsElmer($nick))
			$string = str_replace(array('r','R','l','L'),array('w','W','w','W'),$string);

		$uid = $this->uid;
		$tok = explode("<lf>",$string) ?? $string;
		if ($string == "Array"){ $tok = $string; }
		for ($i = 0; isset($tok[$i]); $i++){
			
			$this->sendraw(":$uid NOTICE $dest :".$tok[$i]);
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
			
<<<<<<< HEAD
		S2S(":$this->uid MODE $dest $string");
=======
		$this->sendraw(":$this->uid MODE $dest $string");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	}
	function svs2mode($nick,$string){
		$nick = new User($nick);
		if (!$nick->IsUser){ return; }
		
<<<<<<< HEAD
		$nick->SetMode("$string");
		S2S(":$this->uid SVS2MODE $nick->uid $string");
	}
	function svslogin($uid,$account)
	{
		S2S(":$this->uid SVSLOGIN * $uid $account");
=======
		$uid = $nick->uid;
		$nick->SetMode("$string");
		$this->sendraw(":$this->uid SVS2MODE $uid $string");
	}
	function svslogin($uid,$account)
	{
		$this->sendraw(":$this->uid SVSLOGIN * $uid $account");
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
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
=======
		$this->sendraw(":$this->uid KICK $chan $nick :$reason");
		do_part($chan,$nick);
	}
}

hook::func("start", function(){
	global $ns,$cs,$bs,$os,$gb,$hs,$ms,$cf;
	$ns->join($cf['logchan']);
	$cs->join($cf['logchan']);
	$bs->join($cf['logchan']);
	$os->join($cf['logchan']);
	$gb->join($cf['logchan']);
	$hs->join($cf['logchan']);
	$ms->join($cf['logchan']);
	//global_notice("Services is back online. Have a great day!");
});


function global_notice($msg) : bool
{
	global $gb;
	$gb->notice("$*",$msg);
	return true;
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
}