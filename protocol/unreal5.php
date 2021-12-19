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
		$this->sendraw("PROTOCTL NOQUIT NICKv2 SJOIN SJOIN2 SJ3 CLK TKLEXT2 NICKIP ESVID MLOCK NEXTBANS EXTSWHOIS SJSBY MTAGS");
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


/* UMODE2 */
hook::func("raw", function($u)
{
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "UMODE2")
	{ 
		return;
	}
	$user = new User(mb_substr($parv[0],1));
	if (!$user->IsUser)
	{
		return;
	}
	
	$user->SetMode($parv[2]);
});


/* WHOIS (server info) */
hook::func("raw", function($u)
{
	
	global $_LINK;
	
	$parv = explode(" ",$u['string']);
	if ($parv[0] !== "PROTOCTL")
	{ 
		return;
	}
	for ($i = 1; isset($parv[$i]); $i++)
	{
		$tok = explode("=",$parv[$i]);
		if ($tok[0] == "SID")
		{
			$_LINK = $tok[1];
		}
	}
});
hook::func("raw", function($u)
{
	
	global $_LINK,$sql;
	
	$parv = explode(" ",$u['string']);
	if ($parv[0] !== "SERVER")
	{ 
		return;
	}
	$sid = $_LINK;
	$_LINK = NULL;
	$name = $parv[1];
	$hops = $parv[2];
	$desc = str_replace("$parv[0] $parv[1] $parv[2] $parv[3] ","",$u['string']);
	
	$sql::sid(array('server' => $name,'hops' => $hops,'sid' => $sid,'desc' => $desc));
});


/* WHOIS */
hook::func("raw", function($u)
{
	
	global $serv,$cf,$servertime;
	
	$parv = explode(" ",$u['string']);
	if (!isset($parv[1]))
		return;
	if ($parv[1] !== "WHOIS")
	{ 
		return;
	}

	if (!($nick = new User(mb_substr($parv[0],1))))
	{
		return;
	}
	else
	{
		$user = $parv[3];
		$whois = new User($user);
		if (!$whois->IsUser)
		{
			$serv->Send("401 $nick->nick $user :No such nick/channel");
			$serv->Send("318 $nick->nick $user :End of /WHOIS list.");
			return;
		}
		$hostmask = (strpos($whois->usermode,"x")) ? $whois->cloak : $whois->realhost;
		$serv->Send("311 $nick->nick $whois->nick $whois->ident $hostmask * :$whois->gecos");
		
		if (strpos($nick->usermode,"o") !== false || $nick->uid == $whois->uid)
		{
			
			$serv->Send("379 $nick->nick $whois->nick :is using modes $whois->usermode");
			$serv->Send("378 $nick->nick $whois->nick :is connecting from *@$whois->realhost $whois->ip");
		}
		if (strpos($whois->usermode,"r"))
		{
			
			$serv->Send("307 $nick->nick $whois->nick :is identified for this nick (+r)");
		}
		$chanlist = get_ison($whois->uid);
		$full_list = NULL;
		for ($p = 0; isset($chanlist['list'][$p]); $p++)
		{
			
			$secret = NULL;
			$chanmode = NULL;
			$chan = find_channel($chanlist['list'][$p]);
			
			if (strpos($chan['modes'],"s") || strpos($chan['modes'],"p"))
			{
				$secret = true;
			}
			else
			{
				$secret = false;
			}
			if ($chanlist['mode'])
			{
				
				$char = $chanlist['mode'][$p];
				
				if ($char == "q")
				{
					$chanmode .= "~";
				}
				elseif ($char == "a")
				{
					$chanmode .= "&";
				}
				elseif ($char == "o")
				{
					$chanmode .= "@";
				}
				elseif ($char == "h")
				{
					$chanmode .= "%";
				}
				elseif ($char == "v")
				{
					$chanmode .= "+";
				}
			
			}
			$sec = ($secret) ? "!" : "";
			if ($secret && (strpos($nick->usermode,"o") || $whois->uid == $nick->uid))
			{
				$full_list .= $sec.$chanmode.$chanlist['list'][$p]." ";
			}
			if (!$secret)
			{
				$full_list .= $chanmode.$chanlist['list'][$p]." ";
			}
		}
		$serv->Send("319 $nick->nick $whois->nick :$full_list");
		
		$sv = find_serv($whois->sid);
		$serv->Send("312 $nick->nick $whois->nick ".$sv['servername']." :".$sv['version']);
		
		if (strpos($whois->usermode,"o"))
		{
			$serv->Send("313 $nick->nick $whois->nick :is an IRC Operator (+o)");
		}
			
		if (strpos($whois->usermode,"z"))
		{
			
			$serv->Send("671 $nick->nick $whois->nick :is using a Secure Connection (+z)");
		}
		if ($whois->account)
		{
			
			$serv->Send("330 $nick->nick $whois->nick $whois->account :is logged in as");
		}
		if ($swhois = GetSWhois($whois->uid))
		{
			foreach ($swhois['swhois'] as $whoistok)
			{
				$serv->Send("320 $nick->nick $whois->nick :$whoistok ");
			}
		}
		$idle = ($servertime - $whois->last);
		if (!strpos($whois->usermode,"I"))
			$serv->Send("317 $nick->nick $whois->nick $idle $whois->ts :seconds idle, signon time");

		$serv->Send("318 $nick->nick $whois->nick :End of /WHOIS list.");

		if (strpos($whois->usermode,"W"))
			$serv->Send("NOTICE $whois->nick :$nick->nick did a /WHOIS on you.");
	}
});

