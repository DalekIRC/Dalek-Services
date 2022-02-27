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

include "serv.php";
include "sql.php";
include "client.php";
include "user.php";
include "timer.php";
include "cmd.php";
include "module.php";
include "filter.php";
include "misc.php";
include "dalek.conf";
include "modules/NickServ/nickserv.php";
include "BotServ/botserv.php";
include "ChanServ/chanserv.php";
include "OperServ/operserv.php";
include "Global/global.php";
include "HostServ/hostserv.php";
include "MemoServ/memoserv.php";
include "wordpress/wordpress.php";
include "channel.php";


loadmodule("umode2");
loadmodule("mode");
loadmodule("protoctl");
loadmodule("tkl");
loadmodule("version");
loadmodule("modules");
loadmodule("away");
loadmodule("whois");
loadmodule("specialwhois");
loadmodule("setname");
loadmodule("sethost");
loadmodule("setident");
loadmodule("chgname");
loadmodule("chghost");
loadmodule("chgident");
loadmodule("motd");
loadmodule("nick");
loadmodule("squit");
loadmodule("quit");
loadmodule("topic");
loadmodule("sjoin");
loadmodule("part");
loadmodule("md");
loadmodule("uid");
loadmodule("third/elmer");

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

start:

for (;;)
{
	
	if (!isset($sql))
	{ 
		$sql = new SQL($sqlip,$sqluser,$sqlpass,$sqldb); hook::run("preconnect", array());
	}
	
	if (!isset($serv)){ $serv = new Server($server,$port,$mypass);
	}
	if (!$socket)
		die();
	while ($input = stream_get_line($socket, 8678, "\n"))
	{
		$timeget = microtime(true);	
		$timetok = explode(".",$timeget);
		if ($servertime != $timetok)
			$servertime = $timetok[0];
		
		if (!$socket)
			die();
	
		if ($cf['debugmode'] == "on")
			echo $input."\n";
	
		flush();
		
		$strippem = ircstrip(str_replace('\n','',str_replace('\r','',$input)));
		$splittem = explode(' ',$strippem);
		
		// If the server pings us
		if ($splittem[0] == 'PING')
		{
			/* hook into ping lol */
			hook::run("ping", array());
			$serv->sendraw("PONG ".$splittem[1]); 	// Ping it back
		}
		elseif ($splittem[0] == 'ERROR')
		{
			
			if (strpos($input,'Throttled') !== false)
			{
				$serv->hear("Uh-oh, we've been throttled! Waiting 40 seconds and starting again.");
				sleep(40);
				$serv->shout("Reconnecting...");
				goto start;
			}
			elseif (strpos($input,'Timeout') !== false)
			{
				$serv->hear("Hmmmm. It seems there was a problem. Please check dalek.conf");
				die();
			}
			else
			{
				$serv->hear("Unknown exit issue! Waiting 40 seconds and restarting");
				sleep(40);
				goto start;
			}
		}
		else
		{
			
			$tagmsg = NULL;
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
				hook::run("raw", array('string' => $strippem));
			
			
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
function S2S($string) {
	global $serv;
	$serv->send($string);
}

function color($c,$string)
{
	return  chr(3).$c.$string.chr(3);
}


function clean_align($str)
{
	$len = strlen($str);
	$rem = 20 - $len;
	$whitespace = whitespace($rem);
	
	return "$str"."$whitespace";
}
function whitespace(int $n)
{
	if ($n < 1)
		return "";
	
	$return = "";
	
	for ($i = 1; $i <= $n; $i++)
		$return .= " ";
	
	return $return;
}