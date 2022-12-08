## DalekIRC's IRCv3 Support ##

As a services package, there's not a great deal of things needed, but there are many ideas...

### Login and Registration ###
| Feature | Link | Description of our implementation |
|-|-|-|
| `SASL` (`PLAIN`,`EXTERNAL`) | https://ircv3.net/specs/extensions/sasl-3.2 | You can use your WordPress account details here, as well as register your device for automatic recognition |
| `draft/account-registration` | https://ircv3.net/specs/extensions/account-registration | You can register on WordPress via IRC. Requires the UnrealIRCd module `third/account-registration` |

### Messaging ###
| Feature | Link | Description of our implementation |
|-|-|-|
| `draft/channel-context` | https://ircv3.net/specs/client-tags/channel-context | Message tags containing `draft/channel-context` will be replied to using the same context by service bots. (UnrealIRCd 6.0.4 or later) |
| `msgid` | https://ircv3.net/specs/extensions/message-ids | DalekIRC Services produce its own, unique msgids. |
| `draft/reply` | https://ircv3.net/specs/client-tags/reply | Message tags containing `msgid` will be replied to using `draft/reply` |
| `server-time` | https://ircv3.net/specs/extensions/server-time | DalekIRC will send its own server time in message responses |