function version_response(User $user)
{
	global $serv,$cf;
	$user_agent = php_uname();
	$serv->sendraw("351 $user->nick Dalek-Services ".$cf['servicesname']." 0 [".$user_agent."]");
}

function module_response(User $user, $string)
{
	global $serv;
	$serv->sendraw("304 $user->nick :$string");
}

/* CHGCMDS */
hook::func("raw", function($u)
{
	$parv = explode(" ",$u['string']);
	if ($parv[1] == "CHGHOST")
	{
		$new = str_replace($parv[0]." ".$parv[1]." ".$parv[2]." ","",$u['string']);
		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE dalek_user SET cloak = ? WHERE UID = ?");
		$prep->bind_param("ss",$new,$parv[2]);
		$prep->execute();
		return;
	}
	elseif ($parv[1] == "CHGIDENT")
	{
		$new = str_replace($parv[0]." ".$parv[1]." ".$parv[2]." ","",$u['string']);
		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE dalek_user SET ident = ? WHERE UID = ?");
		$prep->bind_param("ss",$new,$parv[2]);
		$prep->execute();
		return;
	}
	elseif ($parv[1] == "CHGNAME")
	{
		$new = str_replace($parv[0]." ".$parv[1]." ".$parv[2]." ","",$u['string']);
		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE dalek_user SET gecos = ? WHERE UID = ?");
		$prep->bind_param("ss",$new,$parv[2]);
		$prep->execute();
		return;
	}
});

/* SETCMDS */
hook::func("raw", function($u)
{
	$parv = explode(" ",$u['string']);
	if ($parv[1] == "SETHOST")
	{
		$new = str_replace($parv[0]." ".$parv[1]." ","",$u['string']);
		$nick = mb_substr($parv[0],1);
		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE dalek_user SET cloak = ? WHERE UID = ?");
		$prep->bind_param("ss",$new,$nick);
		$prep->execute();
		return;
	}
	elseif ($parv[1] == "SETIDENT")
	{
		$new = str_replace($parv[0]." ".$parv[1]." ","",$u['string']);
		$nick = mb_substr($parv[0],1);
		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE dalek_user SET ident = ? WHERE UID = ?");
		$prep->bind_param("ss",$new,$nick);
		$prep->execute();
		return;
	}
	elseif ($parv[1] == "SETNAME")
	{
		$new = str_replace($parv[0]." ".$parv[1]." ","",$u['string']);
		$nick = mb_substr($parv[0],1);
		$conn = sqlnew();
		$prep = $conn->prepare("UPDATE dalek_user SET gecos = ? WHERE UID = ?");
		$prep->bind_param("ss",$new,$nick);
		$prep->execute();
		return;
	}
});
	
