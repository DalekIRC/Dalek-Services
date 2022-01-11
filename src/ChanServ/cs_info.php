<?php
/*
 *	(C) 2021 Pride IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: INFO command
 *
 * 
 *	Version: 1
*/

hook::func("preconnect", function(){
	AddCommand(
		$cmd = array(
			'entity' => "X",
			'cmd' => "INFO",
			'help' => "Use this command to view specific information on a user or channel,<lf>you can also use this to confirm is someone is a staff member.<lf>For more information try HELP INFO",
			'helpstr' => "Lookup information on a user or channel",
			'syntax' => "/msg %me INFO <nick|channel>",
		)
	);
});


hook::func("privmsg", function($u)
{
	global $x;
	
	$tok = explode(" ",$u['parv']);
	if ($tok[0] !== "!info")
		return;
	if ($u['dest'][0] !== "#")
		return;
	
	$params = rparv($u['parv']);

	$command = str_replace("!","",$tok[0]);
	$cmd = new Command($command);
	if (strlen($params) == 0)
	{
		$x->notice($u['nick'],$cmd->syntax);
		return;
	}
	X::run("privmsg", array(
		'msg' => "$command $params",
		'nick' => $u['nick'])
	);

});

X::func("privmsg", function($u)
{
	global $x;
	$caps = NULL;
	$nick = new User($u['nick']);
	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "info")
		return;
	
	if (!isset($parv[1]))
	{
		$x->notice($nick->uid,"Syntax: /msg $x->nick INFO <username>");
		return;
	}
	
	$wpuser = new WPUser($parv[1]);
	if (!$wpuser->IsUser)
	{
		goto x_info_botcheck;
	}
	
	$x->notice($nick->uid,"Listing information for ".$parv[1]." ($wpuser->display):");
	
	if (($list = channel_owner_list($wpuser->display)) !== false)
		$x->notice($nick->uid,clean_align("Owner of:").$list);
	
	$caps = wp_get_caps($nick->account);
	
	if ($wpuser->nicename == $nick->account || ($nick->account && in_array("administrator",$caps)))
		$x->notice($nick->uid,clean_align("Email:").$wpuser->email);

	$x->notice($nick->uid,clean_align("Registered on:").$wpuser->regdate);
	$x->notice($nick->uid,clean_align("Last login:").$wpuser->lastlogin);


	if (!($caps = wp_get_caps($wpuser->nicename)))
	{
		$x->notice($nick->uid,clean_align("Permissions:")." None");
		return;
	}
	foreach ($caps as $cap)
		$fullcaps .= $cap.", ";
		
	if (IsStaff($nick))
		$x->notice($nick->uid,clean_align("Permissions").substr($fullcaps,0 ,-2));
	
	$irc = new User($parv[1]);
	if (IsIdentified($irc))
		$loggedin = "Logged in";
	else
		$loggedin = "Not logged in";
	
	if (in_array("administrator",$caps))
		$x->notice($nick->uid,"$wpuser->login is an official network administrator ($loggedin)");
	elseif (in_array("services-admin",$caps))
		$x->notice($nick->uid,"$wpuser->login is an official administrator ($loggedin)");
	elseif (in_array("services-operator",$caps))
		$x->notice($nick->uid,"$wpuser->login is an official operator ($loggedin)");
	elseif (in_array("services-helper",$caps))
		$x->notice($nick->uid,"$wpuser->login is an official helper ($loggedin)");
		
	x_info_botcheck:
	$isbot = new User($parv[1]);
	if (IsServiceBot($isbot))
	{
		$x->notice($nick->uid,"$isbot->nick is an official service bot of IRCNetwork");
		return;
	}
	elseif (!$wpuser->IsUser)
	{
		$chan = new Channel($parv[1]);
		
		$x->notice($nick->uid,"Listing information about $chan->chan");
		if (!$chan->RegDate)
		{
			$x->notice($nick->uid,"$chan->chan is not registered.");
			return;
		}
		$x->notice($nick->uid,"$chan->chan is registered to $chan->owner");
		$x->notice($nick->uid,"Channel email: $chan->email");
		$x->notice($nick->uid,"Channel URL: $chan->url");
	}
	
});


function channel_owner_list($nick)
{
	global $db,$sqlip,$sqluser,$sqlpass,$sqldb,$x;
		
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	$table = "dalek_chaninfo";
	$prep = $conn->prepare("SELECT * FROM $table WHERE owner = ?");
	$prep->bind_param("s",$nick);
	$prep->execute();
	
	$result = $prep->get_result();
	
	if (empty($result))
		return false;
	
	$list = NULL;
	foreach ($result as $row)
		$list .= $row['channel'].", ";
		
	if (!$list)
		return;
	
	$list = substr($list, 0, -2);
	
	return $list;
}