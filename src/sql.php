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
	
	function __construct(){
	}
	function query($query){
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$result = mysqli_query($conn,$query);
			return $result;
		}
	}
	function user_insert($u){
		$conn = sqlnew();
		$user = new User($u['nick']);
		if ($user->IsUser)
			return;
		if (!$conn) { return false; }
		else {
			$prep = $conn->prepare("INSERT INTO dalek_user (
				
				nick,
				timestamp,
				ident,
				realhost,
				account,
				UID,
				usermodes,
				cloak,
				gecos,
				ip,
				SID
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
			$prep->bind_param("sssssssssss",$u['nick'],$u['timestamp'],$u['ident'],$u['realhost'],$u['account'],$u['uid'],$u['usermodes'],$u['cloak'],$u['gecos'],$u['ip'],$u['sid']);
			$prep->execute();
			$prep->close();
		}
	}
	function user_insert_by_serv($ar)
	{
		if (!$ar)
			return;
		$conn = sqlnew();
		$prep = $conn->prepare("INSERT INTO dalek_user (
				
				nick,
				timestamp,
				ident,
				realhost,
				account,
				UID,
				usermodes,
				cloak,
				gecos,
				ip,
				SID
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
				$user = new User($u['nick']);
				if ($user->IsUser)
					continue;
				$prep->bind_param("sssssssssss",$u['nick'],$u['timestamp'],$u['ident'],$u['realhost'],$u['account'],$u['uid'],$u['usermodes'],$u['cloak'],$u['gecos'],$u['ip'],$u['sid']);
				$prep->execute();
				update_last($u['nick']);
		}
	}
	function user_delete($u){
		$conn = sqlnew();
		if (!$conn) { return false; }
		if (!$u)
			return;
		else {
			$prep = $conn->prepare("DELETE FROM dalek_user WHERE UID = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep = $conn->prepare("DELETE FROM dalek_user_meta WHERE UID = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep = $conn->prepare("DELETE FROM dalek_swhois WHERE UID = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep = $conn->prepare("DELETE FROM dalek_ison WHERE nick = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep->close();
		}
	}
	function sid($u){
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$prep = $conn->prepare("INSERT INTO dalek_server (
				
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
			
			$prep->bind_param("sssss",$u['server'],$u['hops'],$u['sid'],$u['desc'],$u['intro_by']);
			$prep->execute();
			$prep->close();
		}
	}
	function delsid($u){
		$conn = sqlnew();
		$prep = $conn->prepare("DELETE FROM dalek_server WHERE sid = ?");
		$prep->bind_param("s",$u);
		$prep->execute();
		$prep = $conn->prepare("DELETE FROM dalek_user_meta WHERE UID LIKE ?");
		$t = $u."%";
		$prep->bind_param("s",$t);
		$prep->execute();
		$prep = $conn->prepare("DELETE FROM dalek_ison WHERE nick LIKE ?");
		$prep->bind_param("s",$t);
		$prep->execute();
		$prep = $conn->prepare("DELETE FROM dalek_user WHERE SID = ?");
		$prep->bind_param("s",$u);
		$prep->execute();

		$conn->close();
	}
	function sjoin($u){
		$conn = sqlnew();
		if (!$conn) { return false; }
		if (find_channel($u['channel']))
			return;
		else {

			$prep = $conn->prepare("INSERT INTO dalek_channels (
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
			$prep = $conn->prepare("SELECT mode FROM dalek_ison WHERE nick = ? AND chan = ?");
			
			$prep->bind_param("ss",$uid,$chan);
			$prep->execute();
			$result = $prep->get_result();
			$row = $result->fetch_assoc();
			$prep->close();
			return $row['mode'];
		}
	}
	function add_userchmode($chan,$user,$mode){
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$mode = $this->get_userchmode($chan,$user).$mode;
			if (!($u = new User($user))->IsUser)
				return false;
				

			$prep = $conn->prepare("UPDATE dalek_ison SET mode = ? WHERE nick = ? AND chan = ?");
			
			$prep->bind_param("sss",$mode,$u->uid,$chan);
			$prep->execute();
			$prep->close();
		}
	}
	function del_userchmode($chan,$user,$mode){
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {
			$mode = str_replace($mode,"",$this->get_userchmode($chan,$user));
			if (!($u = new User($user))->IsUser)
				return false;
			$prep = $conn->prepare("UPDATE dalek_ison SET mode = ? WHERE nick = ? AND chan = ?");
			
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
			$prep = $conn->prepare("UPDATE dalek_channels SET modes = ? WHERE channel = ?");
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
	function insert_ison($chan,$uid,$mode = ""){
		$conn = sqlnew();
		if (!$conn) { return false; }
		else {

			$prep = $conn->prepare("INSERT INTO dalek_ison (
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
	function delete_ison($chan,$uid)
	{
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
	}
}


function do_part($chan,$nick)
{
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
}

function find_channel($channel)
{
	
	global $ns;
	$conn = sqlnew();
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_channels WHERE channel = ?");
		$prep->bind_param("s",$channel);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result){ return false; }
		if ($result->num_rows == 0){ return false; }
		$row = $result->fetch_assoc();
		$prep->close();
	}
	return $row;
}

function get_chmode($channel)
{
	$channel = new Channel($channel);
	return $channel->modes;
}

function sqlnew()
{
	global $cf;
	if (is_null($cf['sqlsock']))
		$conn = mysqli_connect($cf['sqlip'],$cf['sqluser'],$cf['sqlpass'],$cf['sqldb'],$cf['sqlport']);
	else
		$conn = mysqli_connect($cf['sqlip'],$cf['sqluser'],$cf['sqlpass'],$cf['sqldb'],$cf['sqlport'],$cf['sqlsock']);
	return $conn;
}			

hook::func("preconnect", function($u){
	
	$conn = sqlnew();
	
	$result = $conn->multi_query("CREATE TABLE IF NOT EXISTS dalek_user (
		id int NOT NULL AUTO_INCREMENT,
		nick varchar(255) NOT NULL,
		timestamp int NOT NULL,
		ident varchar(255) NOT NULL,
		realhost varchar(255) NOT NULL,
		UID varchar(255) NOT NULL,
		usermodes varchar(255),
		cloak varchar(255) NOT NULL,
		gecos varchar(255) NOT NULL,
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
	CREATE TABLE IF NOT EXISTS dalek_server (
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
	CREATE TABLE IF NOT EXISTS dalek_channels (
		id int NOT NULL AUTO_INCREMENT,
		timestamp int NOT NULL,
		channel varchar(255),
		modes varchar(255),
		topic varchar(255),
		PRIMARY KEY (id)
	);
	CREATE TABLE IF NOT EXISTS dalek_swhois (
		id int AUTO_INCREMENT NOT NULL,
		tag varchar(255),
		uid varchar(255),
		priority varchar(255),
		swhois varchar(255),
		PRIMARY KEY(id)
	);
	CREATE TABLE IF NOT EXISTS dalek_ison (
		id int AUTO_INCREMENT NOT NULL,
		chan varchar(255),
		nick varchar(255),
		mode varchar(255),
		PRIMARY KEY(id)
	);
	CREATE TABLE IF NOT EXISTS dalek_user_meta (
		id int AUTO_INCREMENT NOT NULL,
		UID varchar(10),
		meta_key varchar(255),
		meta_data varchar(255),
		PRIMARY KEY(id)
	);
	TRUNCATE TABLE dalek_user;
	TRUNCATE TABLE dalek_channels;
	TRUNCATE TABLE dalek_server;
	TRUNCATE TABLE dalek_swhois;
	TRUNCATE TABLE dalek_ison;
	TRUNCATE TABLE dalek_user_meta;");
	$conn->close();
});



hook::func("UID", function($u){
	
	global $sql,$ns,$narray,$fsync;
	
	if (isset($fsync))
	{
		$sql::user_insert($u);
		update_last($u['nick']);
	}
	if (!$narray)
		$narray = array();
	
	$narray[] = $u;
	
	/*
	if (isset($ns)){ $ns->log($u['nick']." (".$u['ident']."@".$u['realhost'].") [".$u['ip']."] connected to the network (".$u['sid'].")"); }
	*/
});

hook::func("raw", function($u){

	global $narray;
	
	$parv = explode(" ",$u['string']);
	if ($parv[1] !== "EOS")
		return;

	SQL::user_insert_by_serv($narray);
	unset($narray);		
});


hook::func("SID", function($u){
	
	global $sql;
	
	$sql::sid($u);
	
});

hook::func("SJOIN", function($u){
	global $sql;
	
	$sql::sjoin($u);
});
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
		$prep = $conn->prepare("INSERT INTO dalek_user_meta (UID, meta_key, meta_data) VALUES (?, ?, ?)");

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




function get_num_online_users()
{
	$conn = sqlnew();

	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_user");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return false;
}

function get_num_servers()
{
	$conn = sqlnew();

	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_server");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return false;
}

function get_num_channels()
{
	$conn = sqlnew();

	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_channels");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return false;
}

function get_num_swhois()
{
	$conn = sqlnew();

	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_swhois");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return false;
}

function get_num_meta()
{
	$conn = sqlnew();

	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_user_meta");
		$prep->execute();
		$result = $prep->get_result();
		$count = $result->num_rows;
		return $count;
	}
	return false;
}


function update_last($person)
{
	global $servertime;
	
	$user = new User($person);
	if (!$user->IsUser){ return false; }
	
	$conn = sqlnew();
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("UPDATE dalek_user SET last = ? WHERE UID = ?");
		$prep->bind_param("is",$servertime,$user->uid);
		$prep->execute();
		$prep->close();
	}
}

function find_person($person){
	
	if (!$person or $person == "")
		return;
	global $ns;
	$conn = sqlnew();
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_user WHERE nick = ?");
		$prep->bind_param("s",$person);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result){ goto uidcheck; }
		if ($result->num_rows == 0){ goto uidcheck; }
		$row = $result->fetch_assoc();
		return $row;
		
		uidcheck:
		
		$prep = $conn->prepare("SELECT * FROM dalek_user WHERE UID = ?");
		$prep->bind_param("s",$person);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result){ return; }
		if ($result->num_rows == 0){ return false; }
		$row = $result->fetch_assoc();
		$prep->close();
		return $row;
	}
}

function update_nick($uid,$nick,$ts){
	
	global $ns;
	$conn = sqlnew();
	if (!$conn) { return false; }
	else {
		
		$person = find_person($uid);
		$uid = $person['UID'];
		
		$prep = $conn->prepare("UPDATE dalek_user SET nick = ?, timestamp = ? WHERE UID = ?");
		$prep->bind_param("sis",$nick,$ts,$uid);
		$prep->execute();
		$prep->close();
	}
}
function update_usermode($uid,$new){
	
	global $ns;
	$conn = sqlnew();
	if (!$conn) { return false; }
	else {
		
		$person = find_person($uid);
		$uid = $person['UID'];
		
		$prep = $conn->prepare("UPDATE dalek_user SET usermodes = ? WHERE UID = ?");
		$prep->bind_param("ss",$new,$uid);
		$prep->execute();
		$prep->close();
	}
}
function find_serv($serv){
	
	global $ns;
	$conn = sqlnew();
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_server WHERE servername = ?");
		$prep->bind_param("s",$serv);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result){ goto sidcheck; }
		if ($result->num_rows == 0){ goto sidcheck; }
		$row = $result->fetch_assoc();
		return $row;
		
		sidcheck:
		
		$prep = $conn->prepare("SELECT * FROM dalek_server WHERE sid = ?");
		$prep->bind_param("s",$serv);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result){ return false; }
		if ($result->num_rows == 0){ return false; }
		$row = $result->fetch_assoc();
		$prep->close();
		return $row;
	}
}
function get_ison($uid){
	
	global $ns;
	$conn = sqlnew();
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT * FROM dalek_ison WHERE nick = ?");
		$prep->bind_param("s",$uid);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result){ return false; }
		if ($result->num_rows == 0)
		{
			return false;
		}
		$list = array();
		$mode = array();
		while ($row = $result->fetch_assoc()){
			$list[] = $row['chan'];
			$mode[] = $row['mode'];
		}
		$big = array('list' => $list, 'mode' => $mode);
		
		$prep->close();
		return $big;
	}
}

function recurse_serv_attach($sid)
{
	$squit = array();
	for ($squit[] = $sid, $i = 0; isset($squit[$i]); $i++)
		foreach(serv_attach($squit[$i]) as $key => $value)
			if (!in_array($value,$squit))
				$squit[] = $value;

	return $squit;
}


function del_sid($sid)
{
	global $sql;
	$sql->delsid($sid);
}

function serv_num_users($sid)
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_user WHERE SID = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	$return = $result->num_rows;
	return $return;
}

function serv_num_attach($sid)
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_server WHERE intro_by = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result)
		return 0;
	if (($numr = $result->num_rows) == 0)
		return 0;
	else
		return $numr;
}

function serv_attach($sid)
{
	$return = array();
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_server WHERE intro_by = ?");
	$prep->bind_param("s",$sid);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result)
		return 0;
	while ($row = $result->fetch_assoc())
		if (!in_array($row['sid'],$return))
			$return[] = $row['sid'];

	return $return;
}

function recurse_serv_users($sid)
{
	$return = array();
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_user WHERE SID = ?");
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

