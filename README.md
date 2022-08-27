
[![CodeBabes](https://forthebadge.com/images/badges/built-by-codebabes.svg)]()
[![SpagYetti](https://forthebadge.com/images/badges/contains-tasty-spaghetti-code.svg)]()
[![catsuwu](https://forthebadge.com/images/badges/contains-cat-gifs.svg)]()

[![Version](https://img.shields.io/badge/Beta-0.1-blue.svg)]()
[![Maintained](https://img.shields.io/badge/Maintained-yes-green.svg)]()
[![Maintainer](https://img.shields.io/badge/Maintainer-Valware-blue.svg)](https://github.com/ValwareIRC/)
[![Unreal](https://img.shields.io/badge/UnrealIRCd-6.0+-blue.svg)]()
[![Unreal](https://img.shields.io/badge/WordPress-6.0+-blue.svg)]()
# IRCServices (Work In Progress)
Dalek IRC Services with WordPress integration tailored to you.

### Integral methodology ###
- [x] WordPress integration
- [x] JSON-RPC API

### Brief Overview ###


Planned Pseudoservices

- [x] NickServ
- [x] ChanServ
- [x] OperServ
- [x] BotServ
- [x] Global
- [ ] Inbox (MemoServ replacement with extra features)
- [ ] MetaServ (HostServ replacement with extra features)


### NickServ ###
As always, you can use NickServ to manage your account settings. These services support `SASL` and `draft/account-registration`, and so registering your account directly over IRC is also possible. The only main differences are you can't register an account by messaging NickServ, and the `IDENTIFY` command takes one of `PLAIN` or `EXTERNAL` based on however you have your client set up, and it will take this to mean that you would like to start/continue a SASL flow.

### ChanServ ###
As always, you can use ChanServ to register and manage your channels, give op to people and whatnot.

### OperServ ###
As always, you can use OperServ to manage opery things.

### BotServ ###
As always, you can create and assign special bots in place of ChanServ in a channel.

### Global ###
Global noticer

### Inbox ###
Yes, I know an inbox isn't an outbox! Even though this provides outbox features, it's just how it is. Deal with it.

### MetaServ ###
MetaServ will replace HostServ, and the reason for the name change is because it'll let you request and set more than just vHosts (swhois and other things)

## Requirements ##
- UnrealIRCd 6.0 or later
- WordPress 6.0 or later
