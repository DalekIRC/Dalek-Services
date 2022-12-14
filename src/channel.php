<?php
/*
 *	(C) 2021 Dalek IRC Services
 *
 *	GNU GENERAL PUBLIC LICENSE v3
 *
 *
 *	Author: Valware
 * 
 *	Description: Channel
 *
 * 
 *	Version: 1
*/

class Channel
{
	public $chan = NULL;
	public $IsChan = false;
	public $owner = false;
	public $modes = false;
	public $topic = false;
	public $timestamp = 0;
	public $userlist = NULL;
	public $IsReg = false;
	public $RegDate = 0;
	public $url = NULL;
	public $email = NULL;
	/** The access list */
	public $access = array();
	function __construct($chan)
	{
		$u = find_channel($chan);
		$this->chan = $chan;
		$this->CheckReg();
		if (!$u)
		{
			$this->IsChan = false;
		}
		else
		{
			$this->IsChan = true;
			$this->owner = isset($u['owner']) ? $u['owner'] : NULL;
			$this->modes = mb_substr($u['modes'],1) ?? false;
			$this->topic = $u['topic'];
			$this->timestamp = $u['timestamp'];
			$this->userlist = $this->get_user_list();
		}
	}
	
	function HasMode($s)
	{
		if (strpos($this->modes,$s))
			return true;
		return false;
	}
	
	function HasUser($user)
	{
		$user = new User($user);
		if (!$user->IsUser)
		{
			return;
		}
		$chanlist = get_ison($user->uid);
		if (empty($chanlist))
			return false;
		foreach ($chanlist['list'] as $chan)
		{
			if ($chan == $this->chan)
				return true;
		}
		return false;
	}
	function get_user_list()
	{
		$userlist = [];
		$conn = sqlnew();
		$result = $conn->query("SELECT * FROM ".sqlprefix()."ison WHERE chan = lower('$this->chan')");
		if (!$result)
			return $userlist;
		while($row = $result->fetch_assoc())
			$userlist[] = $user = new User($row['nick']);
		return $userlist;
		
	}
	function UserHasMode(User $user,$mode)
	{
		$chanlist = get_ison($user->uid);
		if (empty($chanlist))
			return false;
		for ($i = 0; isset($chanlist['list'][$i]); $i++)
		{
			if (strpos($chanlist['mode'][$i],$mode) !== false && $chanlist['list'][$i] == $this->chan)
			 {
				return true;
			}
			continue;
		}
		return false;
	}
	
	function IsOp($user)
	{
		if ($this->UserHasMode($user,"o"))
		{
			return true;
		}
		return false;
	}
	function IsHalfop($user)
	{
		if ($this->UserHasMode($user,"h"))
		{
			return true;
		}
		return false;
	}
	
	function IsVoice($user)
	{
		if ($this->UserHasMode($user,"v"))
			return true;
		return false;
	}
	function IsOwner($user)
	{
		if ($this->UserHasMode($user,"q"))
		{
			return true;
		}
		return false;
	}
	
	function IsAdmin($user)
	{
		if ($this->UserHasMode($user,"a"))
			return true;
		return false;
	}
	function SetMode($mode)
	{
		S2S("MODE $this->chan $mode");
		$tok = explode(" ",$mode);
		if (isset($tok[1]))
		{
			$params = rparv($mode);
			MeatballFactory($this,$tok[0],$params,Conf::$settings['info']['services-name']);
		}
		else
			MeatballFactory($this,$mode,"",Conf::$settings['info']['services-name']);
			
		return true;
	}
	
	// Syntax: -/+ char [param]
	function ProcessMode($string,$source)
	{
		global $sql,$servertime;
		
		$parv = explode(" ",$string);
		$switch = $parv[0];
		$chr = $parv[1];
		$param = (isset($parv[2])) ? $parv[2] : false;
		
		$type = cmode_type($chr);
		if ($type == 1 || $type == 5)
		{
			if ($chr == "q" || $chr == "a" || $chr == "o" || $chr == "h" || $chr == "v")
			{
				if ($switch == "+")
				{
					$sql->add_userchmode($this->chan,$param,$chr);
				}
				elseif ($switch == "-")
				{
					$sql->del_userchmode($this->chan,$param,$chr);
				}
				unset($source);
			}
			elseif ($chr == "b" || $chr == "I" || $chr = "e")
			{
				if ($switch == "+")
				{
					switch($chr)
					{
						case "b":
							$type = "&";
							break;
						
						case "I":
							$type = "'";
							break;
						
						case "e";
							$type = "\"";
							break;
					}
					bie($this->chan,"<".$servertime.",".$source.">".$type.$param);
				}
				if ($switch == "-")
				{
					switch($chr)
					{
						case "b":
							$type = "ban";
							break;
						
						case "I":
							$type = "invite";
							break;
						
						case "e";
							$type = "except";
							break;
					}
					$conn = sqlnew();
					$prep = $conn->prepare("DELETE FROM ".sqlprefix()."channel_meta WHERE chan = ? AND meta_key = ? AND meta_value = ?");
					$prep->bind_param("sss",$this->chan,$type,$param);
					$prep->execute();
					$prep->close();
				}
			}
		}
		if ($type >= 2 && $type <= 4)
		{
			$sql->update_chmode($this->chan,$switch,$chr);
		}
		$hook['channel'] = $this;
		$hook['switch'] = $switch;
		$hook['char'] = $chr;
		$hook['param'] = $param;
		hook::run(HOOKTYPE_CHANNELMODE_PARAM, $hook);
	}
	
	
	function CheckReg()
	{
		$conn = sqlnew();
		if (!$conn) { return false; }
		
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."chaninfo WHERE channel = ?");
		$prep->bind_param("s",$this->chan);
		$prep->execute();
		$result = $prep->get_result();
		
