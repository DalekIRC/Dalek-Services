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
\\	Title: All Admin
//	
\\	Desc: Gives admin to all
//	
\\	
//	
\\	
//	
\\	Version: 1
//				
\\	Author:	Valware
//				
*/


hook::func("sjoin", function($u)
{
	global $cs;
	echo "ahhhh\n";
	if ($u['channel'] == "#Valeyard" || $u['channel'] == "#PossumsOnly")
	{
		echo "Hmmmm\n";
		$cs->sendraw("SVSMODE ".$u['channel']." +qo ".$u['nick']." ".$u['nick']);
	}
});

chanserv::func("setcmd", function($u)
{
	
	global $cs,$cf,$operserv;
	$ison = false;
	
	$nick = new User($u['nick']);
	
	if ($operserv['oper'] !== $nick->nick || !$nick->account)
	{
		$cs->notice($nick->uid,"That is an admin only command");
		return;
	}
	
	$parv = explode(" ",$u['cmd']);
	
	if ($parv[0] !== "set")
		return;

	if ($parv[2] !== "alladmin")
		return;
	
	if (!$chan = find_channel($parv[1]))
	{ 
		$cs->notice($nick->uid,"That channel does not exist");
		return;
	}
	
	if (!$nick->account)
	{
		$cs->notice($nick->uid,"You are not logged in.");
		return;
	}
	
	for ($i = 0, $list = get_ison($nick->uid); isset($list['list'][$i]); $i++)
	if ($list['list'][$i] == $parv[1])
			$ison = true;
	
	if (!$ison)
	{
		$cs->notice($nick->uid,"You need to be on that channel to change the settings.");
		return;
	}
	switch($parv[3])
	{
		case "on":
			if (!($setting = channel_setting($parv[1],"alladmin","on")))
			{
				$cs->notice($nick->uid,"All Admin has been set to on for ".$parv[1]);
				return false;
			}
		case "off":
			if (!($setting = channel_setting($parv[1],"alladmin","off")))
			{
				$cs->notice($nick->uid,"All Admin has been set to off for ".$parv[1]);
				return false;
			}
	}
	
});


function channel_setting($channel,$key,$value)
{
	$conn = sqlnew();
	if (!$conn)
		return false;
		
	$prep = $conn->prepare("UPDATE dalek_channel_settings SET setting_key = ?, setting_value = ? WHERE channel = ?");
	$prep->bind_param("sss",$key,$value,$channel);
	$prep->execute();
	$prep->close();
}

hook::func("SJOIN", function($u)
{	
	global $cs;
	
	$tokens = explode(" ",$u['full']);
	$chan = $tokens[3];
	$list = explode(":",$u['full']);
	$parv = explode(" ",$list[count($list) - 1]);
	
	if (!$parv)
	{
		return;
	}
	for ($p = 0; $parv[$p]; $p++)
	{
		$mode = "";
		$item = $parv[$p];
		$user = new User($parv[$p]);
		if (is_all_admin($chan))
		{
			if (!$user->account)
				return;
			if (strpos($user->usermode,"o"))
				$cs->mode($chan,"+qo $user->nick $user->nick");
			else
				$cs->mode($chan,"+ao ".$parv[$p]." ".$parv[$p]);
		}
		else
			return;
	
	}
});


function is_all_admin($chan)
{
	global $ns;
	$conn = sqlnew();
	if (!$conn)
		return false;
		
	$isOn = false;
	$table = "alladmin";
	$prep = $conn->prepare("SELECT * FROM dalek_channel_settings WHERE channel = ? AND setting_key = ?");
	$prep->bind_param("ss",$chan,$table);
	$prep->execute();
	
	$result = $prep->get_result();
	$prep->close();
	while ($row = $result->fetch_assoc())
		if ($row['setting_value'] == "on")
			$isOn = true;
	
	return $isOn;
}

chanserv::func("setlist", function($u){
	
	global $cs;
	
	if (isset($u['key'])){ return; }
	if (isset($parv[0])){ return; }
	$cs->notice($u['nick'],"ALLADMIN            Set a channel to give everyone admin.");
});
chanserv::func("setlist", function($u)
{
	
	global $cs;
	echo strtolower($u['key']);
	if (strtolower($u['key']) !== "alladmin"){ return; }
	$cs->notice($u['nick'],"ALLADMIN            Syntax: /msg $cs->nick set #channel ALLADMIN on|off");
});
