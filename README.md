

[![CodeBabes](https://forthebadge.com/images/badges/built-by-codebabes.svg)]()
[![SpagYetti](https://forthebadge.com/images/badges/contains-tasty-spaghetti-code.svg)]()
[![catsuwu](https://forthebadge.com/images/badges/contains-cat-gifs.svg)]()
  <img width="50" height="35" src="https://i.imgur.com/w6cScKo.png">
  
[![Version](https://img.shields.io/badge/Extermin-8-red.svg)]()
[![Version](https://img.shields.io/badge/Beta-0.1-blue.svg)]()
[![Version](https://img.shields.io/badge/Status-Work%20In%20Progress-cyan.svg)]()
[![Maintained](https://img.shields.io/badge/Maintained-yes-green.svg)]()
[![Maintainer](https://img.shields.io/badge/Maintainer-Valware-purple.svg)](https://github.com/ValwareIRC/)
[![Unreal](https://img.shields.io/badge/UnrealIRCd-6.0+-green.svg)](https://unrealircd.org)
[![WP](https://img.shields.io/badge/WordPress-6.0+-green.svg)](https://wordpress.com)

# Dalek IRC Services

Dalek IRC Services with WordPress integration tailored to you.


### Why do I need Dalek? ###
* Dalek is a set of IRC Services with the best WordPress integration AND the best UnrealIRCd integration on the market.
* [WordPress](https://github.com/wordpress/wordpress) is an open-source Content Management System (CMS) [which makes up 43% of all websites](https://w3techs.com/technologies/details/cm-wordpress)
* [UnrealIRCd](https://github.com/unrealircd/unrealircd) is the most widely deployed Internet Relay Chat daemon (IRCd), [with a market share of 38.6% as of December 2021.](https://www.ircstats.org/servers)

With WordPress + Dalek + UnrealIRCd, you have more creative control over things such as:
  * How your users register and manage their profile:
    - Works with [Ultimate Member](https://ultimatemember.com/), customise how your profiles look
    - Create your own registration options like Date of Birth, Gender ID, Location etc. in WordPress, and these will be reflected in chat.
    - Profile pictures are shown on IRC to clients who support [`METADATA`](https://github.com/ircv3/ircv3-specifications/blob/7c76d2022992d4f9ce088420a861f185169965a2/extensions/metadata.md)
  * Confirmation emails, account deletion, bans and suspensions are all do-able from your WordPress dashboard.
  * Add and remove Services staff via website, simply by adding or revoking their permission in the WordPress `Users` tab.
  
## Planned Pseudoservices ##

- [x] NickServ
- [x] ChanServ
- [x] OperServ
- [x] BotServ
- [x] Global
- [ ] Inbox (MemoServ replacement with extra features)
- [ ] MetaServ (HostServ replacement with extra features)
- [x] bbServ (Optional: bbForums notification bot)

### Knows how to talk with ###
- [x] WordPress
- [x] JSON-RPC (remote procedure calls)
- [x] UnrealIRCd
- [x] SQL Databases
- [x] You!

__NickServ__

As always, you can use NickServ to manage your account settings. These services support `SASL` and `draft/account-registration`, and so registering your account directly over IRC is also possible. The only main differences are you can't register an account by messaging NickServ, and the `IDENTIFY` command takes one of `PLAIN` or `EXTERNAL` based on however you have your client set up, and it will take this to mean that you would like to start/continue a SASL flow.

__ChanServ__

As always, you can use ChanServ to register and manage your channels, give op to people and whatnot.

__OperServ__

As always, you can use OperServ to manage opery things.

__BotServ__
As always, you can create and assign special bots in place of ChanServ in a channel.

__Global__

Global noticer

__Inbox__

Yes, I know an inbox isn't an outbox! Even though this provides outbox features, it's just how it is. Deal with it.

__MetaServ__

MetaServ will replace HostServ, and the reason for the name change is because it'll let you request and set more than just vHosts (swhois and other things)

## Requirements ##
- UnrealIRCd 6.0 or later
- WordPress 6.0 or later
