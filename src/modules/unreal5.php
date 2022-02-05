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
		$this->sendraw("PROTOCTL EAUTH=".$cf['servicesname'].",6000 SID=".$cf['sid']);
		$this->sendraw("PROTOCTL NOQUIT NICKv2 SJOIN SJOIN2 SJ3 CLK TKLEXT2 NICKIP ESVID MLOCK NEXTBANS EXTSWHOIS SJSBY");
		$this->sendraw("SERVER ".$cf['servicesname']." 1 :Dalek IRC Services");
		$this->sendraw("EOS");
		$this->sendraw("MD client ".$cf['sid']." saslmechlist :PLAIN,EXTERNAL");
		

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
		echo "[SEND] $string\n";
		fputs($socket, ircstrip($string)."\n");
		
	}
	function Send($string)
	{
		$this->sendraw($string);
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
hook::func("raw", function($u)
{
	global $fsync;
	$parv = explode(" ",$u['string']);
	if ($parv[0] !== "NETINFO"){ return; }
	$fsync = true;
	hook::run("start", array());
	
});


/* SID */
hook::func("raw", function($u)
{
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "SID")
		return;
	$us = mb_substr($parv[0],1);
	$servername = $parv[2];
	$hops = $parv[3];
	$sid = $parv[4];
	$description = mb_substr(str_replace($parv[0]." ".$parv[1]." ".$parv[2]." ".$parv[3]." ".$parv[4]." ","",$u['string']),1);
	
	hook::run("SID", array(
		"server" => $servername,
		"hops" => $hops,
		"sid" => $sid,
		"desc" => $description,
		"intro_by" => $us)
	);

});


hook::func("start", function($u)
{
	global $cf;
	hook::run("SID", array(
		"server" => $cf['servicesname'],
		"hops" => "0",
		"sid" => $cf['sid'],
		"desc" => $cf['network'])
	);
});
		


function SendRaw($string)
{
	global $serv;
	$serv->Send($string);
}

function IsServiceBot(User $user)
{
	if (strpos($user->usermode,"S") !== false)
		return true;
	return false;
}



function bie($chan,$item)
{
	$tok = explode(",",get_string_between($item,"<",">"));
			
	$timestamp = $tok[0];
	$setby = $tok[1];
	if (is_numeric($tok[1][0]))
	{
		$usr = new User($setby);
		$setby = $usr->nick;
	}
	$item = mb_substr($item,strlen(get_string_between($item,"<",">")) + 2);
	
	$type = $item[0];
	$ext = mb_substr($item,1);
	
	$conn = sqlnew();
	
	$prep = $conn->prepare("INSERT INTO dalek_channel_meta (chan, meta_key, meta_value, meta_setby, meta_timestamp) VALUES (?, ?, ?, ?, ?)");
	
	switch($type)
	{
		case "&":
			$set = "ban";
			break;
			
		case "'":
			$set = "invite";
			break;
		
		case "\"":
			$set = "except";
			break;
	}
	
	$prep->bind_param("sssss",$chan,$set,$ext,$setby,$timestamp);
	$prep->execute();
	$prep->close();
}
