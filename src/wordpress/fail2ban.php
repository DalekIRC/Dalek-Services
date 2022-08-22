<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//		dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: fail2ban
//	
\\	Desc: Bans an IP after they fail authentication a number of times
//	
\\	
//	Syntax: none
\\	
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/

/* $ip = IP
 * $s = 1 or 0
 * 
 * 1 = user failed auth, add a tally
 * 0 = user authed correctly, remove data
 */

function fail2ban($ip, int $s)
{
    global $cf,$servertime,$fail2ban,$saslignore;

    $sn = $cf['servicesname'];
    if (!isset($saslignore))
        $saslignore = array();

    if (!isset($fail2ban))
        $fail2ban = array();

    /* remove */
    if ($s == 0)
    {
        $fail2ban[$ip] = NULL;
        unset($fail2ban[$ip]);
        return false;
    }
    if (!isset($fail2ban[$ip]))
        $fail2ban[$ip] = 1;

    else $fail2ban[$ip]++;

    if (!isset($cf['fail2ban']) || $cf['fail2ban'] !== true)
    {
        return;
    }
    $cb = (isset($cf['fail2ban-failcount'])) ? $cf['fail2ban-failcount'] : 10; // default value is 10 incorrect attempts
    $db = (isset($cf['fail2ban-bantime'])) ? $cf['fail2ban-bantime'] : 30; // default value is 30 minutes ban
    $duration = $db * 60; // we want the seconds
    $expiry = $servertime + $duration;

    if ($fail2ban[$ip] >= $cb)
        S2S("TKL + Z * $ip $sn $expiry $servertime :Too many bad auth attempts. [".$db."m]");
}