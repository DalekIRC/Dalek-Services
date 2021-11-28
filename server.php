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
\\	Title:		Server
//				
\\	Desc:		Parses raw server information and sends
//				it along to their hooks.
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/
global $cf,$sql,$sqlip,$sqluser,$sqlpass,$sqldb,$server,$port,$serv,$servertime,$svs,$ns,$cs;

include "hook.php";
include "dalek.conf";
include "language.php";
include "protocol/".$cf['proto'].".php";
include "sql.php";
include "client.php";
include "user.php";
include "timer.php";
include "squit.php";

include "NickServ/nickserv.php";
include "BotServ/botserv.php";
include "ChanServ/chanserv.php";
include "OperServ/operserv.php";
include "Global/global.php";
include "HostServ/hostserv.php";
include "MemoServ/memoserv.php";
//include "plugins/PATHWEB/uplink.php";
// Server config
$server = $cf['uplink'];
$port = $cf['port'];
$mypass = $cf['serverpassword'];



// SQL config
$sqlip = $cf['sqlip'];
$sqluser = $cf['sqluser'];
$sqlpass = $cf['sqlpass'];
$sqldb = $cf['sqldb'];

timer_add(30,"ping");

function ourtime()
{
	$timeget = microtime(true);	
	$timetok = explode(".",$timeget);
	return $timetok[0];
}
init_ping();
for (;;)
{
	start:
	if (!isset($sql))
	{ 
		$sql = new SQL($sqlip,$sqluser,$sqlpass,$sqldb); hook::run("preconnect", array());
	}
	
	if (!isset($serv)){ $serv = new Server($server,$port,$mypass); }
	for ($servertime = ourtime(); track_time(ourtime()); $servertime = ourtime())
	{
		flush();
		stream_set_blocking($socket,0);
		if(!($input = fgets($socket, 2048)))
			continue;
		if (!$socket)
			die();
	
		if ($cf['debugmode'] == "on")
			echo $input."\n";
		
		$strippem = ircstrip(str_replace('\n','\\n',str_replace('\r','\\r',$input)));
		$splittem = explode(' ',$strippem);

		if (!isset($splittem[0]))
			return;
		
		// If the server pings us
		if ($splittem[0] == 'PING')
			$serv->sendraw("PONG ".$splittem[1]); 	// Ping it back
		
		elseif ($splittem[0] == 'ERROR')
		{
			$serv = NULL;
			$sql = NULL;
			if (strpos($input,'Throttled') !== false)
			{
				printf("Uh-oh, we've been throttled! Waiting 5 seconds and starting again.");
				sleep(5);
				printf("Reconnecting...");
				goto start;
			}
			elseif (strpos($input,'Timeout') !== false)
			{
				printf("Hmmmm. It seems there was a problem. Please check dalek.conf");
				die();
			}
			elseif (strpos($input,'brb lmoa') !== false)
			{
				printf("Looks like we've been asked to restart! Lets go! Pewpewpew!");
				goto start;
			}
			else
			{
				printf("Uh-oh, we've been throttled! Waiting 5 seconds and starting again.");
				sleep(5);
				printf("Reconnecting...");
				goto start;
			}
		}
		else
		{
			
			$tagmsg = NULL;
			if (isset($splittem[0]) == false)
				continue;
			if ($splittem[0][0] == '@')
			{
				$tagmsg = $splittem[0];
				$strippem = ltrim(str_replace($tagmsg,"",$strippem)," ");
				$splittem = explode(" ",$strippem);
			}
			if ($splittem[0] == "PASS")
			{
				
				$pass = mb_substr($splittem[1],1);
				
				if ($pass !== $cf['serverpassword'])
					die("Passwords do not match.");
				
				hook::run("connect", array());
			}
			$action = $splittem[1];
			
			if ($action == "PRIVMSG")
			{
				
				$nick = mb_substr($splittem[0],1);
				$dest = $splittem[2];
				$string = mb_substr(str_replace(":$nick PRIVMSG $dest ","",$strippem),1);
				$token = explode(" ",$string);
				$string = str_replace($token[0],strtolower($token[0]),$string);
				hook::run("privmsg", array(
					"nick" => $nick,
					"dest" => $dest,
					"parv" => $string,
					"mtags" => $tagmsg)
				);
				update_last($nick);
			}
			if ($action == "TAGMSG")
			{

				$nick = mb_substr($splittem[0],1);
				$dest = $splittem[2];
				hook::run("tagmsg", array(
					"nick" => $nick,
					"dest" => $dest,
					"mtags" => $tagmsg)
				);
			}
			else
				hook::run("raw", array('string' => $strippem, 'tagmsg' => $tagmsg));
			
			
		}
	}
}

function get_string_between($string,$start, $end)
{
	$string = ' ' . $string;
	$ini = strpos($string, $start);
	if ($ini == 0) return '';
	$ini += strlen($start);
	$len = strpos($string, $end, $ini) - $ini;
	return substr($string, $ini, $len);
}

function ircstrip($string)
{

	$_ircstrip = str_replace(array(
                chr(10),
                chr(13)
            ), '', $string);
	return $_ircstrip;
}



function init_ping()
{
	global $response,$ns;
	rtimer_add(120,"init_ping()");
	timer_add(120,"PING :international.shitposting.network");
}

function colour($c,$string)
{
	return  chr(3).$c.$string.chr(3);
}
