<?php

/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: Ajoin
//	
\\	Desc: Auto-join on identify.
//	Allows you to add/remove to a list of channels you wish to
\\	be autojoined to when you identify with NickServ.
//	
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/


nickserv::func("privmsg", function($u){
	
	global $ns;
	
	$nick = new User($u['nick']);
	$account = $nick->account ?? NULL;
	$parv = explode(" ",$u['msg']);
	$cmd = $parv[0] ?? NULL;
	$flag = (isset($parv[1])) ? strtolower($parv[1]) : NULL;
	
	if ($cmd !== "ajoin") { return; }

	if (!$account){ $ns->notice($nick->nick,IRC("ERR_NOTLOGGEDIN")); return; }
	if (!$flag){ goto badsyntax_ajoin; }
	if ($flag !== "add" && $flag !== "del" && $flag !== "list"){ goto badsyntax_ajoin; }
	
	if ($flag == "add"){
		if (!($channel = find_channel($parv[2]))){ $ns->notice($nick->nick,"Channel does not exist"); return; }
		if (($reply = ajoin_add($account,$channel['channel'])) !== true){ $ns->notice($nick->nick,$reply); return; }
		$ns->notice($nick->nick,$reply);
		$ns->log($nick->nick." added ".$channel['channel']." to the ajoin list for account $account");
		return;
	}
	elseif ($flag == "del"){
		$channel = $parv[2];
		if (($reply = ajoin_del($account,$channel)) !== true){ $ns->notice($nick->nick,$reply); return ;}
		$ns->log($nick->nick." deleted $channel from the ajoin list for account $account");
		$ns->notice($nick->nick,$reply);
		return;
	}
	elseif ($flag == "list"){
		if (!($list = ajoin_list($account))){ $ns->notice($nick->nick,"Your autojoin list is empty."); return; }
		$ns->notice($nick->nick,"Listing your autojoin list:");
		while($row = $list->fetch_assoc()){
			$ns->notice($nick->nick,$row['channel']);
		}
		return;
	}
	
	badsyntax_ajoin:
	$ns->notice($nick->nick,"Syntax: AJOIN <[add|del]|list> [<channel>]");
	return;
});


hook::func("preconnect", function($u){
	
	global $sql;
	
	$query = "CREATE TABLE IF NOT EXISTS dalek_ajoin (
		id INT AUTO_INCREMENT NOT NULL,
		account VARCHAR(255) NOT NULL,
		channel VARCHAR(255) NOT NULL,
		PRIMARY KEY (id)
	)";
	$sql::query($query);
});

nickserv::func("identify", function($u){
	
	global $ns,$cf;
	
	if (!($list = ajoin_list($u['nick']->account ?? NULL))){ return; }
	while($row = $list->fetch_assoc()){
		if (isset($row['channel'])){ $ns->sendraw(":".$cf['sid']." SVSJOIN ".$u['nick']->nick." ".$row['channel']); }
	}
	
});

function IsAjoin($account,$channel){
	
	$list = ajoin_list($account) ?? NULL;
	$return = NULL;
	if (!$list){ return; }
	if ($list->num_rows == 0){ return; }
	while($row = $list->fetch_assoc()){
		if ($row['channel'] == $channel){ $return = true; }
	}
	if ($return){ return $return; }
	else { return false; }
}
		
function ajoin_list($account){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	else {
		$prep = $conn->prepare("SELECT channel FROM dalek_ajoin WHERE account = ?");
		$prep->bind_param("s",$account);
		$prep->execute();
		$sResult = $prep->get_result();
		$yep = $sResult;
		if ($sResult->num_rows == 0){ return false; }
		
		$prep->close();
		return $yep;
	}
}

function ajoin_add($account,$channel){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	
	if (IsAjoin($account,$channel)){ return "That channel is already on your list."; return; }
	
	else {
		$prep = $conn->prepare("INSERT INTO dalek_ajoin (account, channel) VALUES (?, ?)");
		$prep->bind_param("ss",$account,$channel);
		$prep->execute();
		return "$channel has been added to your autojoin list";
	}
}

function ajoin_del($account,$channel){
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	
	if (!IsAjoin($account,$channel)){ return "That channel is not on your list."; return; }
	
	else {
		$prep = $conn->prepare("DELETE FROM dalek_ajoin WHERE account = ? AND channel = ?");
		$prep->bind_param("ss",$account,$channel);
		$prep->execute();
		return "$channel has been deleted from your autojoin list";
	}
}

nickserv::func("helplist", function($u){
	
	global $ns;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"AJOIN               ".IRC("HELPCMD_AJOIN"));
	
});
