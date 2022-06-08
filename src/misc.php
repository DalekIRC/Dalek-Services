<?php

/* Some defines */
define( "LOG_WARN","[07WARN] ");
define( "LOG_FATAL","[04FATAL] ");
define( "CHAN_CONTEXT", "+draft/channel-context");

$tok = explode("/",__DIR__);
$n = sizeof($tok) - 1;
$tok[$n] = NULL;
unset($tok[$n]);
$ddir = implode("/",$tok);

define( "__DALEK__", $ddir);


function servertime()
{
	$timeget = microtime(true);	
	$timetok = explode(".",$timeget);
	return $timetok[0];
}
function IsMe($srv)
{
	global $cf;
	$serv = new User($srv);
	if (!$serv->IsServer || (strcmp($serv->uid,$cf['sid']) && strcmp($serv->nick,$cf['servicesname'])))
		return false;
	return true;
}

function MeatballFactory(Channel $chan,$modes,$params,$source)
{
	for ($i = 0; isset($modes[$i]); $i++)
	{
		$chr = $modes[$i];
		
		if ($chr == "+" || $chr == "-")
		{
			$switch = $chr;
			continue;
		}
		$type = cmode_type($chr);
		if ($type == 1 || $type == 2 || $type == 5)
		{
			$par = explode(" ",$params);
			$chan->ProcessMode("$switch $chr ".$par[0],$source);
			$params = rparv($params);
			continue;
		}
		elseif ($type == 3)
		{
			$par = explode(" ",$params);
			
			if ($switch == "+")
			{
				$chan->ProcessMode("$switch $chr ".$par[0],$source);
				$params = rparv($params);
			}
			elseif ($switch == "-")
				$chan->ProcessMode("$switch $chr",$source);
			
			continue;
		}
		elseif ($type == 4)
		{
			$chan->ProcessMode("$switch $chr",$source);				
			continue;
		}
	}
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

function rparv($string)
{
	$parv = explode(" ",$string);
	$first = strlen($parv[0]) + 1;
	$string = substr($string, $first);
	if ($string)
		return $string;
	return false;
}

function global_notice($msg) 
{
	global $gb;
	$gb->notice("$*",$msg);
	return true;
}
function IsConnected(){
	global $isconn;
	if (!isset($isconn) || !$isconn)
		return false;
	return true;
}

function IsUser(User $nick)
{
    return $nick->IsUser;
}

function IsOper(User $nick)
{
    if (strpos($nick->usermode,"o") !== false)
        return true;
    return false;
}

function IsServiceBot(User $nick)
{
	if (strpos($nick->usermode,"S") !== false)
		return true;
	return false;
}

function IsBot(User $nick)
{
	if (strpos($nick->usermode,"B") !== false)
		return true;
	return false;
}

function IsSecure(User $nick)
{
	if (strpos($nick->usermode,"z") !== false)
		return true;
	return false;
}

function IsWebUser(User $nick)
{
	return isset($user->meta->webirc);
}

function IsLoggedIn(User $nick)
{
	if (isset($nick->account) && $nick->account && strlen($nick->account))
		return true;
	return false;
}

function MyUser(User $nick)
{
	return $nick->IsClient;
}

function IsServer(User $nick)
{
	return $nick->IsServer;
}

function bold($s)
{
	return chr(2).$s.chr(2);
}

function ul($s)
{
	return chr(29).$s.chr(29);
}

/* 10th May 2022
 * 
 * Additions:
 * 1) $type param, uses "" as default
 *   so that we don't break anything lmao
 * 
 * 2) logging to disk
 */
function SVSLog($string, $type = "") : void
{
	/* affix a type */
	global $cf,$serv;
	$string = $type.$string;

	/* If we have OperServ, use that */
	if (!($client = Client::find("OperServ")))
	{
		if (!empty(Client::$list)) /* If not, just grab the first available client we can find... */
			$client = Client::$list[0];
		else $client = NULL;
	}
	if ($client)
		$client->log($string);

	elseif (isset($serv)) // if nobody connected yet, fkn log using the server!!
	{
		S2S("PRIVMSG ".$cf['logchan']." :".$string);
	}
	log_to_disk($string);
}

/* Logs to disk =] */
function log_to_disk($str) : void
{
	if (!is_dir(__DALEK__."/logs/"))
		mkdir(__DALEK__."/logs/");
	
	$lfile = __DALEK__."/logs/dalek.".date("d-m-Y").".log";
	$logfile = fopen($lfile, "a") or die("Unable to log to disk. Please check directory permissions.");
	fwrite($logfile,$str."\n");
	fclose($logfile);
}



/* check invitation credentials */

function is_invite($one, $two) : bool
{
	global $servertime;

	$return = false;

	$conn = sqlnew();

	/* quickly clear up any expired invitations (12hrs) */

	$exptime = $servertime - 43200;
	$result  = $conn->query("DELETE FROM dalek_invite WHERE realtime < $exptime");
	/* check their credentials */ 
	$prep = $conn->prepare("SELECT * FROM dalek_invite WHERE timestamp = ?");
	$prep->bind_param("s",$one);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || $result->num_rows == 0)
	{
		$prep->close();
		return false;
	}
	while ($row = $result->fetch_assoc())
		if ($row['code'] == $two)
			$return = true;

	if ($return)
	{
		$prep = $conn->prepare("DELETE FROM dalek_invite WHERE code = ?");
		$prep->bind_param("s",$two);
		$prep->execute();
	}	
	$prep->close();
	return $return;
}




/* actually generate the invite code */
function already_invited($invitee)
{
	$conn = sqlnew();
	$ts = $invitee;
	$prep = $conn->prepare("SELECT * FROM dalek_invite WHERE timestamp = ?");
	$prep->bind_param("s",$invitee);
	$prep->execute();
	$result = $prep->get_result();
	echo $result->num_rows."\n\n";
	if ($result->num_rows == 0)
		return false;
	return true;
}

function generate_invite_code($invitee)
{
	global $servertime;
	$conn = sqlnew();

	$ts = $invitee;
	
	$invite = "";

	/* generate some random ascii 40 chars long */
	for ($i = 0; strlen($invite) !== 40; $i++)
	{
		$r = rand(32,126);
		$invite .= chr($r);
	}
	
	/* hash it in, lets say, sha512 */
	$hash = hash("sha512",$invite);

	/* put to table */
	$prep = $conn->prepare("INSERT INTO dalek_invite (code,timestamp,realtime) VALUES (?,?,?)");
	$prep->bind_param("ssi",$hash,$ts,$servertime);
	$prep->execute();

	return $ts.":".$hash;
}

function list_users_by_account($account)
{
	if (!$account)
		return [];
	else $account = strtoupper($account);
	$users = [];
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT UID FROM dalek_user WHERE UPPER(account) = ?");
	$prep->bind_param("s",$account);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || !$result->num_rows)
		return false;

	while($row = $result->fetch_assoc())
		$users[] = new User($row['UID']);

	$prep->close();
	return $users;
}

function split($str) : array
{
	return explode(" ",str);
}