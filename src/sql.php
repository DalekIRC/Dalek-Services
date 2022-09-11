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
\\	Title:		SQL
//				
\\	Desc:		Provides basic essential client-side
//				SQL querying.
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/


class SQL {
	function __construct($ip,$user,$pass,$db)
	{
		global $ip,$user,$pass,$db;
	}
	function query($query)
	{
		global $cf;
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$result = mysqli_query($conn,$query);
			return $result;
		}
	}
	function user_insert($u)
	{
		global $cf;
		$gecos = NULL; // empty gecos to fix a weird error
		$conn = sqlnew();
		$user = new User($u['nick']);
		if ($user->IsUser)
			return;
		if (!$conn) { return false; }

		
		else {
			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."user (
				
				nick,
				timestamp,
				ident,
				realhost,
				account,
				UID,
				usermodes,
				cloak,
				ip,
				SID,
				gecos
			) VALUES (
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?
			)");
			$prep->bind_param("sssssssssss",$u['nick'],$u['timestamp'],$u['ident'],$u['realhost'],$u['account'],$u['uid'],$u['usermodes'],$u['cloak'],$u['ip'],$u['sid'], $gecos);
			$prep->execute();
			$prep->close();
			update_gecos($u['nick'],$u['gecos']);
		}
	}
	function user_insert_by_serv($ar)
	{
		if (!$ar)
			return;
		$conn = sqlnew();
		$prep = $conn->prepare("INSERT INTO ".sqlprefix()."user (
				
				nick,
				timestamp,
				ident,
				realhost,
				account,
				UID,
				usermodes,
				cloak,
				ip,
				SID,
				gecos
			) VALUES (
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?,
				?
			)");
		
		foreach ($ar as $u)
		{
				$gecos = NULL;
				$user = new User($u['nick']);
				if ($user->IsUser)
					continue;
				$prep->bind_param("sssssssssss",$u['nick'],$u['timestamp'],$u['ident'],$u['realhost'],$u['account'],$u['uid'],$u['usermodes'],$u['cloak'],$u['ip'],$u['sid'], $gecos);
				$prep->execute();
				update_last($u['nick']);
				update_gecos($u['nick'],$u['gecos']);
		}
	}
	function user_delete($u)
	{
		global $cf;
		$conn = sqlnew();
		if (!$conn) { return false; }
		if (!$u)
			return;
		else {
			$prep = $conn->prepare("DELETE FROM ".sqlprefix()."user WHERE UID = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep = $conn->prepare("DELETE FROM ".sqlprefix()."user_meta WHERE UID = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep = $conn->prepare("DELETE FROM ".sqlprefix()."swhois WHERE UID = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep = $conn->prepare("DELETE FROM ".sqlprefix()."ison WHERE nick = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep->close();
		}
	}
	function sid($u)
	{
		global $cf;
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."server (
				servername,
				hops,
				sid,
				version,
				intro_by
			) VALUES (
				?,
				?,
				?,
				?,
				?
			)");
			if (!isset($u['intro_by']) || !$u['intro_by'])
			{
				$u['intro_by'] = $cf['sid'];
			}
			$prep->bind_param("sssss",$u['server'],$u['hops'],$u['sid'],$u['desc'],$u['intro_by']);
			$prep->execute();
			$prep->close();
		}
	}
	function delsid($sid)
	{
		global $sql;
		$conn = sqlnew();

		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user WHERE SID = ?");
		$prep->bind_param("s",$sid);
		$prep->execute();
		$result = $prep->get_result();
		while($row = $result->fetch_assoc())
			$sql->user_delete($row['UID']);
		$prep = $conn->prepare("DELETE FROM ".sqlprefix()."server WHERE SID = ?");
		$prep->bind_param("s",$sid);
		$prep->execute();
	}
	static function sjoin($u)
	{
		global $cf;
		$conn = sqlnew();
		if (!$conn) { return false; }
		if (find_channel($u['channel']))
			return;
		else {

			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."channels (
				timestamp,
				channel,
				modes
			) VALUES (
				?,
				?,
				?
			)");
			
			$prep->bind_param("sss",$u['timestamp'],$u['channel'],$u['modes']);
			$prep->execute();
			$prep->close();
		}
	}
	
	function get_userchmode($chan,$user)
	{
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			if (!($u = new User($user))->IsUser)
				return false;
			$uid = $u->uid;
			$prep = $conn->prepare("SELECT mode FROM ".sqlprefix()."ison WHERE nick = ? AND chan = ?");
			
			$prep->bind_param("ss",$uid,$chan);
			$prep->execute();
			$result = $prep->get_result();
			$row = $result->fetch_assoc();
			$prep->close();
			if (!isset($row['mode']))
				return false;
			return $row['mode'];
		}
	}
	function add_userchmode($chan,$user,$mode)
	{
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$mode = $this->get_userchmode($chan,$user).$mode;
			if (!($u = new User($user))->IsUser)
				return false;
				

			$prep = $conn->prepare("UPDATE ".sqlprefix()."ison SET mode = ? WHERE nick = ? AND chan = ?");
			
			$prep->bind_param("sss",$mode,$u->uid,$chan);
			$prep->execute();
			$prep->close();
		}
	}
	function del_userchmode($chan,$user,$mode)
	{
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$mode = str_replace($mode,"",$this->get_userchmode($chan,$user));
			if (!($u = new User($user))->IsUser)
				return false;
			$prep = $conn->prepare("UPDATE ".sqlprefix()."ison SET mode = ? WHERE nick = ? AND chan = ?");
			
			$prep->bind_param("sss",$mode,$u->uid,$chan);
			$prep->execute();
			$prep->close();
		}
	}

	function update_chmode($chan,$switch,$chr)
	{
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$prep = $conn->prepare("UPDATE ".sqlprefix()."channels SET modes = ? WHERE channel = ?");
			$modes = get_chmode($chan);
			if ($switch == "+")
			{
				$set = "+".$modes.$chr;
				$prep->bind_param("ss",$set,$chan);
				$prep->execute();
			}
			elseif ($switch == "-")
			{
				$set = "+".str_replace($chr,"",$modes);
				$prep->bind_param("ss",$set,$chan);
				$prep->execute();
			}
			$prep->close();
		}
	}
	function insert_ison($chan,$uid,$mode = "")
	{
		global $cf;
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {

			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."ison (
				chan,
				nick,
				mode
			) VALUES (
				?,
				?,
				?
			)");
			
			$prep->bind_param("sss",$chan,$uid,$mode);
			$prep->execute();
			$prep->close();
		}
	}
	function delete_ison($chan,$uid,$modes = "")
	{
		$sql_modestring = "+"; // what modes to set dem as in de databass

		if (!BadPtr($modes)) // figure out them modes
		{
			
			for ($i = 0; !BadPtr($modes); $i++, $char = $modes[$i])
			{
				if (!strcmp($char,"+"))
					strcat($sql_modestring,"v");
				
				elseif (!strcmp($char,"%"))
					strcat($sql_modestring,"h");
				
				elseif (!strcmp($char,"@"))
					strcat($sql_modestring,"o");

				elseif (!strcmp($char,"~"))
					strcat($sql_modestring,"a");

				elseif (!strcmp($char,"*"))
					strcat($sql_modestring,"q");

				elseif (!strcmp($char,"!"))
					strcat($sql_modestring,"Y");
			}
		}


		$conn = sqlnew();
		$prep = $conn->prepare("DELETE FROM ".sqlprefix()."ison WHERE nick = ? AND lower(chan) = ?");
		$prep->bind_param("ss",$uid,$chan);
		$prep->execute();

		/* cleanup any non-permanent empty channel */

		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."ison WHERE lower(chan) = ?");
		$prep->bind_param("s",$chan);
		$prep->execute();
		$result = $prep->get_result();

		if ($result->num_rows == 0)
		{
			$lookup = find_channel($chan);
			if (strpos($lookup['modes'],"P") == false)
			{
				$prep = $conn->prepare("DELETE FROM ".sqlprefix()."channels WHERE channel = ?");
				$prep->bind_param("s",$lookup['channel']);
				$prep->execute();
			}
		}
		$prep->close();
	}
}


