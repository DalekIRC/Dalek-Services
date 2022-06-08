<?php

/* TO-DO */
class MessageFilter
{
    static $filter_list = [];

    function __construct(String $mod, Client $client, String &$to_find, String $to_replace_with)
    {
        if (!module_exists($mod))
        {
            SVSLog(LOG_WARN."MessageFilter is trying to attach to a module which is not yet loaded. Maybe the module is loading things in the wrong order? Aborting.");
            return;
        }
        
    }
}