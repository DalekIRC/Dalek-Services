<?php
/*
 *	(C) 2022 Dalek IRC Services
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



hook::func("privmsg", function($u)
{
	global $cs;
	
	$tok = explode(" ",$u['parv']);
	if ($tok[0] !== "!info")
		return;
	if ($u['dest'][0] !== "#")
		return;
	
	$params = rparv($u['parv']);
	
	if (strlen($params) == 0)
	{
		$cs->notice($u['nick'],"Syntax: /msg $cs->nick INFO <#channel>");
		return;
	}
	chanserv::run("privmsg", array(
		'msg' => "$command $params",
		'nick' => $u['nick'])
	);

});

chanserv::func("privmsg", function($u)
{
	global $cs;
	$caps = NULL;
	$nick = new User($u['nick']);
	$parv = explode(" ",$u['msg']);
	
	if ($parv[0] !== "info")
		return;
	
	if (!isset($parv[1]))
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick INFO <#channel>");
		return;
	}

	$chan = new Channel($parv[1]);
	
	$cs->notice($nick->uid,"Listing information about $chan->chan");
	if (!$chan->RegDate)
	{
		$cs->notice($nick->uid,"$chan->chan is not registered.");
		return;
	}
	$cs->notice($nick->uid,"$chan->chan is registered to $chan->owner");
	$cs->notice($nick->uid,"$chan->chan was registered on: ".gmdate("Y-m-d\TH:i:s\Z", $chan->RegDate));
	$cs->notice($nick->uid,"Channel email: $chan->email");
	$cs->notice($nick->uid,"Channel URL: $chan->url");

	
});

chanserv::func("helplist", function($u){
	
	global $cs;
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"INFO                List information about a given channel.");
	
});


chanserv::func("help", function($u){
	
	global $cs;
	
	if ($u['key'] !== "autoop"){ return; }
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"Command: AUTOOP");
	$cs->notice($nick,"Syntax: /msg $cs->nick INFO <#channel>");
	$cs->notice($nick,"Example: /msg $cs->nick INFO <#channel>");
});

function channel_owner_list($nick)
{
	global $cs;
		
	$conn = sqlnew();
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
