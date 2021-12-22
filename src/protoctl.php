<?php


hook::func("preconnect", function()
{
	global $sql;
	
	$query = "CREATE TABLE IF NOT EXISTS dalek_protoctl_meta (
		id int AUTO_INCREMENT NOT NULL,
		meta_key varchar(255) NOT NULL,
		meta_value varchar(255) NOT NULL,
		PRIMARY KEY(id)
	)";
	$sql::query($query);
	$query = "TRUNCATE TABLE dalek_protoctl_meta";
	$sql::query($query);
	$query = "DELETE FROM dalek_channel_meta WHERE meta_key = 'ban'";
	$sql::query($query);
	$query = "DELETE FROM dalek_channel_meta WHERE meta_key = 'invite'";
	$sql::query($query);
	$query = "DELETE FROM dalek_channel_meta WHERE meta_key = 'except'";
	$sql::query($query);
});
	


hook::func("raw", function($u)
{
	
	global $sqlip,$sqluser,$sqlpass,$sqldb;
	
	$parv = explode(" ",$u['string']);
	
	/* Storing our protoctl info */
	if ($parv[0] !== "PROTOCTL")
		return;
	
	$conn = mysqli_connect($sqlip,$sqluser,$sqlpass,$sqldb);
	if (!$conn) { return false; }
	
	for ($i = 1; isset($parv[$i]); $i++)
	{
		$tok = explode("=",$parv[$i]);
		
		$key = $tok[0];
		$val = $tok[1] ?? false;
		
		$prep = $conn->prepare("INSERT INTO dalek_protoctl_meta (meta_key, meta_value) VALUES (?, ?)");
		
		/* Remembering which type each CHANMODE is  according to https://modern.ircdocs.horse/#mode-message */
		if ($key == "CHANMODES")
		{
			$modetok = explode(",",$val);
			
			
			for ($s = 0; isset($modetok[$s]); $s++)
			{
				$num = $s + 1;
				$fkey = "CHANMODES_TYPE".$num;
				$prep->bind_param("ss",$fkey,$modetok[$s]);
				$prep->execute();
			}
		}
		
		if ($key == "USERMODES")
		{
			$prep->bind_param("ss",$key,$val);
			$prep->execute();
		}
		
		if ($key == "PREFIX")
		{
			$mode = get_string_between($val,"(",")");
			$num = "-".strlen($mode);
			$prefix = substr($val,$num);
			$all = $mode.",".$prefix;
			$prep->bind_param("ss",$key,$all);
			$prep->execute();
		}
		$prep->close();
	}
});



hook::func("raw", function($u)
{
	$parv = explode(" ",$u['string']);
	
	if ($parv[1] !== "MODE")
		return;
	
	$dest = $parv[2];
	$chan = new Channel($dest);
	
	$modes = $parv[3];
	
	$toAdd = array();
	$toDel = array();
	
	$sp = NULL;
	
	$params = str_replace($parv[0]." ".$parv[1]." ".$parv[2]." ".$parv[3]." ","",$u['string']);
		
	MeatballFactory($chan,$modes,$params,mb_substr($parv[0],1));
});

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

function rparv($string)
{
	$parv = explode(" ",$string);
	$first = strlen($parv[0]) + 1;
	$string = substr($string, $first);
	if ($string)
		return $string;
	return false;
}
