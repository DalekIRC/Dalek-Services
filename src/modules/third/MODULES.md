## Third-party modules go in this folder

Once you've placed your third-party module inside the `src/modules/third` folder,
you can then load the module in a number of ways:

1. From the command line.

	From the Dalek-Services directory type:

	`./dalek module load third/<name_of_module>`

	For example, to load dalek_updates, you would put the file at `src/modules/third/dalek_updates.php` and type:

	`./dalek module load third/dalek_updates`


2. Message OperServ over IRC.

	From IRC, make sure you have /OPER'd and type this:
	
	`/os loadmod third/<name_of_module>`
	
	For example, to load dalek_updates, you would put the file at `src/modules/third/dalek_updates.php` and type:
	
	`/os loadmod third/dalek_updates`
	
	
3. Config.

	Modules that load when services start are specified in `conf/modules.conf`
	
	If you want the module to stay loaded through restarts after using one of the before methods, you should add it in your `modules.conf`.
	
	For example, to load dalek_updates, you would put the file at `src/modules/third/dalek_updates.php` and type:
	
	`loadmodule("third/dalek_updates");`


### Note:

Modules won't be reloaded during a rehash. Rehashing updates your configuration options.
