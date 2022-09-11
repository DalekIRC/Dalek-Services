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
\\	Title: Set Email
//	
\\	Desc: Allows a user to update the email address associated
//	with their account.
\\	
//	Syntax: SET SHOWSOCIAL <on|off>
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/



nickserv::func("setcmd", function($u){
	
	global $ns,$nickserv;
	$nick = new User($u['nick']);
	$parv = explode(" ",$u['cmd']);
	if ($parv[0] !== "set"){ return; }

	
	if ($parv[1] !== "showsocial"){ return; }
	if ($nickserv['login_method'] !== "wordpress"){ return; }
	if (!($account = IsLoggedIn($u['nick']))){ $ns->notice($u['UID'],"You must be logged in to use this command."); return; }
	if (!isset($parv[2])){ return; }
	
	$is = $parv[2];
	if ($is !== "on" && $is !== "off")
	{
		$ns->notice($u['uid'],"Syntax: /msg $ns->nick SET SHOWSOCIAL <on|off>");
		return;
	}
	if ($is == "on")
		SetSocial($nick->uid,$account);
	else
		DelSocial($nick->uid);

	$ns->notice($nick->uid,"SHOWSOCIAL has been set to be '$is'");
	ShowSocial($account,$is);
	return;
	
	
});
hook::func("UID", function($u)
{
	if (!isset($u['account']))
		return;
	if ($u['account'] && IsShowSocial($u['account']))
	{
		SetSocial($u['uid'],$u['account']);
	}
});

function DelSocial($uid)
{
	specialwhois::del_swhois($uid,'facebook');
	specialwhois::del_swhois($uid,'website');
	specialwhois::del_swhois($uid,'twitter');
	specialwhois::del_swhois($uid,'LinkedIn');
	specialwhois::del_swhois($uid,'dribbble');
	specialwhois::del_swhois($uid,'Instagram');
	specialwhois::del_swhois($uid,'Pinterest');
	specialwhois::del_swhois($uid,'WordPress');
	specialwhois::del_swhois($uid,'GitHub');
	specialwhois::del_swhois($uid,'Medium');
	specialwhois::del_swhois($uid,'YouTube');
	specialwhois::del_swhois($uid,'Vimeo');
	specialwhois::del_swhois($uid,'vKontakte');
	specialwhois::del_swhois($uid,'Odnoklassniki');
	specialwhois::del_swhois($uid,'TikTok');
}
function SetSocial($uid, $account)
{
	$wp_user = new WPUser($account);

	$social = array('website' => $wp_user->user_url,
						'facebook' => $wp_user->user_meta->facebook,
						'twitter' => $wp_user->user_meta->twitter,
						'LinkedIn' => $wp_user->user_meta->linkedin,
						'dribbble' => $wp_user->user_meta->dribbble,
						'Instagram' => $wp_user->user_meta->instagram,
						'Pinterest' => $wp_user->user_meta->pinterest,
						'WordPress' => $wp_user->user_meta->wordpress,
						'GitHub' => $wp_user->user_meta->github,
						'Medium' => $wp_user->user_meta->medium,
						'YouTube' => $wp_user->user_meta->youtube,
						'Vimeo' => $wp_user->user_meta->vimeo,
						'vKontakte' => $wp_user->user_meta->vkontakte,
						'Odnoklassniki' => $wp_user->user_meta->odnoklassniki,
						'TiKTok' => $wp_user->user_meta->tiktok);

		foreach($social as $key => $value)
		{
			if (strlen($value) !== 0)
				specialwhois::send_swhois($uid,$key,"$key: $value");
		}
}
function ShowSocial($account,$option)
{
	$opt = "showsocial";
	$conn = sqlnew();
	if (!$conn) { return false; }
	else
	{
		$prep = $conn->prepare("SELECT setting_value FROM ".sqlprefix()."account_settings WHERE account = ? AND setting_key = 'showsocial'");
		$prep->bind_param("s",$account);
		$prep->execute();
		$result = $prep->get_result();
		
		if ($result->num_rows == 0)
		{
		
			$prep = $conn->prepare("INSERT INTO ".sqlprefix()."account_settings (account, setting_key, setting_value) VALUES (?, ?, ?)");
			$prep->bind_param("sss", $account, $opt, $option);
			$prep->execute();
			$return = true;
		}
		
		else
		{
			
			while($row = $result->fetch_assoc())
			{
				$switch = $row['setting_value'];
				
				if (($switch == "on" && $option == "on") || ($switch == "off" && $option == "off")){ $return = false; }
				else
				{
					$prep = $conn->prepare("UPDATE ".sqlprefix()."account_settings SET setting_value = ? WHERE account = ? AND setting_key = ?");
					$prep->bind_param("sss", $option, $account, $opt);
					$prep->execute();
					$return = true;
				}
			}
		}
	}
	$prep->close();
	return $return;
}
function IsShowSocial($account)
{	
	$conn = sqlnew();
	if (!$conn) { return false; }
	else
	{
		$prep = $conn->prepare("SELECT setting_value FROM ".sqlprefix()."account_settings WHERE account = ? AND setting_key = 'showsocial'");
		$prep->bind_param("s", $account);
		$prep->execute();
		$result = $prep->get_result();
		
		if ($result->num_rows == 0){ return false; }
		else
		{
			
			$row = $result->fetch_assoc();
			if ($row['setting_value'] == "on"){ $return = true; }
			if ($row['setting_value'] == "off"){ $return = false; }
		}
	}
	$prep->close();;
	return $return;
}
		

nickserv::func("setlist", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress")
		return;
	if (isset($u['key'])){ return; }
	if (isset($parv[0])){ return; }
	$ns->notice($u['nick'],"SHOWSOCIAL          Show your social links in your NickServ INFO and /WHOIS.");
});
