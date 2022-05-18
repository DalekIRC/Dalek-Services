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
<<<<<<< HEAD
=======
global $cf,$sql,$server,$port,$serv,$servertime,$svs,$ns,$cs;
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a

/* Loading the base code :P */

include __DIR__.'/../conf/dalek.conf';
global $cf,$sql,$server,$port,$serv,$servertime;

include "hook.php";
include "language.php";
<<<<<<< HEAD
=======

>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
include "serv.php";
include "sql.php";
include "client.php";
include "user.php";
<<<<<<< HEAD
include "modules/wordpress/wordpress.php";
include "timer.php";
include "channel.php";
=======
include "timer.php";
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
include "cmd.php";
include "module.php";
include "filter.php";
include "misc.php";
<<<<<<< HEAD
include "servcmd.php";
=======
include "dalek.conf";
include "modules/NickServ/nickserv.php";
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
include "BotServ/botserv.php";
include "Global/global.php";
include "HostServ/hostserv.php";
include "MemoServ/memoserv.php";
<<<<<<< HEAD


=======
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
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a

//include "plugins/PATHWEB/uplink.php";
// Server config
$server = $cf['uplink'];
$port = $cf['port'];
$mypass = $cf['serverpassword'];


<<<<<<< HEAD

// SQL config
$sqlip = $cf['sqlip'];
$sqluser = $cf['sqluser'];
$sqlpass = $cf['sqlpass'];
$sqldb = $cf['sqldb'] ?? "3306";
$sqlport = $cf['sqlport'];

/* Okay, we've established all the information lmao, let's load the modules */

include __DIR__.'/../conf/modules.conf';

=======
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
start:

for (;;)
{
	
	if (!isset($sql))
	{ 
<<<<<<< HEAD
		$sql = new SQL($sqlip,$sqluser,$sqlpass,$sqldb,$sqlport); hook::run("preconnect", array());
=======
		$sql = new SQL(); hook::run("preconnect", array());
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	}
	
	if (!isset($serv)){ $serv = new Server($server,$port,$mypass);
	}
	if (!$socket)
		die();
<<<<<<< HEAD

	stream_set_blocking($socket,0);
	stream_set_timeout($socket,0);
	while ($input = stream_get_line($socket, 0, "\n"))
=======
	while ($input = stream_get_line($socket, 8678, "\n"))
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	{
		
		$timeget = microtime(true);	
		$timetok = explode(".",$timeget);
		if ($servertime != $timetok)
			$servertime = $timetok[0];
		
		if (!$socket)
			die();
	
		log_to_disk($input);
		if ($cf['debugmode'] == "on")
			echo "[\e[0;30;47mRECV\e[0m] ".$input."\n";
	
		flush();
		
		$strippem = utf8_encode(ircstrip(str_replace('\n','',str_replace('\r','',$input))));
		$splittem = explode(' ',$strippem);
		
		// If the server pings us
		if ($splittem[0] == 'PING')
		{
			/* hook into ping lol */
			hook::run("ping", array());
<<<<<<< HEAD
			S2S("PONG ".$splittem[1]); 	// Ping it back
=======
			$serv->sendraw("PONG ".$splittem[1]); 	// Ping it back
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
				if (IsConnected())
				{
					$serv->hear("Connection issue. Trying again in 30 seconds");
					sleep(30);
					goto start;
				}
				else
				{
					die($serv->hear("Connection issue. Please check dalek.conf"));					
				}
				
=======
				$serv->hear("Hmmmm. It seems there was a problem. Please check dalek.conf");
				die();
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
				global $isconn;
				$isconn = NULL;
				
				$pass = mb_substr($splittem[1],1);
				
				if ($pass !== $cf['serverpassword'])
					die("Passwords do not match.");
				
				hook::run("connect", array());
				$isconn = true;
			}
			$action = $splittem[1];
<<<<<<< HEAD

			/* well, we stopped supporting tags for the while for reasons */
=======
			
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
				hook::run("raw", array('mtags' => $tagmsg, 'string' => $strippem));
			
			
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
                chr(13),
				chr(2),
            ), '', $string);
	return $_ircstrip;
}
function S2S($string) {
	global $serv;
<<<<<<< HEAD
	$serv->sendraw($string);
=======
	$serv->send($string);
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
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
<<<<<<< HEAD
}
=======
}
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
