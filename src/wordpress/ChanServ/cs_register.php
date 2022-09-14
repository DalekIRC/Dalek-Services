<?php
/*
 *	(C) 2021 Dalek IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: REGISTER channels
 *
 * 
 *	Version: 1
*/


hook::func("start", function()
{
	$conn = sqlnew();
	if (!$conn)
		return;
	
	$result = $conn->query("SELECT * FROM ".sqlprefix()."chaninfo");
	if (!$result)
		return;
	if ($result->num_rows == 0)
		return;
	
	while ($row = $result->fetch_assoc())
	{
		$chan = new Channel($row['channel']);
		if (!$chan->IsChan)
			return;
		if (strpos($chan->modes,"r") == false)
			$chan->SetMode("+r");
	}
});


chanserv::func("privmsg", function($u)
{
	global $cs;
	$parv = explode(" ",$u['msg']);
	$nick = new User($u['nick']);
	
	if ($parv[0] !== "register")
		return;
	
	if (!isset($parv[1]))
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick REGISTER <channel>");
		return;
	}
	$chan = new Channel($parv[1]);
	
	if (!$nick->account)
	{
		$cs->notice($nick->uid,"You must be logged in to use that command.");
		return;
	}
	if (!$chan->HasUser($nick->uid))
	{
		$cs->notice($nick->uid,"You must be on that channel to register it.");
		return;
	}
	if (!$chan->IsOp($nick->uid))
	{
		$cs->notice($nick->uid,"You must be an operator on that channel to register it.");
		return;
	}
	if ($chan->IsReg)
	{
		$cs->notice($nick->uid,"That channel is already registered.");
		return;
	}
	$account = (isset($parv[2])) ? $parv[2] : $nick->account;
	$wp = new WPUser($account);
	if ($account !== $nick->account)
		if (!IsAdmin(wp_get_caps($nick->account)))
		{
			$cs->notice($nick->uid,"You may not register channels on behalf of others.");
			return;
		}
	if (!$wp->IsUser)
	{
		$cs->notice($nick->uid,"'$account' is not a registered user.");
		return;
	}
	if (!($reg = register_channel($chan->chan,$account)))
	{
		$cs->notice($nick->uid,"Could not register channel at this time.");
		return;
	}
	$chan->SetMode("+rq $nick->nick");
	$cs->log("Channel $chan->chan has been registered by $nick->nick to account $account");
	$cs->notice($nick->uid,"Channel $chan->chan has been registered under account $account");
	
	
});

function register_channel($chan,$owner)
{
	global $servertime;

	$conn = sqlnew();
	if (!$conn)
		return false;
	$chatlink = "/chat/?channel=".$chan;
	$prep = $conn->prepare("INSERT INTO ".sqlprefix()."chaninfo (channel, owner, regdate, chatlink) VALUES (?, ?, ?, ?)");
	$prep->bind_param("ssss",$chan,$owner,$servertime,$chatlink);
	$prep->execute();
	
	$permission = "owner";
	$prep = $conn->prepare("INSERT INTO ".sqlprefix()."chanaccess (channel, nick, access) VALUES (?, ?, ?)");
	$prep->bind_param("sss",$chan,$owner,$permission);
	$prep->execute();
	return true;
}

hook::func("preconnect", function()
{
	global $sql;
	
	$query = "CREATE TABLE IF NOT EXISTS ".sqlprefix()."chaninfo (
				id int AUTO_INCREMENT NOT NULL,
				channel varchar(255) NOT NULL,
				owner varchar(255) NOT NULL,
				regdate varchar(15) NOT NULL,
				url varchar(255),
				email varchar(255),
				chatlink varchar(255),
				PRIMARY KEY(id)
			)";
	$sql->query($query);
	
	$query = "CREATE TABLE IF NOT EXISTS ".sqlprefix()."chanaccess (
				id int AUTO_INCREMENT NOT NULL,
				channel varchar(255) NOT NULL,
				nick varchar(255) NOT NULL,
				access varchar(20) NOT NULL,
				PRIMARY KEY(id)
			)";
	$sql->query($query);
});


chanserv::func("helplist", function($u){
	
	global $cs;
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"REGISTER            Register a channel to your account");
	
});


chanserv::func("help", function($u){
	
	global $cs;
	
	if ($u['key'] !== "register"){ return; }
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"Command: REGISTER");
	$cs->notice($nick,"Syntax: /msg $cs->nick register #channel");
	$cs->notice($nick,"Example: /msg $cs->nick register #channel");
});