function do_part($chan,$nick)
{
	$conn = sqlnew();
	$prep = $conn->prepare("DELETE FROM ".sqlprefix()."ison WHERE nick = ? AND lower(chan) = ?");
	$prep->bind_param("ss",$nick->uid,$chan);
	$prep->execute();

	/* cleanup any non-permanent empty channel */
	
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."ison WHERE lower(chan) = ?");
	$prep->bind_param("s",$chan);
	$prep->execute();
	$result = $prep->get_result();
	
	if ($result->num_rows == 0)
	{
		$lookup = find_channel($chan);
		if (strpos($lookup['modes'],"P") == false)
		{
			$prep = $conn->prepare("DELETE FROM ".sqlprefix()."channels WHERE channel = ?");
			$prep->bind_param("s",$lookup['channel']);
			$prep->execute();
		}
	}
	$prep->close();
}

function find_channel($channel)
{
	$conn = sqlnew();
	$return = [];
	if (!$conn)
		return false;
	else {
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."channels WHERE channel = ?");
		$prep->bind_param("s",$channel);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result)
			return false;
		if ($result->num_rows == 0)
			return false;
		$return = $result->fetch_assoc();

		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."chaninfo WHERE channel = ?");
		$prep->bind_param("s",$channel);
		$prep->execute();
		$result = $prep->get_result();
		if ($result && $result->num_rows > 0)
			while($row = $result->fetch_assoc())
				$return['owner'] = $row['owner'];


		$prep->close();
	}
	return $return;
}