/* MODULE */
hook::func("raw", function($u)
{
	global $cf;
	$parv = explode(" ",$u['string']);
	
	if ($parv[1] !== "MODULE")
	{ 
		return;
	}
	$user = new User(mb_substr($parv[0],1));
	if (!$user->IsUser)
		return;

	module_response($user,"Listing modules for ".$cf['servicesname']);
	foreach (get_included_files() as $module)
		if (mb_substr($module,-4) == ".php")
			module_response($user,"*** ".str_replace(getcwd()."/","",$module));
});



/* VERSION */
hook::func("raw", function($u)
{
	$parv = explode(" ",$u['string']);
	
	if ($parv[1] !== "VERSION")
	{ 
		return;
	}
	$user = new User(mb_substr($parv[0],1));
	if (!$user->IsUser)
		return;
	version_response($user);
});

/* SWHOIS */
hook::func("raw", function($u)
{
	global $ns;
	$parv = explode(" ",$u['string']);
	
	if ($parv[1] !== "SWHOIS")
	{ 
		return;
	}
	/*
	$user = new User($parv[2]);
	if (!isset($user->uid))
		return;
		*/
	$username = $parv[2];
	$switch = $parv[3];
	$tag = $parv[4];
	$priority = $parv[5];
	$whois = str_replace("$parv[0] $parv[1] $parv[2] $switch $tag $priority :","",$u['string']);
	
	SWHOIS("$username $switch $tag $priority $whois");
});

function SetSWhois($uid,$swhois)
{
	global $serv;

	$cmd = "SWHOIS $uid + services -500 :$swhois";
	SWHOIS($cmd);
	$serv->Send($cmd);
}

/* GetSwhois command (lookup) */
function GetSWhois($uid)
{
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	$user = new User($uid);
	if (!$user->IsUser)
	{
		return;
	}
	
	if (!$conn) { return false; }
	else
	{
		$prep = $conn->prepare("SELECT * FROM dalek_swhois WHERE uid = ? ORDER BY priority DESC");
		$prep->bind_param("s", $uid);
		$prep->execute();
		$result = $prep->get_result();
		
		if ($result->num_rows == 0){ return false; }
		else
		{
			$swhois = array();
			$tag = array();
			
			while($row = $result->fetch_assoc())
			{
				$swhois[] = $row['swhois'];
				$tag[] = $row['tag'];
			}
		}
	}
	$return = array('swhois' => $swhois, 'tag' => $tag);
	$prep->close();;
	return $return;
}



/*	SWHOIS command (incoming)
	$parv[1] = UID,
	$parv[2] = +/-,
	$parv[3] = tag,
	$parv[4] = priority,
	$parv[5] = swhois
*/
function SWHOIS($string){
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	$parv = explode(" ",$string);
	
	$user = $parv[0];
	$switch = $parv[1];
	$tag = $parv[2];
	$priority = $parv[3];
	$whois = str_replace("$user $switch $tag $priority ","",$string);
	
	
	if ($switch == "+")
	{
		if (!$conn) { return false; }
		else
		{
			$prep = $conn->prepare("INSERT INTO dalek_swhois (tag, uid, priority, swhois) VALUES (?, ?, ?, ?)");
			$prep->bind_param("ssss",$tag,$user,$priority,$whois);
			$prep->execute();
			$prep->close();
		}
		
	}
	if ($switch == "-")
	{
		if (!$conn){ return false; }
		else
		{
			if ($whois == "*")
			{
				$prep = $conn->prepare("DELETE FROM dalek_swhois WHERE uid = ? AND tag = ?");
				$prep->bind_param("ss",$user,$tag);
				$prep->execute();
				$prep->close();
			}
			else
			{
				$prep = $conn->prepare("DELETE FROM dalek_swhois WHERE uid = ? AND tag = ? AND swhois = ?");
				$prep->bind_param("sss",$user,$tag,$whois);
				$prep->execute();
				$prep->close();
			}
		}
	}
};

