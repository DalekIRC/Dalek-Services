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
	
	function __construct($ip,$user,$pass,$db){
		global $ip,$user,$pass,$db;
	}
	function query($query){
		global $sqlip,$sqluser,$sqlpass,$sqldb,$cf;
		$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
		if (!$conn) { return false; }
		else {
			$result = mysqli_query($conn,$query);
			return $result;
		}
	}
	function user_insert($u){
		global $sqlip,$sqluser,$sqlpass,$sqldb,$cf;
		$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
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
	function user_delete($u){
		global $sqlip,$sqluser,$sqlpass,$sqldb,$cf;
		$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
		if (!$conn) { return false; }
		else {
			$prep = $conn->prepare("DELETE FROM dalek_user WHERE UID = ?");
			$prep->bind_param("s",$u);
			$prep->execute();
			$prep->close();
		}
	}
	function sid($u){
		global $sqlip,$sqluser,$sqlpass,$sqldb,$cf;
		$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
		if (!$conn) { return false; }
		else {
			$prep = $conn->prepare("INSERT INTO dalek_server (
				
				servername,
				hops,
				sid,
				version
			) VALUES (
				?,
				?,
				?,
				?
			)");
			
			$prep->bind_param("ssss",$u['server'],$u['hops'],$u['sid'],$u['desc']);
			$prep->execute();
			$prep->close();
		}
	}
	function sjoin($u){
		global $sqlip,$sqluser,$sqlpass,$sqldb,$cf;
		$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
		if (!$conn) { return false; }
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
	function insert_ison($chan,$uid,$mode){
		global $sqlip,$sqluser,$sqlpass,$sqldb,$cf;
		$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
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
}
			

hook::func("preconnect", function($u){
	global $sql;
	
	$query = "CREATE TABLE IF NOT EXISTS dalek_user (
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
	)";
	$sql::query($query);
	
	$query = "CREATE TABLE IF NOT EXISTS dalek_server (
		id int NOT NULL AUTO_INCREMENT,
		servername varchar(255),
		sid varchar(3) NOT NULL,
		linktime varchar(10),
		usermodes varchar(255),
		channelmodes varchar(255),
		hops varchar(4),
		version varchar(255),
		PRIMARY KEY (id)
	)";
	$sql::query($query);
	
	$query = "CREATE TABLE IF NOT EXISTS dalek_channels (
		id int NOT NULL AUTO_INCREMENT,
		timestamp int NOT NULL,
		channel varchar(255),
		modes varchar(255),
		topic varchar(255),
		PRIMARY KEY (id)
	)";
	$sql::query($query);
	
	$query =	"CREATE TABLE IF NOT EXISTS dalek_swhois (
		id int AUTO_INCREMENT NOT NULL,
		tag varchar(255),
		uid varchar(255),
		priority varchar(255),
		swhois varchar(255),
		PRIMARY KEY(id)
	)";
	$sql::query($query);
	
	$query =	"CREATE TABLE IF NOT EXISTS dalek_ison (
		id int AUTO_INCREMENT NOT NULL,
		chan varchar(255),
		nick varchar(255),
		mode varchar(255),
		PRIMARY KEY(id)
	)";
	$sql::query($query);
	
	$query = 	"TRUNCATE TABLE dalek_user";
	$sql::query($query);
	$query = 	"TRUNCATE TABLE dalek_channels";
	$sql::query($query);
	$query = 	"TRUNCATE TABLE dalek_server";
	$sql::query($query);
	$query = 	"TRUNCATE TABLE dalek_swhois";
	$sql::query($query);
	$query =	"TRUNCATE TABLE dalek_ison";
	$sql::query($query);
});



hook::func("UID", function($u){
	
	global $sql,$ns;
	$sql::user_insert($u);
	update_last($u['nick']);
	if (isset($ns)){ $ns->log($u['nick']." (".$u['ident']."@".$u['realhost'].") [".$u['ip']."] connected to the network (".$u['sid'].")"); }
	
});


hook::func("SID", function($u){
	
	global $sql;
	
	$sql::sid($u);
	
});

hook::func("SJOIN", function($u){
	global $sql;
	
	$sql::sjoin($u);
});

function update_last($person)
{
	
	global $sqlip,$sqluser,$sqlpass,$sqldb,$servertime;
	
	$user = new User($person);
	if (!$user->IsUser){ return false; }
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("UPDATE dalek_user SET last = ? WHERE UID = ?");
		$prep->bind_param("is",$servertime,$user->uid);
		$prep->execute();
		$prep->close();
	}
}
		
		
function find_person($person){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb,$ns;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
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

function find_channel($channel){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb,$ns;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
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

function update_nick($uid,$nick,$ts){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb,$ns;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
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
	
	global $sqlip,$sqluser,$sqlpass,$sqldb,$ns;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
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
	
	global $sqlip,$sqluser,$sqlpass,$sqldb,$ns;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
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
		
		if (!$result){ return; }
		if ($result->num_rows == 0){ return false; }
		$row = $result->fetch_assoc();
		$prep->close();
		return $row;
	}
}
function get_ison($uid){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb,$ns;
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
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