function get_chmode($channel)
{
	$channel = new Channel($channel);
	return $channel->modes;
}

function sqlnew()
{
	global $cf;

	$i = 0;
	beginning:
	if ($i >= 15)
		die(SVSLog("Could not connect to the database for 30 seconds. Shutting down.", LOG_FATAL));
		
	if (is_null($cf['sqlsock']))
		$conn = mysqli_connect($cf['sqlip'],$cf['sqluser'],$cf['sqlpass'],$cf['sqldb'],$cf['sqlport'] = "3306");
	else
		$conn = mysqli_connect($cf['sqlip'],$cf['sqluser'],$cf['sqlpass'],$cf['sqldb'],$cf['sqlport'] = "3306",$cf['sqlsock']);

	if ($conn->connect_error)
	{
		SVSLog("Could not connect to mysql database (".$conn->connect_error.": Error code: ".$conn->connect_errno.") Trying again in 2 seconds", LOG_WARN);
		sleep(2);
		$i++;
		goto beginning;
	}
	return $conn;
}			

function sqlprefix()
{
    global $cf;
    return (isset($cf['sqlprefix'])) ? $cf['sqlprefix'] : 'dalek_';
}

hook::func(HOOKTYPE_PRE_CONNECT, function($u)
{
	$conn = sqlnew();
	
	$conn->multi_query("CREATE TABLE IF NOT EXISTS ".sqlprefix()."user (
		id int NOT NULL AUTO_INCREMENT,
		nick varchar(255) NOT NULL,
		timestamp int NOT NULL,
		ident varchar(255) NOT NULL,
		realhost varchar(255) NOT NULL,
		UID varchar(255) NOT NULL,
		usermodes varchar(255),
		cloak varchar(255) NOT NULL,
		gecos varchar(255),
		ip varchar(255) NOT NULL,
		account varchar(255),
		secure varchar(1),
		fingerprint varchar(255),
		SID varchar(3) NOT NULL,
		oper varchar(1),
		away varchar(1),
		awaymsg varchar(255),
		version varchar(255),
		last int,
		PRIMARY KEY (id)
	);
	CREATE TABLE IF NOT EXISTS ".sqlprefix()."server (
		id int NOT NULL AUTO_INCREMENT,
		servername varchar(255),
		sid varchar(3) NOT NULL,
		linktime varchar(10),
		usermodes varchar(255),
		channelmodes varchar(255),
		hops varchar(4),
		version varchar(255),
		intro_by varchar(255),
		PRIMARY KEY (id)
	);
	CREATE TABLE IF NOT EXISTS ".sqlprefix()."channels (
		id int NOT NULL AUTO_INCREMENT,
		timestamp int NOT NULL,
		channel varchar(255),
		modes varchar(255),
		topic varchar(255),
		PRIMARY KEY (id)
	);
	CREATE TABLE IF NOT EXISTS ".sqlprefix()."swhois (
		id int AUTO_INCREMENT NOT NULL,
		tag varchar(255),
		uid varchar(255),
		priority varchar(255),
		swhois varchar(255),
		PRIMARY KEY(id)
	);
	CREATE TABLE IF NOT EXISTS ".sqlprefix()."ison (
		id int AUTO_INCREMENT NOT NULL,
		chan varchar(255),
		nick varchar(255),
		mode varchar(255),
		PRIMARY KEY(id)
	);
	CREATE TABLE IF NOT EXISTS ".sqlprefix()."user_meta (
		id int AUTO_INCREMENT NOT NULL,
		UID varchar(10),
		meta_key varchar(255),
		meta_data varchar(255),
		PRIMARY KEY(id)
	);
	CREATE TABLE IF NOT EXISTS ".sqlprefix()."tkldb (
		id int AUTO_INCREMENT NOT NULL,
		type varchar(2) NOT NULL,
		ut varchar(255) NOT NULL,
		mask varchar(255) NOT NULL,
		set_by varchar(255) NOT NULL,
		expiry int NOT NULL,
		timestamp int NOT NULL,
		reason varchar(255),
		PRIMARY KEY(id)
	);
	CREATE TABLE IF NOT EXISTS ".sqlprefix()."invite (
		id int AUTO_INCREMENT NOT NULL,
		code varchar(255) NOT NULL,
		timestamp varchar(255) NOT NULL,
		realtime int NOT NULL,
		PRIMARY KEY(id)
	);

	TRUNCATE TABLE ".sqlprefix()."user;
	TRUNCATE TABLE ".sqlprefix()."channels;
	TRUNCATE TABLE ".sqlprefix()."server;
	TRUNCATE TABLE ".sqlprefix()."swhois;
	TRUNCATE TABLE ".sqlprefix()."ison;
	TRUNCATE TABLE ".sqlprefix()."user_meta;
	TRUNCATE TABLE ".sqlprefix()."tkldb;");
	$conn->close();
});



