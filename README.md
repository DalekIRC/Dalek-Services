# <div align="center"><img width="50" height="35" src="https://i.imgur.com/w6cScKo.png"> DalekIRC Services</div>
<div align="center">

[![Version](https://img.shields.io/badge/Extermin-8-red.svg)]()
[![Version](https://img.shields.io/badge/Version-0.1_beta-blue.svg)]()
[![Maintained](https://img.shields.io/badge/Maintained-yes-darkgreen.svg)]()
[![Unreal](https://img.shields.io/badge/UnrealIRCd-6.0.4_or_later-darkgreen.svg)](https://unrealircd.org)
[![WP](https://img.shields.io/badge/WordPress-6.0_or_later-darkgreen.svg)](https://wordpress.com)
<a href="https://github.com/DalekIRC/Dalek-Services/actions/workflows/irctest.yml">
        <img alt="Validation by irctest" src="https://github.com/DalekIRC/Dalek-Services/actions/workflows/irctest.yml/badge.svg" />


DalekIRC Services with UnrealIRCd & WordPress integration tailored to you.<br><br>
</div>

### Why do I need Dalek? ###
* DalekIRC is a set of IRC Services with the best WordPress integration AND the best UnrealIRCd integration on the market.
* [WordPress](https://github.com/wordpress/wordpress) is an open-source Content Management System (CMS) [which makes up 43% of all websites](https://w3techs.com/technologies/details/cm-wordpress)
* [UnrealIRCd](https://github.com/unrealircd/unrealircd) is the most widely deployed Internet Relay Chat daemon (IRCd), [with a market share of 38.6% as of December 2021.](https://www.ircstats.org/servers)

With WordPress + Dalek + UnrealIRCd, you have more creative control over things such as:
  * How your users register and manage their profile:
	- Works with [Ultimate Member](https://ultimatemember.com/), customise how your profiles look
	- Create your own registration options like Date of Birth, Gender ID, Location etc. in WordPress, and these will be reflected in chat.
	- Profile pictures are shown on IRC to clients who support [`METADATA`](https://github.com/ircv3/ircv3-specifications/blob/7c76d2022992d4f9ce088420a861f185169965a2/extensions/metadata.md)
  * Confirmation emails, account deletion, bans and suspensions are all do-able from your WordPress dashboard.
  * Add and remove Services staff via website, simply by adding or revoking their permission in the WordPress `Users` tab. You can oper them from the WordPress dashboard.
  * With our WordPress plugin, you have an overview of all the users, channels, network bans, servers. Additionally, you can oper staff on IRC from the website, rehash servers, remove bans, WHOIS users, WHOIS IPs, and more.
  
## Planned Services ##

- [x] NickServ
- [x] ChanServ
- [x] OperServ
- [x] BotServ
- [x] Global
- [ ] MetaServ (HostServ replacement with extra features)
- [x] bbServ (Optional: bbForums notification bot)

<i>Although DalekIRC currently uses bots (NickServ, ChanServ etc), the ball is rolling to move things to a more "server-side command" environment, eliminating need to message a bot to ask what you need.</i>
### Knows how to talk with ###
- [x] WordPress
- [x] JSON-RPC (remote procedure calls)
- [x] UnrealIRCd
- [x] SQL Databases
- [x] You!


## IRCv3 ##
DalekIRC has a keen interest in the advancement of IRC specifically, and so aims to add as many IRCv3 features as is workable from a services point of view, as well as suggest a few things in return.

To learn more about IRCv3, what it means, and how it's used, [check out their website](https://ircv3.net).

To learn more about how DalekIRC uses IRCv3, [check out the support table](IRCv3.md)
	
<div align="center">
	
### <a href="https://github.com/unrealircd/unrealircd/"><img width="210" height="50" src="https://i.ibb.co/dB6H5Zq/Screenshot-from-2022-09-26-00-20-15.png"></a><a href="https://ircv3.net/"><img width="160" height="35" src="https://d33wubrfki0l68.cloudfront.net/27a59ae6bb716a8d8aa13ab8abdd2933ade16546/0a308/img/logo-forwhite.svg"></a><a href="https://github.com/wordpress/wordpress/"><img width="210" height="50" src="https://i.ibb.co/0c5NpSV/Word-Press-Logo-2003-2008.png"></a></div>
