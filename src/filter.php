<?php


class fltr {

    static $filters = array();

	public static function func($filter, $function)
    {
		self::$filters[$filter]['func'] = $function;
	}
    public static function setnick($filter, $nick)
    {
        self::$filters[$filter]['nick'] = $nick;
    }
    public static function setmod($filter, $mod)
    {
        self::$filters[$filter]['mod'] = $mod;
    }
	public static function del($filter, $function)
    {
		foreach (self::$filters[$filter] as $c)
		    if ($c['func'] == $function)
            {
                self::$filters[$filter] = NULL;
                unset(self::$filters[$filter]);
            }
	}
}

function filter_this($nick, $string)
{
    foreach (fltr::$filters as $filter)
    {
        if ($filter['nick'] == $nick)
        {
            $f = $filter['func'];
            $string = $f($string);
        }
    }
    return $string;
}

class Filter {

    function __construct($modname,$filter,$func,$nick)
    {
        if ($this->filter_exists($filter))
        {
            $this->success = false;
        }
        else $this->register_new_filter($modname,$filter,$func,$nick);
    }
    function filter_exists($filter)
    {
        if (in_array($filter,fltr::$commands))
            return true;
        return false;
    }
    function register_new_filter($modname,$filter,$func,$nick)
    {
        global $commands;
        fltr::func(strtolower($filter),$func);
        fltr::setnick(strtolower($filter),$nick);
        fltr::setmod(strtolower($filter),$modname);
        $this->success = true;
    }
}


function FilterAdd($modname, $filter, $func, $nick) : bool
{
    if (!module_exists($modname))
    {
        return false;
    }

    $filter = new Filter($modname, $filter, $func, $nick);
    if (!$cmd->success)
        return false;

    return true;
}