hook::func(HOOKTYPE_WELCOME, function($u)
{
	global $sql,$fsync;
	
	$sql->user_insert($u);
	update_last($u['nick']);

	if (isset($fsync))
		SVSLog($u['nick']." (".$u['ident']."@".$u['realhost'].") [".$u['ip']."] connected to the network (".$u['sid'].")");
});



hook::func(HOOKTYPE_SERVER_CONNECT, function($u)
{
	global $sql;
	
	$sql->sid($u);
	
});

hook::func(HOOKTYPE_SJOIN, function($u)
{
	global $sql;
	
	$sql->sjoin($u);
});

/* Adds user meta to the database
 * $person = nick or uid
 * $key = meta key
 * $data = meta value
*/
function umeta_add($person,$key = "",$data = "")
{
	$user = new User($person);
	if (!$user->IsUser)
		return false;
	$conn = sqlnew();
	if ($data == "")
		return;
	if (!$conn) { return false; }
	
	else {
		$prep = $conn->prepare("INSERT INTO ".sqlprefix()."user_meta (UID, meta_key, meta_data) VALUES (?, ?, ?)");

		$tag = explode(";",mb_substr($data,1));
		for ($i = 0; isset($tag[$i]); $i++)
		{
			$t = explode("/",$tag[$i]);
			$key = $t[0];
			$value = $t[1];
			
			$prep->bind_param("sss",$user->uid,$key,$value);
			$prep->execute();
		}
		$prep->close();
	}
}




function get_num_online_users() : int
{
	$conn = sqlnew();

	if (!$conn)
		return 0;
	else {
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return 0;
}

function get_num_servers() : int
{
	$conn = sqlnew();

	if (!$conn)
		return 0;
	else {
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."server");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return 0;
}

function get_num_channels() : int
{
	$conn = sqlnew();

	if (!$conn)
		return 0;
	else {
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."channels");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return 0;
}

function get_num_swhois() : int
{
	$conn = sqlnew();

	if (!$conn)
		return 0;
	else {
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."swhois");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return 0;
}

function get_num_meta() : int
{
	$conn = sqlnew();

	if (!$conn)
		return 0;
	else {
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user_meta");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return 0;
}


function update_last($person) : bool
{
	global $servertime;
	if (!isset($person->IsUser) && is_string($person))
	{
		$user = new User($person);
		if (!$user->IsUser)
			return false;
	}
	$conn = sqlnew();
	if (!$conn)
		return false;
	else {
		$prep = $conn->prepare("UPDATE ".sqlprefix()."user SET last = ? WHERE UID = ?");
		$prep->bind_param("is",$servertime,$user->uid);
		$prep->execute();
		$prep->close();
	}
	return true;
}

function is_a_ban($chan,$ban) : bool
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."channel_meta WHERE chan = ? AND meta_value = ?");
	$prep->bind_param("ss",$chan->chan,$ban);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || !$result->num_rows)
		return false;
	return true;
}

function find_person($person = NULL)
{
	if (!$person or $person == "")
		return;

	$conn = sqlnew();
	if (!$conn) { return false; }
	else
	{
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user WHERE nick = ?");
		$prep->bind_param("s",$person);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result)
			goto uidcheck;
		if ($result->num_rows == 0)
			goto uidcheck;
		$row = $result->fetch_assoc();
		return $row;
		
		uidcheck:
		
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user WHERE UID = ?");
		$prep->bind_param("s",$person);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result)
			return false;
		if ($result->num_rows == 0)
			return false;
		$row = $result->fetch_assoc();
		$prep->close();
		return $row;
	}
}

function update_nick($uid,$nick,$ts) : bool
{
	$conn = sqlnew();
	if (!$conn)
		return false;
	else {
		
		$person = new User($uid);
		if (!$person->IsUser)
			return false;
		
		$prep = $conn->prepare("UPDATE ".sqlprefix()."user SET nick = ?, timestamp = ? WHERE UID = ?");
		$prep->bind_param("sis",$nick,$ts,$uid);
		$prep->execute();
		$prep->close();
	}
	return true;
}

