<?php

/* TO-DO */
class Filter
{
	static $filter_list = [];

	static function Add(String $mod, Client $client, $to_find, $to_replace_with) : void
	{
		if ($mod && !module_exists($mod))
		{
			SVSLog(LOG_WARN."Filter is trying to attach to a module which is not yet loaded. Maybe the module is loading things in the wrong order? Aborting.");
            return;
		}
		$filter = [];
		$filter['modname'] = $mod;
		$filter['nick'] = $client->nick;
		$filter['needle'] = $to_find;
		$filter['replace'] = $to_replace_with;
		self::$filter_list[] = $filter;
	}
	static function Del($modname,$nick)
	{
		foreach(self::$filter_list as $i => $filter)
			if (!strcasecmp($filter['modname'],$modname) && (!strcasecmp($nick,$filter['nick']) || $nick == "*"))
			{
				self::$filter_list[$i] = NULL;
				self::$filter_list = array_values(self::$filter_list);
			}
	}
	/* 
	 * Filters a given string
	 */
	static function String($nick,&$mtags = NULL,&$haystack) : void
	{
		foreach(self::$filter_list as $filter)
        {
            if (!$filter)
                return;

			if (!strcasecmp($nick,$filter['nick']))
				$haystack = str_replace($filter['needle'],$filter['replace'],$haystack);
        }
	}
	/* 
	 * Filters a given array full of strings
	 */
	static function StringArray($nick,&$mtags,&$array_of_haystacks) : void
	{
		foreach ($array_of_haystacks as &$haystack)
			self::String($nick,$mtags,$haystack);

	}
}