<?php
/*
 *	(C) 2021 Pride IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: BANS
 *
 * 
 *	Version: 1
*/
hook::func("preconnect", function(){
	AddCommand(
		$cmd = array(
			'entity' => "X",
			'cmd' => "BANS",
			'help' => "Lists all banned users from a channel.",
			'helpstr' => "Lists all banned users from a channel.",
			'syntax' => "/msg %me BANS <#channel>",
		)
	);
});
X::func("privmsg", function($u)
{
    global $x;
	
	$parv = explode(" ",$u['msg']);
	if ($parv[0] !== "bans")
		return;
	
	$nick = new User($u['nick']);
	$wpnick = new WPUser($u['nick']);
	if (!$nick->account)
	{
		$eu->notice($nick->uid,"You need to login use that command.");
		return;
	}
	
	if (!isset($parv[1]))
	{
		$x->notice($nick->uid,"Syntax: /msg $x->nick BANS <#channel>");
		return;
	}
	$chan = new Channel($parv[1]);
	
	if (!$chan->IsChan)
	{
		$x->notice($nick->uid,"That channel doesn't exist.");
		return;
	}
	
	
	$chaccess = ChanAccessAsInt($chan,$nick);
	
	if ($chaccess <= 2 && !IsStaff($nick))
	{
		$x->notice($nick->uid,"Permission denied");
		return;
	}
	
	$conn = conn();
	$prep = $conn->prepare("SELECT * FROM dalek_channel_meta WHERE chan = ? and meta_key = ?");
	$meta_key = "ban";
	$prep->bind_param("ss",$chan->chan,$meta_key);
	$prep->execute();
	
	$result = $prep->get_result();
	
	if (!$result->num_rows)
    {
        $prep->close();
        $x->notice($nick->uid,"There are no bans on channel $chan->chan");
        return;
    }
    
    $x->notice($nick->uid,"Listing bans on channel: $chan->chan");
    $x->notice($nick->uid,clean_align("Mask:").clean_align("Set by:").clean_align("Timestamp:"));
    while ($row = $result->fetch_assoc())
        $x->notice($nick->uid,clean_align($row['meta_value']).clean_align($row['meta_setby']).clean_align($row['meta_timestamp']));
    $prep->close();
});