		if (!$result || !$result->num_rows)
			$this->IsReg = false;
		else
		{
			$this->IsReg = true;
			while ($row = $result->fetch_assoc())
			{
				$this->RegDate = $row['regdate'];
				$this->owner = $row['owner'];
				$this->url = $row['url'] ?? false;
				$this->email = $row['email'] ?? false;
			}
		}
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."chanaccess WHERE channel = ?");
		$prep->bind_param("s",$this->chan);
		$prep->execute();
		$result = $prep->get_result();
		$access_list = array();
		if ($result->num_rows > 0);
			while ($row = $result->fetch_assoc())
				$access_list[$row['nick']] = $row['access'];
		
		$this->access = $access_list;
	}
	function IsEmpty()
	{
		if (!($conn = sqlnew()))
			return;
		$prep = $conn->prepare("SELECT * FROM ".sqlprefix()."ison WHERE chan = ?");
		$prep->bind_param("s",$this->chan);
		$prep->execute();
		$result = $prep->get_result();
		if ($result->num_rows == 0)
			return true;
		return false;
	}
	
}


function cmode_type($chr)
{
	if (strlen($chr) !== 1)
		return false;
	$conn = sqlnew();
	if (!$conn) { return false; }
	
	$type = NULL;
	
	$result = $conn->query("SELECT * FROM ".sqlprefix()."protoctl_meta WHERE meta_key LIKE 'CHANMODES_TYPE%'");
	
	while ($row = $result->fetch_assoc())
	{
		if (strpos($row['meta_value'],$chr) > -1)
		{

			$type = $row['meta_key'][-1];
		}
	}
	if (!$type)
		if ($chr == "o" || $chr == "h" || $chr == "v" || $chr == "a" || $chr == "q")
			$type = 5;
		
	$result->close();
	
	if (!$type)
		return false;
	
	return $type;
}

function channel_list() : array
{
	$conn = sqlnew();
	$result = $conn->query("SELECT * FROM ".sqlprefix()."channels");
	if (!$result)
		return [];
	
	$chanlist = [];
	while($row = $result->fetch_assoc())
		$chanlist[] = new Channel($row['channel']);

	return $chanlist;
}

function get_channel_setting(Channel $channel, String $setting)
{
	$conn = sqlnew();
	$chan = strtolower($channel->chan);
	$prep = $conn->prepare("SELECT * FROM " . sqlprefix() . "channel_settings WHERE LOWER(channel) = ? AND setting_key = ? LIMIT 1");
	$prep->bind_param("ss", $chan, $setting);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || !$result->num_rows)
		return false;

	else while ($row = $result->fetch_assoc())
		return $row['setting_value']; // return the first result
}

function set_channel_setting(Channel $channel, String $setting, String $value = NULL)
{
	$conn = sqlnew();
	$chan = strtolower($channel->chan);
	$prep = $conn->prepare("SELECT * FROM " . sqlprefix() . "channel_settings WHERE LOWER(channel) = ? AND setting_key = ? LIMIT 1");
	$prep->bind_param("ss", $chan, $setting);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || !$result->num_rows)
	{
		if (!$value)
			return;

		$prep = $conn->prepare("INSERT INTO " . sqlprefix(). "channel_settings (channel, setting_key, setting_value) VALUES (?, ?, ?)");
		$prep->bind_param("sss", $chan, $setting, $value);
		$prep->execute();
	}
	elseif ($value)
	{
		$prep = $conn->prepare("UPDATE " . sqlprefix(). "channel_settings SET setting_value = ? WHERE channel = ? AND setting_key = ?");
		$prep->bind_param("sss", $value, $chan, $setting);
		$prep->execute();
	}
	else
	{
		$prep = $conn->prepare("DELETE FROM " . sqlprefix(). "channel_settings WHERE channel = ? AND setting_key = ?");
		$prep->bind_param("ss", $chan, $setting);
		$prep->execute();
	}
}