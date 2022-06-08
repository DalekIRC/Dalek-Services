<?php

hook::func("SJOIN", function($u)
{
	global $os;
	if ($u['channel'] == "#PossumsOnly")
	{
		$tok = explode(" ",$u['full']);
		if ($tok[5])
			$os->sendraw("SVSMODE #PossumsOnly +ao");
		else
		{
			printf($u['full']);
			$nick = mb_substr($tok[4],1);
			echo $nick;
			$os->mode("#PossumsOnly","+ao $nick $nick");
		}
	}
});