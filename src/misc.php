<?php

/* Some defines */
define( "LOG_WARN","[07WARN] ");
define( "LOG_FATAL","[04FATAL] ");
define( "LOG_RPC", "[RPC] ");
define( "CHAN_CONTEXT", "+draft/channel-context");
define( "RECYCLED_MESSAGE", "dalek/recycled");

/* Returns unix time */
function servertime()
{
	$timeget = microtime(true);	
	$timetok = explode(".",$timeget);
	return $timetok[0];
}

/* Returns microseconds */
function microsecs()
{
	$timeget = microtime(true);	
	$timetok = explode(".",$timeget);
	if (!isset($timetok[1]))
		$timetok[1] = "00";
	return mb_substr($timetok[1],0,3);
}

/* If the String is our server name or server ID
 *
 * Syntax:
 * IsMe(String $name_or_sid)
 * 
 * Returns:
 * bool
 */
function IsMe($srv)
{
	global $cf;
	$serv = new User($srv);
	if (!$serv->IsServer || (strcmp($serv->uid,$cf['sid']) && strcmp($serv->nick,$cf['servicesname'])))
		return false;
	return true;
}

/* This function goes through channel modes and deals
 * with them appropriately.
 * It's called Meatball Factory because this was complex for me lol
 */
function MeatballFactory(Channel $chan,$modes,$params,$source)
{
	$switch = NULL;
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


/* Function to process bans that are set in channels */
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
	
	$prep = $conn->prepare("INSERT INTO ".sqlprefix()."channel_meta (chan, meta_key, meta_value, meta_setby, meta_timestamp) VALUES (?, ?, ?, ?, ?)");
	
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

/* This function takes a string, tokenizes
 * it by a space (chr 32), removes the first
 * word/token, and returns the result.
 * Mostly used for string manipulation around
 * the source.
 * 
 * Syntax:
 * rparv(String $sentence)
 * 
 * Returns:
 * string|false
 */
function rparv($string)
{
	$parv = explode(" ",$string);
	$first = strlen($parv[0]) + 1;
	$string = substr($string, $first);
	if ($string)
		return $string;
	return false;
}

/* Does a global notice */
function global_notice($msg) 
{
	if (!IsConnected())
		return false;
	$gb = Client::find("Global"); // if we have Global bot
	if ($gb)
		$gb->notice("$*",$msg);
	else
		S2S("NOTICE $* :".$msg);
	return true;
}

/* Checks if we are currently fully connected
 * to an IRC server.
 * 
 * Syntax:
 * IsConnected()
 * 
 * Returns:
 * bool
 */
function IsConnected(){
	global $isconn;
	if (!isset($isconn) || !$isconn)
		return false;
	return true;
}

/* Checks if the User object is connecting to
 * IRC as a normal user.
 *
 * Syntax:
 * IsUser($user_object)
 * 
 * Returns:
 * bool
 */
function IsUser(User $nick)
{
    return $nick->IsUser;
}

/* Checks if the User object is an oper.
 *
 * Syntax:
 * IsOper($user_object)
 * 
 * Returns:
 * bool
 */
function IsOper(User $nick)
{
    if (strpos($nick->usermode,"o") !== false)
        return true;
    return false;
}

/* Checks if the User object is connecting to
 * IRC using Services Mode (+S).
 *
 * Syntax:
 * IsServiceBot($user_object)
 * 
 * Returns:
 * bool
 */
function IsServiceBot(User $nick)
{
	if (strpos($nick->usermode,"S") !== false)
		return true;
	return false;
}

/* Checks if the User object is connecting to
 * IRC as a Bot.
 *
 * Syntax:
 * IsBot($user_object)
 * 
 * Returns:
 * bool
 */
function IsBot(User $nick)
{
	if (strpos($nick->usermode,"B") !== false)
		return true;
	return false;
}

/* Checks if the User object is connecting to
 * IRC securely.
 *
 * Syntax:
 * IsSecure($user_object)
 * 
 * Returns:
 * bool
 */
function IsSecure(User $nick)
{
	if (strpos($nick->usermode,"z") !== false)
		return true;
	return false;
}

/* Checks if the User object is connecting to
 * IRC using WEBIRC protocol.
 *
 * Syntax:
 * IsWebUser($user_object)
 * 
 * Returns:
 * bool
 */
function IsWebUser(User $nick)
{
	return isset($nick->meta->webirc);
}


/* Checks if the User object is logged in.
 *
 * Syntax:
 * IsLoggedIn($user_object)
 * 
 * Returns:
 * bool
 */
function IsLoggedIn(User $nick)
{
	if (isset($nick->account) && $nick->account && strlen($nick->account))
		return true;
	return false;
}

/* Checks if the User object is a client
 * of ours, from this pseudo-server
 *
 * Syntax:
 * MyUser($user_object)
 * 
 * Returns:
 * bool
 */
function MyUser(User $nick)
{
	return $nick->IsClient;
}

/* Checks a user object to see if it
 * is also a server
 * 
 * Syntax:
 * IsServer($user_object)
 * 
 * Returns:
 * bool
 */
function IsServer(User $nick)
{
	return $nick->IsServer;
}

/* Returns bold text from the given
 * argument.
 * 
 * Syntax:
 * bold($string);
 * 
 * Returns:
 * string
 */
function bold($s)
{
	return chr(2).$s.chr(2);
}

/* Returns underlined text from the given
 * argument.
 * 
 * Syntax:
 * ul($string);
 * 
 * Returns:
 * string
 */
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

/* Logs to disk =]
 *
 * Syntax: 
 * log_to_disk($string_to_log)
 *
 * Returns:
 *  
*/
function log_to_disk($str) : void
{
	if (!is_dir(DALEK_LOG_DIR))
		mkdir(DALEK_LOG_DIR);
	
	$lfile = DALEK_LOG_DIR . "/dalek.".date("d-m-Y").".log";
	$logfile = fopen($lfile, "a") or die("Unable to log to disk. Please check directory permissions.");
	fwrite($logfile,$str."\n");
	fclose($logfile);
}



/* Checks if an invitation is valid
 * 
 * Syntax:
 * is_invite($unix_timestamp)
 * 
 * Returns:
 * bool
*/
function is_invite($one, $two) : bool
{
	global $servertime;

	$return = false;

	$conn = sqlnew();

	/* quickly clear up any expired invitations (12hrs) */

	$exptime = $servertime - 43200;
	$result  = $conn->query("DELETE FROM ".sqlprefix()."invite WHERE realtime < $exptime");
	/* check their credentials */ 
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."invite WHERE timestamp = ?");
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
		$prep = $conn->prepare("DELETE FROM ".sqlprefix()."invite WHERE code = ?");
		$prep->bind_param("s",$two);
		$prep->execute();
	}	
	$prep->close();
	return $return;
}




