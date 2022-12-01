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
	public $sid, $name;
	function __construct($server,$port,$password)
	{
				
		// INITIALISING CONNECT SEQUENCE lmao
		$this->connect($server,$port,$password);
	
	}
	private function connect($server,$port,$password)
	{
		
		// Declare de globals;
		global $socket;
		
		// Anything we wanna initialise before we connect
		
		$this->sid = Conf::$settings['info']['SID'];
		$this->name = Conf::$settings['info']['services-name'];
		/* pre connect shit */
		
		// we are disabling verification for now until built upon more :>
		// create ssl context
		$context = stream_context_create(['ssl' => [
			'verify_peer'  => true,
			'verify_peer_name'  => true,
			'allow_self_signed' => true,
			'ciphers' => 'ECDHE-ECDSA-AES256-GCM-SHA384'
		]]);

		//opening socket YO
		$socket = stream_socket_client($server.':'.$port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
		
		
		
		
		$this->sendraw("PASS $password");
		$this->sendraw("PROTOCTL EAUTH=$this->name,6000 SID=$this->sid");
		$this->sendraw("PROTOCTL NOQUIT NICKv2 SJOIN SJOIN2 SJ3 CLK TKLEXT2 NICKIP ESVID MLOCK NEXTBANS EXTSWHOIS SJSBY MTAGS");
		$this->sendraw("SERVER $this->name 1 :Dalek IRC Services");
		$this->sendraw("MD client $this->sid saslmechlist :PLAIN,EXTERNAL");
		$this->sendraw("MD client $this->sid externalreglink :https://valware.uk/register");
		hook::run(HOOKTYPE_BURST, $this);
		//$this->sendraw("MD client $this->sid regkeylist :before-connect,email-required,custom-account-name");
		$this->sendraw("EOS");
		

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
		
		if ($string[0] !== "@") // if there are no mtags on it
		{
			$new_mtags = array_to_mtag(generate_new_mtags());
			strprefix($string, $new_mtags);
		}

		if (Conf::$settings['log']['debug'] == "yes")
			echo "[\e[0;30;42mSEND\e[0m] $string\n";
			
		fputs($socket, ircstrip($string)."\n");
		
	}
	function svskill($uid,$string)
	{
		$this->sendraw("SVSKILL $uid :$string");
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

hook::func(HOOKTYPE_RAW, function($u)
{
	if (Conf::$settings['log']['debug'] == "yes")
	$us = Conf::$settings['info']['SID'];
	$parv = explode(" ",$u['string']);
	if ($parv[0] !== "NETINFO"){ return; }
	$array = array(
		"server" => Conf::$settings['info']['services-name'],
		"hops" => "0",
		"sid" => Conf::$settings['info']['SID'],
		"desc" => Conf::$settings['info']['network-name'],
		"intro_by" => $us);
	hook::run(HOOKTYPE_SERVER_CONNECT, $array);
	$var = [];
	hook::run(HOOKTYPE_START, $var);
	
});


/* SID */
hook::func(HOOKTYPE_RAW, function($u)
{
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "SID")
		return;
	$us = mb_substr($parv[0],1);
	if (!$us)
		$us = Conf::$settings['info']['SID'];
	$servername = $parv[2];
	$hops = $parv[3];
	$sid = $parv[4];
	$description = mb_substr($u['string'],strlen($parv[0]." ".$parv[1]." ".$parv[2]." ".$parv[3]." ".$parv[4]." ") + 1);
	$array = array(
		"server" => $servername,
		"hops" => $hops,
		"sid" => $sid,
		"desc" => $description,
		"intro_by" => $us);
	hook::run("SID", $array);
});

