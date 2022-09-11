<?php
/*
 *	(C) 2022 Dalek IRC Services
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
chanserv::func("privmsg", function($u)
{
    global $cs;
	
	$parv = explode(" ",$u['msg']);
	if ($parv[0] !== "bans")
		return;
	
	$nick = new User($u['nick']);
	$wpnick = new WPUser($u['nick']);
	if (!$nick->account)
	{
		$cs->notice($nick->uid,"You need to login use that command.");
		return;
	}
	
	if (!isset($parv[1]))
	{
		$cs->notice($nick->uid,"Syntax: /msg $cs->nick BANS <#channel>");
		return;
	}
	$chan = new Channel($parv[1]);
	
	if (!$chan->IsChan)
	{
		$cs->notice($nick->uid,"That channel doesn't exist.");
		return;
	}
	
	
	$chaccess = ChanAccessAsInt($chan,$nick);
	
	if ($chaccess <= 2 && !IsAdmin($nick))
	{
		$cs->notice($nick->uid,"Permission denied");
		return;
	}
	
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."channel_meta WHERE chan = ? and meta_key = ?");
	$meta_key = "ban";
	$prep->bind_param("ss",$chan->chan,$meta_key);
	$prep->execute();
	
	$result = $prep->get_result();
	
	if (!$result->num_rows)
    {
        $prep->close();
        $cs->notice($nick->uid,"There are no bans on channel $chan->chan");
        return;
    }
    
    $cs->notice($nick->uid,"Listing bans on channel: $chan->chan");
    $cs->notice($nick->uid,clean_align("Mask:").clean_align("Set by:").clean_align("Timestamp:"));
    while ($row = $result->fetch_assoc())
        $cs->notice($nick->uid,clean_align($row['meta_value']).clean_align($row['meta_setby']).clean_align($row['meta_timestamp']));
    $prep->close();
});


chanserv::func("helplist", function($u){
	
	global $cs;
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"BANS                View the ban list for a channel.");
	
});


chanserv::func("help", function($u){
	
	global $cs;
	
	if ($u['key'] !== "bans"){ return; }
	
	$nick = $u['nick'];
	
	$cs->notice($nick,"Command: BANS");
	$cs->notice($nick,"Syntax: /msg $cs->nick bans #channel");
	$cs->notice($nick,"Example: /msg $cs->nick bans #channel");
});