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

/* Loading the base code :P */

/*
 * Language files. Please uncomment yours!
 * If yours isn't available and you would like to contribute
 * one, please make a pull request via github.
 */
 
include "languages/en_GB";
//include "languages/tr_TR";

include __DIR__.'/../conf/dalek.conf';
global $cf,$sql,$server,$port,$serv,$servertime;
include "misc.php";
include "conf.php";
include "language.php";
include "hook.php";
include "rpc.php";
include "serv.php";
include "sql.php";
include "client.php";
include "user.php";
include "wordpress/wordpress.php";
include "channel.php";
include "cmd.php";
include "module.php";
include "filter.php";
include "servcmd.php";
include "events.php";
include "buffer.php";

//include "plugins/PATHWEB/uplink.php";
// Server config
$server = $cf['uplink'];
$port = $cf['port'];
$mypass = $cf['serverpassword'];

/* Config run */
Conf::run();

// SQL config
$sqlip = $cf['sqlip'];
$sqluser = $cf['sqluser'];
$sqlpass = $cf['sqlpass'];
$sqldb = $cf['sqldb'];
$arr = [];
$sql = new SQL($sqlip,$sqluser,$sqlpass,$sqldb); hook::run("preconnect", $arr);
/* Okay, we've established all the information lmao, let's load the modules */


include __DIR__.'/../conf/modules.conf';

start:
$serv = new Server($server,$port,$mypass);

if (!$socket || !$server)
	die("oops");

stream_set_blocking($socket, 0);

for ($input = Buffer::do_buf(stream_get_line($socket, 0, "\n"));;$input = Buffer::do_buf(stream_get_line($socket, 0, "\n")))
{

	/* Check for new events */
	if ($servertime != servertime())
	{
		Events::CheckForNew();
		$servertime = servertime();
	}
	
	/* Check for RPC Calls */
	rpc_check();
	if (!$socket)
		die();
	if (!$input)
		continue;

	log_to_disk($input);
	if ($cf['debugmode'] == "on")
		echo "[\e[0;30;47mRECV\e[0m] ".$input."\n";
	
	flush();
	//RPC::check();
	$strippem = ircstrip(str_replace('\\','\\\\',$input));
	$splittem = explode(' ',$strippem);
	
	// If the server pings us
	if ($splittem[0] == 'PING')
	{
		/* hook into ping lol */
		$arr = [];
		hook::run("ping", $arr);
		S2S("PONG ".$splittem[1]); 	// Ping it back
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
			
		}
		else
		{
			$serv->hear("Unknown exit issue! Waiting 40 seconds and restarting");
			usleep(400000);
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
			
			$array = [];
			hook::run("connect", $array);
			$isconn = true;
		}
		$action = $splittem[1];

		/* well, we stopped supporting tags for the while for reasons */
		if ($action == "TAGMSG")
		{

			$nick = mb_substr($splittem[0],1);
			$dest = $splittem[2];
			$array = array(
				"nick" => $nick,
				"dest" => $dest,
				"mtags" => $tagmsg);
			hook::run("tagmsg",$array);
			
		
		}
		else
		{
			$array = array('mtags' => $tagmsg, 'string' => $strippem);
			hook::run("raw", $array);
		}
		
	}
}

