<?php


/*				
//	(C) 2021 DalekIRC Services
\\				
//		dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: _is_disabled
//	
\\	Desc: Provides function for WordPress plugin "Disable User Login"
//	
\\	
//	Syntax: none
\\	
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/


function _is_disabled(WPUser $user) : int
{
	if (!isset($user->user_meta->_is_disabled))
		return 0;
	if ($user->user_meta->_is_disabled == "1")
		return 1;
	if ($user->user_meta->_is_disabled !== NULL)
		return 0;
	return $user->user_meta->_is_disabled;
}

function suspend_account(WPUser $user, $reason = "Account suspended") : bool
{
	global $wpconfig,$cf;

	if ($user->IsAdmin)
		return false;
	
	if (_is_disabled($user))
		return false;

	$conn = sqlnew();
	$check = "_is_disabled";
	$put = 1;
	$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."usermeta WHERE user_id = ? AND meta_key = ?");
	$prep->bind_param("is", $user->user_id, $check);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || !$result->num_rows)
	{
		$prep = $conn->prepare("INSERT INTO ".$wpconfig['dbprefix']."usermeta (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
		$prep->bind_param("iss", $user->user_id, $check, $put);
		$prep->execute();
	}
	else {
		$prep = $conn->prepare("UPDATE ".$wpconfig['dbprefix']."usermeta SET meta_value = ? WHERE user_id = ? AND meta_key = ?");
		$prep->bind_param("sis", $put, $user->user_id, $check);
		$prep->execute();
	}
	$ns = Client::find("NickServ");

	/* find all users ... */
	foreach(user_list() as $u)
	{	
		/* ...logged in with this account and ... */
		if (!strcasecmp($user->user_login,$u->account))
		{
			/* 1) Log them out */
			ns_logout::UserLogout($u);

			/* 2) Part them from any "important" channels */
			$chans = $u->channels['list'];
			foreach ($chans as $i => $c)
			{
				$chan = new Channel($c);
				if ($chan->HasMode("R") || $chan->HasMode("O"))
					S2S("SVSPART $u->nick $chan->chan Account suspended");
			}
		}
	}
	
	return true;
}
function unsuspend_account(WPUser $user) : bool
{
	global $wpconfig,$cf;

	if ($user->IsAdmin)
		return false;
	if (!_is_disabled($user))
		return false;
	$conn = sqlnew();
	$check = "_is_disabled";
	$put = 0;
	$prep = $conn->prepare("SELECT * FROM ".$wpconfig['dbprefix']."usermeta WHERE user_id = ? AND meta_key = ?");
	$prep->bind_param("is", $user->user_id, $check);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || !$result->num_rows)
	{
		$prep = $conn->prepare("INSERT INTO ".$wpconfig['dbprefix']."usermeta (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
		$prep->bind_param("iss", $user->user_id, $check, $put);
		$prep->execute();
	}
	else {
		$prep = $conn->prepare("UPDATE ".$wpconfig['dbprefix']."usermeta SET meta_value = ? WHERE user_id = ? AND meta_key = ?");
		$prep->bind_param("sis", $put, $user->user_id, $check);
		$prep->execute();
	}
	
	return true;
}
hook::func("ping", function($u)
{
	$ns = Client::find("NickServ");
	foreach(user_list() as $user)
	{
		if (!$user->IsWordPressUser)
			continue;
		if (_is_disabled($user->wp))
		{
			ns_logout::UserLogout($user);
			$chans = $user->channels['list'];
			/* 2) Part them from any "important" channels */
			foreach ($chans as $i => $c)
			{
				$chan = new Channel($c);
				if ($chan->HasMode("R") || $chan->HasMode("O"))
					S2S("SVSPART $u->nick $chan->chan Account suspended");
			}
		}
	}
});




















