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
	
	function __construct($nick,$ident,$hostmask,$uid,$gecos)
	{
		global $servertime,$cf;
		
		$this->nick = $nick;
		$this->uid = $uid;

		$this->elmer = false;

		$this->sendraw("UID $nick 0 $servertime $ident $hostmask $uid $nick +oiqS * * * :$gecos");
		
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
		
		
	}
	function sendraw($string)
	{
		// Declare de globals;
		global $socket;
		echo "[SEND] $string\n";
		fputs($socket, ircstrip($string)."\n");
		
	}
	function msg($dest,$string)
	{
		if ($this->elmer)
			$string = preg_match('[rl]','w',$string);
		$this->sendraw(":$this->uid PRIVMSG $dest :$string");
	}
	function log($string){
		global $cf;
		
		$this->msg($cf['logchan'],$string);
	}
		
	function join($dest)
	{
		global $sql,$servertime;
		
		$chan = new Channel($dest);

		if ($chan->HasUser($this->uid))
			return;
		$timestamp = (isset($chan->timestamp)) ? $chan->timestamp : $servertime;
		$this->sendraw("SJOIN $timestamp $dest :~".$this->uid);
		$sql->insert_ison($dest,$this->uid);
	}
	function part($dest)
	{
		global $sql;
		
		$chan = new Channel($dest);
		if (!$chan){ return; }
		if (!$chan->HasUser($this->uid))
			return;
		$this->sendraw("SJOIN $chan->timestamp $dest :~".$this->uid);
		$sql->delete_ison($dest,$this->uid);
	}
	function notice($dest,$string)
	{
		
		if ($this->elmer)
			$string = preg_match("[rl]","w",$string);

		$uid = $this->uid;
		$tok = explode("<lf>",$string) ?? $string;
		if ($string == "Array"){ $tok = $string; }
		for ($i = 0; isset($tok[$i]); $i++){
			
			$this->sendraw(":$uid NOTICE $dest :".$tok[$i]);
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
			
		$this->sendraw(":$this->uid MODE $dest $string");
	}
	function svs2mode($nick,$string){
		$nick = new User($nick);
		if (!$nick->IsUser){ return; }
		
		$uid = $nick->uid;
		$nick->SetMode("$string");
		$this->sendraw(":$this->uid SVS2MODE $uid $string");
	}
	function svslogin($uid,$account)
	{
		$this->sendraw(":$this->uid SVSLOGIN * $uid $account");
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
}

hook::func("start", function(){
	global $ns,$cs,$bs,$os,$gb,$hs,$ms;
	$ns->join("#services");
	$cs->join("#services");
	$cs->join("#PossumsOnly");
	$bs->join("#services");
	$os->join("#services");
	$gb->join("#services");
	$hs->join("#services");
	$ms->join("#services");
	//global_notice("Services is back online. Have a great day!");
});


function global_notice($msg) : bool
{
	global $gb;
	$gb->notice("$*",$msg);
	return true;
}