/* Checks if a user has already been issued with a
 * currently valid invitation code.
 * 
 * Syntax:
 * already_invited($username)
 * 
 * Returns:
 * bool 
 */
function already_invited($invitee) : bool
{
	$conn = sqlnew();
	$ts = $invitee;
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."invite WHERE timestamp = ?");
	$prep->bind_param("s",$invitee);
	$prep->execute();
	$result = $prep->get_result();
	if ($result->num_rows == 0)
		return false;
	return true;
}

/* Generates an invitation code which the invitee can use
 * as SASL credentials in order to gain a temporary
 * authentication.
 * 
 * Syntax:
 * generate_invite_code($invite_nick)
 * 
 * Returns:
 * string $code
 */
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
	$prep = $conn->prepare("INSERT INTO ".sqlprefix()."invite (code,timestamp,realtime) VALUES (?,?,?)");
	$prep->bind_param("ssi",$hash,$ts,$servertime);
	$prep->execute();

	return $ts.":".$hash;
}

/* Looks up users who are connected to IRC
 * using the account name you specify and returns
 * an array of User objects.
 * 
 * Syntax:
 * list_users_by_account($account_name)
 *
 * Returns:
 * array $users[0+] => (User)$user
 */
function list_users_by_account($account)
{
	if (!$account)
		return [];
	else $account = strtoupper($account);
	$users = [];
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT UID FROM ".sqlprefix()."user WHERE UPPER(account) = ?");
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

/* Splits up a string by a space
 * (chr 32)
 *
 * Syntax:
 * split($string)
 * 
 * Returns:
 * array $tokens
 */
function split($str, $delimiter = " ") : array
{
	return explode($delimiter,$str);
}

function mtag_to_array($mtags) : array
{
	$mtag = array();

	$tags = explode(";",mb_substr($mtags,1));
	foreach($tags as $tag)
	{
		$tok = explode("=",$tag);

		$key = $tok[0];
		$value = mb_substr($tag,strlen($tok[0]) + 1);

		$mtag[$key] = $value;
	}
	return $mtag;
}

/* Create a new full set of outgoing message tags
 * for our clients
 * 
 * Syntax:
 * generate_new_mtags(Client $client_on_our_services_server)
 * 
 * Returns:
 * array $mtags["name"] => $info
 * 
 */
function generate_new_mtags(Client $user = NULL)
{
	$mtags = [];
	$mtags["msgid"] = new_msgid();
	$mtags["time"] = irc_timestamp();
	$user = ($user) ? new User($user->nick) : $user;
	if ($user && IsLoggedIn($user))
		$mtags["account"] = $user->account;
	return $mtags;
}

/* Create a new msgid for outgoing client messages.
 * Syntax:
 * new_msgid()
 * 
 * Returns string of md5 based on pseudo-random generators
 * and current server time.
*/
function new_msgid() : string
{
	$s = random_bytes(24);
	return base64_encode($s);
}

/* 
 * Returns timestamp used in places like server-time
 * mtags, like @time=2022-07-25T16:51:40.009Z
 * 
 * Syntax:
 * irc_timestamp()
 */
function irc_timestamp()
{
	$thing = microsecs();
	return date("Y-m-d\TH:i:s.".$thing."\Z");
}

/* Add mtag to mtag list with tag and value
 * by reference.
 * 
 * Doesn't return anything.
 * 
 * Syntax:
 * add_mtag(&$list, $tag_name, $tag_value)
 */
function add_mtag(&$list, $tag, $value) : void
{
	$list[$tag] = $value;
}

/* Merges our current mtags to the target mtags
 * by reference.
 * 
 * Doesn't return anything.
 * 
 * Syntax:
 * duplicate_mtags(&$target_msgtags, $current_msgtags)
 */
function duplicate_mtags(&$target, $current) : void
{
	if (!$current)
		return;
	foreach($current as $tag => $value)
		add_mtag($target, $tag, $value);
	
}

/* Checks if the token provided is a bad pointer, by reference
 * Returns bool value true if it IS bad
 *
 * Syntax:
 * BadPtr($variable)
 * 
 * Returns:
 * @
*/
function BadPtr(&$tok)
{
	if (!isset($tok) ||	!$tok || strlen($tok) == 0)
		return true;
	return false;
}


/* You can pass a string or an array as $notice
 * It forwards to a splat operator which deals
 * with it
 */

function sendnotice(User $user, Client $me = NULL, $mtags = [], ...$notice)
{
	
	if ($me)
		foreach($notice as $n)
			$me->notice_with_mtags($mtags, $user->uid, $n);

	else 
	{
		foreach($notice as $n)
			S2S(array_to_mtag($mtags)."NOTICE $user->uid :$n");
	}
}

function sendmsg(User $user, Client $me = NULL, $mtags = [], ...$msg)
{
	if ($me)
		foreach($msg as $n)
			$me->msg_with_mtags($mtags, $user->uid, $n);

	else 
	{
		foreach($msg as $n)
			S2S(array_to_mtag($mtags)."PRIVMSG $user->uid :$n");
	}
}
/* Dalek's char counting function */
function dcount_chars($haystack, $needle)
{
	$count = 0;
	
	for($i = 0; isset($haystack[$i]); $i++)
		if (!strcmp($haystack[$i],$needle))
			$count++;

	return $count;
}
/* Dalek's case-insensitive char counting function */
function dcasecount_chars($haystack, $needle)
{
	$count = 0;
	
	for($i = 0; isset($haystack[$i]); $i++)
		if (!strcasecmp($haystack[$i],$needle))
			$count++;
	return $count;
}

/* Some functions from C to make things easier */
function strcat(&$targ,$string)
{ $targ .= $string; }

function strlcat(&$targ,$string,$size)
{
	strcat($targ,$string);
	$targ = mb_substr($targ,0,$size);
}

function strprefix(&$targ,$string)
{ $targ = $string.$targ; }

function strlprefix(&$targ,$string,$size)
{
	$s = $size;
	if (sizeof($targ) >= $s)
		return;

	strprefix($targ,$string);
	$targ = mb_substr($targ,0,$s);
}

function svslogin($uid, $account, $by = NULL)
{
	global $cf;

	$by = ($by) ? $by->uid : $cf['sid'];
	S2S(":$by SVSLOGIN * $uid $account");
	
}

/* must be logged in
 * placeholders:
 * %n = network
 * %a = account
 * %r = rank
 * 
 * example:
 * "%n/%r/%a" (default) would equivelate to:
 * "Chatsite/staff/Bob" or
 * "Chatsite/user/Alice"
*/
function DoCloak($uid,$account)
{
	$u = new WPUser($account);
	
	global $cf;
	$clk = (isset($cf['cloak_users'])) ? $cf['cloak_users'] : false;

	if (!$clk) // if we are not cloaking the user
		return;
	$cloak = "";
	$rank = cloak_rank($u);
	if ($clk == true) //de folt
		$cloak = $cf['network']."/".$rank."/".$account;
	
	else // they doing a custom one
	{
		$cloak = str_replace("%r",$rank,$clk);
		$cloak = str_replace("%a",$account,$clk);
		$cloak = str_replace("%n",$cf['network'],$clk);
	}

	/* actually set the cloak */
	$send = "CHGHOST $uid :$cloak";
	S2S($send); // send it out
	Buffer::add_to_buffer($send); // we also need to process it, this is the quickest way	
}

// for quick check with the function above
function cloak_rank(WPUser $u)
{
	$rank = ($u->IsAdmin) ? "staff" : "user"; // staff or user?
	$rank = (isset($u->user_meta->robot) && strstr($u->user_meta->robot,"IRC Bot")) ? "bot" : $rank; // but wait? what if bot?
	return $rank;
}
function send_numeric($target, $numeric, ...$string)
{
	foreach($string as $str)
		S2S("$numeric $target :$string");
}

/* Send notice to users with mode:
 * @argv1 $mode : Can be a string of modes to send to
 * @argv2+ $string: One or more strings to send to these usermodes
 */
function sendto_umode($mode, ...$string)
{
	for ($i = 0; isset($mode[$i]); $i++)
		foreach ($string as $str)
			S2S("SENDUMODE ".$mode[$i]." :$str");
}

function log_to_opers($string)
{
	sendto_umode("o",$string);
	SVSLog($string);
}

function array_to_mtag($mtags)
{
	$mtags_to_send = NULL;
	if ($mtags)
	{
		$mtags_to_send = "@";
		foreach ($mtags as $mkey => $mval)
			$mtags_to_send .= $mkey."=".$mval.";";

		$mtags_to_send = rtrim($mtags_to_send,";");
		$mtags_to_send .= " ";
	}
	return $mtags_to_send;
}


function glue($array, $delimiter = " ")
{
	$string = "";
	foreach($array as $str)
	{
		if (!$str)
			continue;
		$string .= $str.$delimiter;
	}
	return trim($string,$delimiter);
}

function cut_first_from($string, $delimiter = " ")
{
	$parv = split($string,":");
	$parv[0] = NULL;
	return trim(glue($parv,":"));
}



function IsRPCCall()
{
	$array_of_files_included = get_included_files();
	foreach($array_of_files_included as $file)
	{
		$n = explode("/",$file);
		if ($n[count($n) - 1] == "main.php")
			return false;
		if ($n[count($n) - 1] == "index.php")
			return true;
	}
	die(); // if we can't tell if we're an RPC user or not, what the hell even happened?
}

/* some basic validation checking */
function is_valid_hostmask($string)
{
	for ($i = 0; isset($string[$i]); $i++)
		if (!is_invalid_hostmask_char($string[$i]))
			return false;
	return true;
}
function is_invalid_hostmask_char($char)
{
	if (preg_match('/\d/', $char) || preg_match('/[a-zA-Z]/', $char) || $char == "." || $char == "-" || $char == "_")
		return true;
	return false;
}

/* If we are running in debug mode */
function IsDebugMode()
{
	global $cf;
	if (isset($cf['debugmode']) && $cf['debugmode'] == "on")
		return true;
	return false;
}

function DebugLog($string, $type = "") : void
{

	if (!IsDebugMode())
		return;
	/* affix a type */
	global $cf,$serv;
	$string = "[DEBUG]".trim($type)." ".$string;

	echo $string."\n";
	log_to_disk($string);
}

function LogChan()
{
	global $cf;
	if (isset($cf['logchan']))
		return $cf['logchan'];
	else
		return "#services";
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
	
	if (!$string)
		return;
	$serv->sendraw($string);
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

function local_rpc_call($json)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"http://localhost:1024/api/");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
				$json);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec($ch);
	curl_close ($ch);
	$json = json_decode($server_output, true);
	return $json;
}