/* MOTD */
hook::func("raw", function($u)
{
	
	global $serv,$cf;
	
	$parv = explode(" ",$u['string']);
	
	if ($parv[1] !== "MOTD")
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
		$fg = fgets($motd);
		if (empty($fg))
			$serv->Send("372 $nick->nick :- ");
		else
			$serv->Send("372 $nick->nick :- $fg");
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

/* SQUIT */

hook::func("raw", function($u)
{
	global $sql;
	
	$parv = explode(" ",$u['string']);
	if ($parv[0] !== "SQUIT")
		return;
	$s = find_serv($parv[1]);
	hook::run("squit", array('sid' => $s['sid']));
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
	global $fsync;
	$parv = explode(" ",$u['string']);
	if ($parv[0] !== "NETINFO"){ return; }
	$fsync = true;
	hook::run("start", array());
	
});


/* MD (meta data) */

hook::func("raw", function($u)
{
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "MD")
		return;
	if ($parv[2] == "client")
	{
		$user = $parv[3];
		$key = $parv[4];
		$value = mb_substr(str_replace($parv[0]." ".$parv[1]." ".$parv[2]." ".$parv[3]." ".$parv[4]." ","",$u['string']),1);
		md_add($user,$key,$value);
	}
	/* to do: channel meta and server meta */
});

function md_add($person,$key,$value)
{
        $conn = sqlnew();
        if (!$conn) { return false; }
        
        else {
                $prep = $conn->prepare("INSERT INTO dalek_user_meta (UID, meta_key, meta_data) VALUES (?, ?, ?)");
	$prep->bind_param("sss",$person,$key,$value);
	$prep->execute();
                $prep->close();
        }
}

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
	

/* PART */
hook::func("raw", function($u)
{
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "PART")
		return;
	$nick = new User(mb_substr($parv[0],1));
	$chan = strtolower($parv[2]);
	$conn = sqlnew();
	$prep = $conn->prepare("DELETE FROM dalek_ison WHERE nick = ? AND lower(chan) = ?");
	$prep->bind_param("ss",$nick->uid,$chan);
	$prep->execute();

	/* cleanup any non-permanent empty channel */
	
	$prep = $conn->prepare("SELECT * FROM dalek_ison WHERE lower(chan) = ?");
	$prep->bind_param("s",$chan);
	$prep->execute();
	$result = $prep->get_result();
	
	if ($result->num_rows == 0)
	{
		$lookup = find_channel($chan);
		if (strpos($lookup['modes'],"P") == false)
		{
			$prep = $conn->prepare("DELETE FROM dalek_channels WHERE channel = ?");
			$prep->bind_param("s",$lookup['channel']);
			$prep->execute();
		}
	}
	$prep->close();
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
		

/* UID */
hook::func("raw", function($u)
{
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "UID")
	{ 
		return;
	}
	if (count($parv) < 4)
		return;
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
	$tagmsg = (isset($u['tagmsg'])) ? $u['tagmsg'] : "";
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
		"gecos" => $gecos,
		"tagmsg" => $tagmsg)
	);	
});

hook::func("SJOIN", function($u)
{	
	global $sql;
	
	$tokens = explode(" ",$u['full']);
	$chan = $tokens[3];
	$list = explode(":",$u['full']);
	$parv = explode(" ",$list[count($list) - 1]);
	
	if (!$parv)
	{
		return;
	}
	for ($p = 0; isset($parv[$p]); $p++)
	{
		$mode = "";
		$item = $parv[$p];
		loopback:
		if (!isset($item[0]))
		{
			continue;
		}
		if ($item[0] == "+")
		{
			$mode .= "v";
			$item = mb_substr($item,1);
			goto loopback;
		}
		if ($item[0] == "%")
		{
			$mode .= "h";
			$item = mb_substr($item,1);
			goto loopback;
		}
		if ($item[0] == "@")
		{
			$mode .= "o";
			$item = mb_substr($item,1);
			goto loopback;
		}
		if ($item[0] == "~")
		{
			$mode .= "a";
			$item = mb_substr($item,1);
			goto loopback;
		}
		if ($item[0] == "*")
		{
			$mode .= "q";
			$item = mb_substr($item,1);
			goto loopback;
		}
		
		if (isset($mode))
		{
			$sql::insert_ison($chan,$item,$mode);
		}
		
	}
});
