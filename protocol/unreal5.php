<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		Protocol
//				
\\	Desc:		Class for the server itself which hold functions
//				which use the IRC protocol.
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/




class Server
{
	
	function __construct($server,$port,$password)
	{
				
		// INITIALISING CONNECT SEQUENCE lmao
		$this->connect($server,$port,$password);
	
	}
	private function connect($server,$port,$password)
	{
		
		// Declare de globals;
		global $socket,$nickserv,$chanserv,$botserv,$operserv,$hostserv,$servertime,$svs,$cf;
		
		// Anything we wanna initialise before we connect
		
		$this->sid = $cf['sid'];
		$this->name = $cf['servicesname'];
		/* pre connect shit */
		
		// we are disabling verification for now until built upon more :>
		// create ssl context
		$context = stream_context_create(['ssl' => [
			'verify_peer'  => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
			'ciphers' => 'ECDHE-ECDSA-AES256-GCM-SHA384'
		]]);

		//opening socket YO
		$socket = stream_socket_client($server.':'.$port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
		
		
		
		
		$this->sendraw("PASS $password");
		$this->sendraw("PROTOCTL EAUTH=".$cf['servicesname']." SID=".$cf['sid']);
		$this->sendraw("PROTOCTL NOQUIT NICKv2 SJOIN SJ3 CLK TKLEXT2 NICKIP ESVID MLOCK EXTSWHOIS SJSBY MTAGS");
		$this->sendraw("SERVER ".$cf['servicesname']." 1 :Dalek IRC Services");
		$this->sendraw("EOS");
		$this->sendraw("MD client ".$cf['sid']." saslmechlist :PLAIN");
		

	}
	function svs2mode($nick,$string)
	{
		
		if (!($nick = find_person($nick))){ return; }
		
		$uid = $nick['UID'];
		
		$this->sendraw(":$this->sid SVS2MODE $uid $string");
	}
	function sendraw($string)
	{
		// Declare de globals;
		global $socket;
		
		fputs($socket, ircstrip($string)."\n");
		
	}
	function Send($string)
	{
		$this->sendraw(":".$this->sid." ".$string);
	}
	function svskill($uid,$string)
	{
		global $cf;
		$sid = $cf['sid'];
		
		$this->sendraw(":$sid SVSKILL $uid :$string");
	}
	function shout($string)
	{
		global $me;
		echo "[".$me."][-->] ".$string."\n";
	}
	function hear($string)
	{
		global $me;
		echo "[".$me."][<--] ".$string."\n";
	}
}

/* WHOIS */
hook::func("raw", function($u)
{
	
	global $serv,$cf;
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "WHOIS")
	{ 
		return;
	}

	if (!($nick = new User(mb_substr($parv[0],1))))
	{
		return;
	}
	if ($parv[2] == $cf['sid'])
	{
		$user = mb_substr($parv[3],1);
		$whois = new User($user);
		if (!$whois->IsUser)
		{
			$serv->Send("401 $nick->nick $user :No such nick/channel");
			$serv->Send("318 $nick->nick $user :End of /WHOIS list.");
			return;
		}
		$serv->Send("311 $nick->nick $whois->nick $whois->ident $whois->cloak * :");
		
		if (strpos($nick->usermode,"o"))
		{
			
			$serv->Send("379 $nick->nick $whois->nick :is using modes $whois->usermode");
			$serv->Send("378 $nick->nick $whois->nick :is connecting from *@$whois->realhost");
		}
		if (strpos($whois->usermode,"r"))
		{
			
			$serv->Send("307 $nick->nick $whois->nick :is identified for this nick (+r)");
		}
		if (strpos($whois->usermode,"z"))
		{
			
			$serv->Send("671 $nick->nick $whois->nick :is using a Secure Connection (+z)");
		}
		if ($whois->account)
		{
			
			$serv->Send("330 $nick->nick $whois->nick $whois->account :is logged in as");
		}
		
		$serv->Send("318 $nick->nick $whois->nick :End of /WHOIS list.");
	}
});


