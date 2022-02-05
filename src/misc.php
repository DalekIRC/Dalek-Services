<?php

function MeatballFactory(Channel $chan,$modes,$params,$source)
{
	for ($i = 0; isset($modes[$i]); $i++)
	{
		$chr = $modes[$i];
		
		if ($chr == "+" || $chr == "-")
		{
			$switch = $chr;
			continue;
		}
		$type = cmode_type($chr);
		if ($type == 1 || $type == 2 || $type == 5)
		{
			$par = explode(" ",$params);
			$chan->ProcessMode("$switch $chr ".$par[0],$source);
			$params = rparv($params);
			continue;
		}
		elseif ($type == 3)
		{
			$par = explode(" ",$params);
			
			if ($switch == "+")
			{
				$chan->ProcessMode("$switch $chr ".$par[0],$source);
				$params = rparv($params);
			}
			elseif ($switch == "-")
				$chan->ProcessMode("$switch $chr",$source);
			
			continue;
		}
		elseif ($type == 4)
		{
			$chan->ProcessMode("$switch $chr",$source);				
			continue;
		}
	}
}



function bie($chan,$item)
{
	$tok = explode(",",get_string_between($item,"<",">"));
			
	$timestamp = $tok[0];
	$setby = $tok[1];
	if (is_numeric($tok[1][0]))
	{
		$usr = new User($setby);
		$setby = $usr->nick;
	}
	$item = mb_substr($item,strlen(get_string_between($item,"<",">")) + 2);
	
	$type = $item[0];
	$ext = mb_substr($item,1);
	
	$conn = sqlnew();
	
	$prep = $conn->prepare("INSERT INTO dalek_channel_meta (chan, meta_key, meta_value, meta_setby, meta_timestamp) VALUES (?, ?, ?, ?, ?)");
	
	switch($type)
	{
		case "&":
			$set = "ban";
			break;
			
		case "'":
			$set = "invite";
			break;
		
		case "\"":
			$set = "except";
			break;
	}
	
	$prep->bind_param("sssss",$chan,$set,$ext,$setby,$timestamp);
	$prep->execute();
	$prep->close();
}

function rparv($string)
{
	$parv = explode(" ",$string);
	$first = strlen($parv[0]) + 1;
	$string = substr($string, $first);
	if ($string)
		return $string;
	return false;
}


function IsUser(User $nick)
{
    return $nick->IsUser;
}

function IsOper(User $nick)
{
    if (strpos($nick->usermode,"o") !== false)
        return true;
    return false;
}

function IsServiceBot(User $nick)
{
	if (strpos($nick->usermode,"S") !== false)
		return true;
	return false;
}