function update_host($uid,$host,$ts) : bool
{
	$conn = sqlnew();
	if (!$conn)
		return false;
	else {
		$person = new User($uid);
		if (!$person->IsUser)
			return false;
		$uid = $person->uid;
		
		$prep = $conn->prepare("UPDATE ".sqlprefix()."user SET cloak = ?, timestamp = ? WHERE UID = ?");
		$prep->bind_param("sis",$host,$ts,$uid);
		$prep->execute();
		$prep->close();
	}
	return true;
}
function update_ident($uid,$ident,$ts) : bool
{
	$conn = sqlnew();
	if (!$conn)
		return false;
	else {
		$person = new User($uid);
		if (!$person->IsUser)
			return false;
		$uid = $person->uid;
		
		$prep = $conn->prepare("UPDATE ".sqlprefix()."user SET ident = ?, timestamp = ? WHERE UID = ?");
		$prep->bind_param("sis",$ident,$ts,$uid);
		$prep->execute();
		$prep->close();
	}
	return true;
}
function update_usermode($uid,$new) : bool
{
	$conn = sqlnew();
	if (!$conn)
		return false;
	else {
		
		$person = find_person($uid);
		$uid = $person['UID'];
		
		$prep = $conn->prepare("UPDATE ".sqlprefix()."user SET usermodes = ? WHERE UID = ?");
		$prep->bind_param("ss",$new,$uid);
		$prep->execute();
		$prep->close();
	}
	return true;
}
function find_serv($serv) 
{
	$conn = sqlnew();
	if (!$conn)
		return false;
	else {
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."server WHERE servername = ?");
		$prep->bind_param("s",$serv);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result)
{ goto sidcheck; }
		if ($result->num_rows == 0)
{ goto sidcheck; }
		$row = $result->fetch_assoc();
		return $row;
		
		sidcheck:
		
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."server WHERE sid = ?");
		$prep->bind_param("s",$serv);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result)
{ return false; }
		if ($result->num_rows == 0)
{ return false; }
		$row = $result->fetch_assoc();
		$prep->close();
		return $row;
	}
}
function get_ison($uid)
{
	$conn = sqlnew();
	if (!$conn)
		return false;
	else {
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."ison WHERE nick = ?");
		$prep->bind_param("s",$uid);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result || $result->num_rows == 0)
		{
			return false;
		}
		$list = array();
		$mode = array();
		while ($row = $result->fetch_assoc())
{
			$list[] = $row['chan'];
			$mode[] = $row['mode'];
		}
		$big = array('list' => $list, 'mode' => $mode);
		
		$prep->close();
		return $big;
	}
}

function recurse_serv_attach($sid) : array
{
	$squit = array();
	for ($squit[] = $sid, $i = 0; isset($squit[$i]); $i++)
		foreach(serv_attach($squit[$i]) as $key => $value)
			if (!in_array($value,$squit))
				$squit[] = $value;

	return $squit;
}


function del_sid($sid) : void
{
	global $sql;
	$sql->delsid($sid);
}

function serv_num_users($sid) : int
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user WHERE SID = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || !$result->num_rows)
		return 0;
	$return = $result->num_rows;
	return (int)$return;
}

function serv_num_attach($sid) : int
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."server WHERE intro_by = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result)
		return 0;
	if (($numr = $result->num_rows) == 0)
		return 0;
	else
		return (int)$numr;
}

function serv_attach($sid)
{
	$return = array();
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."server WHERE intro_by = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result)
		return false;
	while ($row = $result->fetch_assoc())
		if (!in_array($row['sid'],$return))
			$return[] = $row['sid'];

	return $return;
}

function recurse_serv_users($sid)
{
	$return = array();
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."user WHERE SID = ?");
	foreach (recurse_serv_attach($sid) as $s)
	{
		$prep->bind_param("s",$s);
		$prep->execute();
		$result = $prep->get_result();
		if (!$result)
			continue;
		while ($row = $result->fetch_assoc())
			if (!in_array($row['UID'],$return))
				$return[] = $row['UID'];
	}
	return $return;
}

function update_gecos($nick,$gecos) : void
{
	$conn = sqlnew();
	$gecos = ircstrip($gecos);
	$prep = $conn->prepare("UPDATE ".sqlprefix()."user SET gecos = ? WHERE nick = ?");
	$prep->bind_param("ss",$gecos,$nick);
	$prep->execute();
}
