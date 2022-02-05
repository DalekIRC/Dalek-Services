<?php

hook::func("join", function($u)
{
	global $cs;

	$chan = new Channel($u['chan']);

	if (!isset($chan->owner))
		return;
	if (!isset($chan->modes))
		return;
	elseif (strpos($chan->modes,"r") == false)
		$cs->mode($chan->chan,"+r");
});

hook::func("SJOIN", function($u)
{
	global $cs;

	$chan = new Channel($u['channel']);

	if (!isset($chan->owner))
		return;
	elseif (strpos($chan->modes,"r") == false)
		$cs->mode($chan->chan,"+r");
});

