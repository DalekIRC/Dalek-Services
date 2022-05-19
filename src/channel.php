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
			$this->modes = mb_substr($u['modes'],1) ?? false;
			$this->topic = $u['topic'];
			$this->timestamp = $u['timestamp'];
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
	
	function UserHasMode($user,$mode)
	{
		$user = new User($user);
		$chanlist = get_ison($user->uid);
		if (empty($chanlist))
			return false;
		for ($i = 0; isset($chanlist['list'][$i]); $i++)
		{
			if ($var = strpos($chanlist['mode'][$i],$mode) !== false && $chanlist['list'][$i] == $this->chan)
			 {
				return true;
			}
			continue;
		}
		return false;
	}
	
	function IsOp($user)
	{
		if ($var = $this->UserHasMode($user,"o"))
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
	
	function SetMode($mode)
	{
		global $cf;
		S2S("MODE $this->chan $mode",$cf['servicesname']);
		$tok = explode(" ",$mode);
		if (isset($tok[1]))
		{
			$params = rparv($mode);
			MeatballFactory($this,$tok[0],$params,$cf['servicesname']);
		}
		else
			MeatballFactory($this,$mode,"",$cf['servicesname']);
			
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
					$prep = $conn->prepare("DELETE FROM dalek_channel_meta WHERE chan = ? AND meta_key = ? AND meta_value = ?");
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
	}
	
	function CheckReg()
	{
		$conn = sqlnew();
		if (!$conn) { return false; }
		
		$prep = $conn->prepare("SELECT * FROM dalek_chaninfo WHERE channel = ?");
		$prep->bind_param("s",$this->chan);
		$prep->execute();
		$result = $prep->get_result();
		
		if ($result->num_rows == 0)
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
		$prep = $conn->prepare("SELECT * FROM dalek_chanaccess WHERE channel = ?");
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
		$prep = $conn->prepare("SELECT * FROM dalek_ison WHERE chan = ?");
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
	
	$result = $conn->query("SELECT * FROM dalek_protoctl_meta WHERE meta_key LIKE 'CHANMODES_TYPE%'");
	
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