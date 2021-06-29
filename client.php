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
	
	function __construct($nick,$ident,$hostmask,$uid,$gecos){
		global $servertime,$cf;
		
		$this->nick = $nick;
		
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
	function sendraw($string){
		// Declare de globals;
		global $socket;
		
		fputs($socket, ircstrip($string)."\n");
		
	}
	function msg($dest,$string){
		
		$this->sendraw(":$this->nick PRIVMSG $dest :$string");
	}
	function join($dest){
		global $servertime;
		
		$chan = find_channel($dest);
		if (!$chan){ return; }
		
		$this->sendraw("SJOIN ".$chan['timestamp']." $dest :~".$this->nick);
	}
	function notice($dest,$string){
		
		$this->sendraw(":$this->nick NOTICE $dest :$string");
		
	}
	function mode($dest,$string){
		
		$this->sendraw(":$this->nick MODE $dest $string");
	}
}
