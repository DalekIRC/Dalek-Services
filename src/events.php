<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\	Title:		Events
//				
\\	Desc:		Check our events schedule
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

// define
define('EVENT_MIN_REPETITIONS', 1); // can only do a minimum of once per second
define('EVENT_MAX_REPETITIONS', 10000); // limits to ten thousand. if you want it persistently, use 0
define('EVENT_MIN_INTERVAL', 1); // can only do a minimum of once per second
define('EVENT_MAX_INTERVAL', 31556926); // limits to no bigger than one year interval
define('LOG_EVENT', '[EVENT] ');


class Events
{
	static $list = [];

	static function CheckForNew()
	{
		// anti-flood mechanism
		if (!self::time_keeper()) // if the time-keeper says it's not ok
			return;

		if (empty(Events::$list)) // Nope, sorry, nothing.
			return;

		$l = &Events::$list;
		foreach($l as &$ee)
		{
			if (empty($ee))
				continue;
			$i = 0;
			foreach ($ee as $timestamp => &$event)
			{
                
				if ($timestamp <= servertime()) // come, Event... it is time...
				{
                    $i = array_search($event, array_keys(Events::$list)); // find our offset
                    
					if (IsDebugMode()) // let the debugger know ;D
                        DebugLog("Event triggered: ".$event['modname'], LOG_EVENT);

					$func = ($event['modname']) ? [0 => $event['modname'], 1 => $event['function']] : $event['function'];
					$params = $event['params'];
					if ($params)
					{
						call_user_func_array($func,$params);
					}
					else
					{
						call_user_func($func, null);
					}				
					if ($event['repetitions'] > EVENT_MIN_REPETITIONS) // if they are bigger than 1, decrement
					{
						$event['repetitions'];
						$event_i--;
					}

					elseif ($event['repetitions'] == EVENT_MIN_REPETITIONS) // looks we just ran the last one! good job guys you can go home early today
					{
                        array_splice(Events::$list, $i);
                        return;
                    }

					if ($event['interval']) // bump the timestamp to the next interval
					{
						Events::$list[servertime() + $event['interval']] = $event;
                        array_splice(Events::$list, $i);
                    }
				}
			}
		}
	}
	/* @param   int		 $ctime		  int or String of numbers of UNIX time for when this should trigger.
	 * @param   int		 $repetitions	How many times you want to run the event. NULL if none. 0 if you want to run it infinitely.
	 * @param   String	  $interval	   How much time to wait between each event, NULL if none. 
	 * @param   String	  $function	   The function you want to reference in your module to run. NULL if none.
	 * @param   Array	   $params		 The parameters you want associated with this
	 * @param   String	  $modname		The module handle associated with this event, so we can remove it when the module gets unloaded.
	 * 
	 * @return  bool					returns true if the event was successfully added, false if it were not.
	 * Either one of the NULL-able params must be present. You cannot use neither to create an empty timer.
	 */
	static function Add(int $ctime, int $repetitions = NULL, int $interval = NULL, String $function = NULL, Array $params = NULL, &$error, String $modname = NULL) : bool
	{
		if (!isset($event))
			$event = []; // event arrae

		if (!$function) // if function is null return false;
		{
			$error = "no function fam lol what you even doing\n";
		}
		elseif ($ctime && !is_numeric($ctime)) // if it's an invalid unix time
		{
			$error = "invalid unix time lol: $ctime\n";
		}
		elseif ($ctime && $ctime < servertime()) // if the thing is asking to put a timer for something in the past?
		{
			$error = "trying to change things in the past, not even Doctor Who could do that..\nour time = ".servertime()."\their time $ctime\n";
		}
		elseif ($repetitions && !is_numeric($repetitions)) // repetitions was not a number
		{
			$error = "repetitions you chose was not a number: $repetitions\n";
		}

		if ($interval)
		{
			if (!is_numeric($interval))
				$error = "not an actual numeric: $interval";

			elseif ($interval < EVENT_MIN_INTERVAL && $interval != "0") // repetitions was not a number or was too small
			{
				$error = "not a valid interval numeric: $interval";
			}
			elseif ($repetitions != "0") // needs repetition to use interval... lol
				$error = "not a valid repetition numeric: $repetitions";
		}
		if (!BadPtr($error))
		{
			SVSLog("Unable to add event from module: ".$modname ?? "none", LOG_EVENT);
			SVSLog($error, LOG_EVENT);
			return false;
		}
        $event[$ctime]['function'] = $function; // we should have $function or we scrood, but we returned earlier so it's okay =]
		$event[$ctime]['interval'] = $interval; // de interval
		$event[$ctime]['repetitions'] = $repetitions; // add repetitions if we got any   
		$event[$ctime]['modname'] = $modname; // assign the module name to it if there were any module.
		$event[$ctime]['params'] = $params; // An array of params to be called to it...
		
		if (empty($event)) // shouldn't happen but might as well be safe
			return false;
		SVSLog("Added event from module $modname to list", LOG_EVENT);
		Events::$list[] = $event; // add it to the list \o/ 
		return true;
	}

	static $timekeep = 0;
	// anti-spam mechanism to protect the CheckForNew() function from being run more than once per second
	static function time_keeper()
	{
		$time = servertime();
		if (self::$timekeep == $time)
			return false;
		else
		{
			self::$timekeep = $time;
			return true;
		}
	}
}