/* MOTD */
hook::func("raw", function($u)
{
	
	global $serv,$cf;
	
	$parv = explode(" ",$u['string']);
	
	if ($parv[1] !== "MOTD" || $cf['sid'] !== mb_substr($parv[2],1))
	{
		return;
	}
	
	if (!($nick = new User(mb_substr($parv[0],1)))->IsUser)
	{
		return; 	
	}
	
	$motd = fopen("dalek.motd","r") ?? false;
	if (!$motd){
		$serv->Send("422 $nick->nick :No MOTD found.");
		return;
	}
	$serv->Send("375 $nick->nick :--------oOo------- MOTD from ".$cf['servicesname']." --------oOo-------");
	while(!feof($motd)){
		$serv->Send("372 $nick->nick :".fgets($motd));
	}
	$serv->Send("376 $nick->nick :--------oOo -------        End of MOTD         --------oOo-------");
	return;
});

/* NICK */
hook::func("raw", function($u)
{
	
	$parv = explode(" ",$u['string']);
	
	if ($parv[1] !== "NICK"){ return; }
	
	$uid = mb_substr($parv[0],1);
	update_nick($uid,$parv[2],$parv[3]);

	return;
});

/* QUIT */

hook::func("raw", function($u)
{
	global $sql;
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "QUIT"){ return; }
	$uid = mb_substr($parv[0],1);
	$quitmsg = str_replace("$parv[0] $parv[1] $parv[2] ","",$u['string']);
	
	$nick = new User($uid);
	$nick->exit();
	
	hook::run("quit", array(
		'uid' => $uid,
		'quitmsg' => $quitmsg)
	);
});


hook::func("raw", function($u)
{
	
	$parv = explode(" ",$u['string']);
	if ($parv[0] !== "NETINFO"){ return; }
	
	hook::run("start", array());
	
});


/* SJOIN */
hook::func("raw", function($u)
{
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "SJOIN"){ return; }
	
	$sid = mb_substr($parv[0],1);
	$timestamp = $parv[2];
	$chan = $parv[3];
	$modes = ($parv[4][0] == ":") ? "" : $parv[4];
	
	$tok = explode(" :",$u['string']);
	$topic = $tok[1] ?? "";
	
	hook::run("SJOIN", array(
		"sid" => $sid,
		"timestamp" => $timestamp,
		"channel" => $chan,
		"modes" => $modes,
		"topic" => $topic,
		"full" => $u['string'])
	);
});

/* SID */
hook::func("raw", function($u)
{
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "SID"){ return; }
	
	$us = mb_substr($parv[0],1);
	$servername = $parv[2];
	$hops = $parv[3];
	$sid = $parv[4];
	$description = mb_substr(str_replace($parv[0]." ".$parv[1]." ".$parv[2]." ".$parv[3]." ".$parv[4]." ","",$u['string']),1);
	
	hook::run("SID", array(
		"server" => $servername,
		"hops" => $hops,
		"sid" => $sid,
		"desc" => $description)
	);

});

/* UID */
hook::func("raw", function($u)
{
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "UID")
	{ 
		return;
	}
	
	$sid = mb_substr($parv[0],1);
	$nick = $parv[2];
	$ts = $parv[4];
	$ident = $parv[5];
	$realhost = $parv[6];
	$uid = $parv[7];
	$account = ($parv[8] == "0") ? false : $parv[8];
	$usermodes = $parv[9];
	$cloak = $parv[11];
	$ipb64 = ($parv[12] !== "*") ? $parv[12] : NULL;
	$ip = inet_ntop(base64_decode($ipb64)) ?? "*";
	if (!$ip){ $ip = ""; }
	$tok = explode(":",$u['string']);
	$gecos = $tok[count($tok) - 1];
	
	hook::run("UID", array(
		"sid" => $sid,
		"nick" =>$nick,
		"timestamp" => $ts,
		"ident" => $ident,
		"realhost" => $realhost,
		"uid" => $uid,
		"account" => $account,
		"usermodes" => $usermodes,
		"cloak" => $cloak,
		"ip" => $ip ?? $ipb64,
		"gecos" => $gecos)
	);	
});

