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

if ($cf['proto'] == 'unreal5'){ include "protocol/unreal5.php"; }
include "sql.php";
include "client.php";

include "NickServ/nickserv.php";
include "BotServ/botserv.php";
include "ChanServ/chanserv.php";
include "OperServ/operserv.php";
include "Global/global.php";
include "HostServ/hostserv.php";
include "MemoServ/memoserv.php";

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

for (;;){
		
	$timeget = microtime(true);	
	$timetok = explode(".",$timeget);
	$servertime = $timetok[0];
	if (!isset($sql)){ $sql = new SQL($sqlip,$sqluser,$sqlpass,$sqldb); hook::run("preconnect", array()); }
	
	if (!isset($serv)){ $serv = new Server($server,$port,$mypass); }
	while ($input = fgets($socket, 1000)) {
	
		if (!$socket){ die(); }
	
		if ($cf['debugmode'] == "on") { echo $input."\n"; if ($ns){ /*$ns->msg("#dalek",$input);*/ } }
		flush();
		
		$strippem = ircstrip(str_replace('\n','',str_replace('\r','',$input)));
		$splittem = explode(' ',$strippem);
		
		// If the server pings us
		if ($splittem[0] == 'PING') { 
		
			// Ping it back
			$serv->sendraw("PONG ".$splittem[1]);
			
		}
		elseif ($splittem[0] == 'ERROR') {
			
			if (strpos($input,'Throttled') !== false) {
				$serv->hear("Uh-oh, we've been throttled! Waiting 40 seconds and starting again.");
				sleep(40);
				$serv->shout("Reconnecting...");
				goto start;
			}
			elseif (strpos($input,'Timeout') !== false) {
				$serv->hear("Hmmmm. It seems there was a problem. Please check config.conf that 'nick', 'ident' and 'realname' are correct");
				die();
			}
			elseif (strpos($input,'brb lmoa') !== false) {
				$serv->hear("Looks like we've been asked to restart! Lets go! Pewpewpew!");
				goto start;
			}
			else {
				$serv->hear("Unknown exit issue! Restarting");
				goto start;
			}
		}
		else {
			
			$tagmsg = NULL;
			if ($splittem[0][0] == '@'){
				$tagmsg = $splittem[0];
				$strippem = ltrim(str_replace($tagmsg,"",$strippem)," ");
				$splittem = explode(" ",$strippem);
			}
			if ($splittem[0] == "PASS"){
				
				$pass = mb_substr($splittem[1],1);
				
				if ($pass !== $cf['serverpassword']){ die("Passwords do not match."); }
				
				hook::run("connect", array());
			}
			$action = $splittem[1];
			
			if ($action == "PRIVMSG"){
				
				$nick = mb_substr($splittem[0],1);
				$dest = $splittem[2];
				$string = mb_substr(str_replace(":$nick PRIVMSG $dest ","",$strippem),1);
				hook::run("privmsg", array(
					"nick" => $nick,
					"dest" => $dest,
					"parv" => $string,
					"mtags" => $tagmsg)
				);
			}
			if ($action == "TAGMSG"){

				$nick = mb_substr($splittem[0],1);
				$dest = $splittem[2];
				hook::run("tagmsg", array(
					"nick" => $nick,
					"dest" => $dest,
					"mtags" => $tagmsg)
				);
			}
			elseif ($action == "UID"){
				$sid = mb_substr($splittem[0],1);
				$nick = $splittem[2];
				$ts = $splittem[4];
				$ident = $splittem[5];
				$realhost = $splittem[6];
				$uid = $splittem[7];
				$account = ($splittem[8] == "0") ? false : $splittem[8];
				$usermodes = $splittem[9];
				$cloak = $splittem[11];
				$ipb64 = $splittem[12];
				
				$tok = explode(":",$strippem);
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
					"ipb64" => $ipb64,
					"gecos" => $gecos)
				);	
			}
			elseif ($action == "SID"){
				$us = mb_substr($splittem[0],1);
				$servername = $splittem[2];
				$hops = $splittem[3];
				$sid = $splittem[4];
				$description = mb_substr(str_replace($splittem[0]." ".$splittem[1]." ".$splittem[2]." ".$splittem[3]." ".$splittem[4]." ","",$strippem),1);
				
				hook::run("SID", array(
					"server" => $servername,
					"hops" => $hops,
					"sid" => $sid,
					"desc" => $description)
				);
			}
			elseif ($action == "SJOIN"){
				$sid = mb_substr($splittem[0],1);
				$timestamp = $splittem[2];
				$chan = $splittem[3];
				$modes = ($splittem[4][0] == ":") ? "" : $splittem[4];
				
				$tok = explode(" :",$strippem);
				$topic = $tok[1] ?? "";
				
				hook::run("SJOIN", array(
					"sid" => $sid,
					"timestamp" => $timestamp,
					"channel" => $chan,
					"modes" => $modes,
					"topic" => $topic,
					"full" => $strippem)
				);
			}
			elseif ($splittem[0] == "NETINFO"){
			
				$ns->join("#services");
				$cs->join("#services");
				$cs->join("#Valeyard");
				$bs->join("#services");
				$os->join("#services");
				$gb->join("#services");
				$hs->join("#services");
				$ms->join("#services");
				$serv->sendraw("MD client ".$cf['sid']." saslmechlist :PLAIN");
				$gb->notice("$*","Services is back online. Have a great day!");
				hook::run("start", array());
			}
			elseif ($action == "QUIT"){
				
				$quitmessage = str_replace($splittem[0]." ".$splittem[2]." ","",$strippem);
				
				hook::run("quit", array(
					'uid' => mb_substr($splittem[0],1),
					'quitmsg' => $quitmessage)
				);
			}
			elseif ($action == "SASL"){
				nickserv::run("sasl", array(
					'sasl' => $strippem)
				);
			}
			elseif ($action == "NICK"){
				$uid = mb_substr($splittem[0],1);
				update_nick($uid,$splittem[2],$splittem[3]);
			}
		}
	}
}

function get_string_between($string,$start, $end){
			$string = ' ' . $string;
			 $ini = strpos($string, $start);
			 if ($ini == 0) return '';
			 $ini += strlen($start);
			 $len = strpos($string, $end, $ini) - 			$ini;
  			return substr($string, $ini, $len);
}

function ircstrip($string){

	$_ircstrip = str_replace(array(
                chr(10),
                chr(13)
            ), '', $string);
	return $_ircstrip;
}
