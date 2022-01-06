s<?php


nickserv::func("privmsg", function($u)
{
	global $ns;

	$parv = explode(" ",strtolower($u['msg']));
	$parc = count($parv);

	if ($parv[0] !== "autoprivate")
		return;

	if ($parc < 2 || (($s = $parv[1]) !== "on" && $s !== "off"))
	{
		$ns->notice($u['nick'],"Syntax: AUTOPRIVATE [on|off]");
		return;
	}

	$nick = new User($u['nick']);

	if (!$nick->account)
	{
		$ns->notice($nick->uid,"You must be logged in to use that command.");
		return;
	}

	$setting = ($s == "on") ? 1 : 0;
	$addel = ($setting) ? "+" : "-";
	
	if (!user_meta_autoprivate($nick,"autoprivate",$setting))
	{
		$ns->notice($nick->uid,"An error occurred. Please contact a services administrator.");
		return;
	}
	$ns->notice($nick->uid,"AUTOPRIVATE has been set to '$s'");
	if ($setting)
		$ns->svs2mode($nick->nick,"+D");
	return;
});


nickserv::func("privmsg", function($u)
{
	global $ns;

	$parv = explode(" ",strtolower($u['msg']));
	$parc = count($parv);

	if ($parv[0] !== "private")
		return;

	if ($parc < 2 || (($s = $parv[1]) !== "on" && $s !== "off"))
	{
		$ns->notice($u['nick'],"Syntax: PRIVATE [on|off]");
		return;
	}

	$nick = new User($u['nick']);

	if (!$nick->account)
	{
		$ns->notice($nick->uid,"You must be logged in to use that command.");
		return;
	}

	$setting = ($s == "on") ? 1 : 0;
	
	if ($setting)
	{
		if (($var = strpos($nick->usermode,"D")) == false)
		{
			$ns->notice($nick->uid,"You are now blocking private messages. Let me just give you the +D lmfao");
			$ns->svs2mode($nick->nick,"+D");
		}
		else
			$ns->notice($nick->uid,"You are already blocking private messages.");

		return;
	}
	else
	{
		if (strpos($nick->usermode,"D") !== false)
		{
			$ns->notice($nick->uid,"You are now allowing private messages.");
			$ns->svs2mode($nick->nick,"-D");
		}
		else
			$ns->notice($nick->uid,"You are already allowing private messages");

		return;
	}
});

nickserv::func("identify", function($u)
{
	global $ns;
	if (is_meta_private($u['account']))
		$ns->svs2mode($u['nick']->nick,"+D");
});
	
function user_meta_autoprivate(User $nick, String $meta, bool $setting) : bool
{
	$setting = ($setting) ? "on" : "off";
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_account_settings WHERE account = ? AND setting_key = ?");
	$prep->bind_param("ss",$nick->account,$meta);
	$prep->execute();

	$result = $prep->get_result();
	if ($result->num_rows > 0)
	{
		$prep = $conn->prepare("UPDATE dalek_account_settings SET setting_value = ? WHERE account = ? AND setting_key = ?");
		$prep->bind_param("sss",$setting,$nick->account,$meta);
		$prep->execute();
	}
	else
	{
		$prep = $conn->prepare("INSERT INTO dalek_account_settings (account, setting_key, setting_value) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$nick->account,$meta,$setting);
		$prep->execute();
	}

	$prep = $prep = $conn->prepare("SELECT * FROM dalek_account_settings WHERE account = ? AND setting_key = ? AND setting_value = ?");
	$prep->bind_param("sss",$nick->account,$meta,$setting);
	$prep->execute();

	$result = $prep->get_result();
	if ($result->num_rows == 0)
		return false;
	return true;
}

function is_meta_private($account)
{
	$conn = sqlnew();
	$key = "autoprivate";
	$value = "on";
	$prep = $conn->prepare("SELECT * FROM dalek_account_settings WHERE account = ? AND setting_key = ? AND setting_value = ?");
	$prep->bind_param("sss",$account,$key,$value);
	$prep->execute();

	$result = $prep->get_result();
	if ($result->num_rows > 0)
	{
		while ($row = $result->fetch_assoc())
			if ($row = "on")
				return true;
			else
				return false;
	}
	return false;
}


nickserv::func("helplist", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress")
		return;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"AUTOPRIVATE         Automatically disables your private messages when you login.");
	
});

nickserv::func("help", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress")
		return;
	
	if ($u['key'] !== "autoprivate"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: AUTOPRIVATE");
	$ns->notice($nick,"Syntax: /msg $ns->nick AUTOPRIVATE [on|off]");
	$ns->notice($nick,"Example: /msg $ns->nick AUTOPRIVATE on");
});

nickserv::func("helplist", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress")
		return;
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"PRIVATE             Disable/enable PRIVATE (sets/unsets privdeaf +D)");
	
});

nickserv::func("help", function($u){
	
	global $ns,$nickserv;
	if ($nickserv['login_method'] !== "wordpress")
		return;
	
	if ($u['key'] !== "private"){ return; }
	
	$nick = $u['nick'];
	
	$ns->notice($nick,"Command: PRIVATE");
	$ns->notice($nick,"Turns on PRIVATE mode; sets privdeaf (+D) on you");
	$ns->notice($nick,"Syntax: /msg $ns->nick PRIVATE [on|off]");
	$ns->notice($nick,"Example: /msg $ns->nick PRIVATE on");
});