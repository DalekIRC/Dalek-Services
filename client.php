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
		
		fputs($socket, ircstrip($string)."\n");
		
	}
	function msg($dest,$string)
	{
		
		$this->sendraw(":$this->uid PRIVMSG $dest :$string");
	}
	function log($string){
		global $cf;
		
		$this->msg($cf['logchan'],$string);
	}
		
	function join($dest)
	{
		global $servertime;
		
		$chan = find_channel($dest);
		if (!$chan){ return; }
		
		$this->sendraw("SJOIN ".$chan['timestamp']." $dest :~".$this->uid);
	}
	function notice($dest,$string)
	{
		$uid = $this->uid;
		$tok = explode("<lf>",$string) ?? $string;
		if ($string == "Array"){ $tok = $string; }
		for ($i = 0; isset($tok[$i]); $i++){
			
			$this->sendraw(":$uid NOTICE $dest :".$tok[$i]);
		}
	}
	function mode($dest,$string)
	{
		
		$this->sendraw(":$this->uid MODE $dest $string");
	}
	function svs2mode($nick,$string){
		
		if (!($nick = find_person($nick))){ return; }
		
		$uid = $nick['UID'];
		
		$this->sendraw(":$this->uid SVS2MODE $uid $string");
	}
	function svslogin($uid,$account)
	{
		global $sasl;
		
		if (isset($sasl[$uid])){ goto svsloginexists; }
		elseif (!($nick = find_person($uid))){ return; }
		
		
		$uid = $nick['UID'];
		
		svsloginexists:
		$this->sendraw(":$this->uid SVSLOGIN * $uid $account");
	}
	function exit()
	{
		global $sql;
		$sql::user_delete($uid);
	}
}

hook::func("start", function(){
	global $ns,$cs,$bs,$os,$gb,$hs,$ms;
	$ns->join("#services");
	$cs->join("#services");
	$cs->join("#Valeyard");
	$bs->join("#services");
	$os->join("#services");
	$gb->join("#services");
	$hs->join("#services");
	$ms->join("#services");
	$gb->notice("$*","Services is back online. Have a great day!");
});