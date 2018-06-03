# TameTheArk management interface.

This package uses xPaw's PHP-Source-Query library, which can be found here: https://github.com/xPaw/PHP-Source-Query/tree/master/SourceQuery and is included for simplicity's sake.

## What is TameTheArk

TameTheArk is a web based UI for managing your Ark: Survival Evolved (video game) services on a single server. It has been tested with Apache. Currently, it requires that the Apache server be run as the user who runs Steam.

## What is required for TameTheArk to operate?

1. Linux: I haven't tested this at all under Windows - if you want to do that, it's entirely up to you.
2. Ark: Survival Evolved set up as a dedicated server.
3. SteamCMD: Command line tool for running Steam games.
4. PHP: A modern implementation of php (PHP 5.5 should do)
5. Apache: The Apache web server.
I think that's it... if it doesn't work for you, let's determine why and add it to the requirements!

## How do I use TameTheArk?

1. Install Ark, SteamCMD, PHP, and Apache on your Linux host.
2. Copy the files from this directory into /opt/tametheark (or another accessible location)
3. Configure sudoers. Example can be found in $ttaroot/res/sudoers.d.ark
4. Configure your service - $ttaroot/arks/[servicename]/conf - copy the example directory if needed.
5. Configure tametheark ($ttaroot/conf/conf.php rename $ttaroot/conf/conf.example and edit)
6. Configure your Apache Virtualhost to point to the $ttaroot/www directory. Set authentication up here.
7. Start Apache.
8. Visit your web UI.

## What works?

Some things!

1. Force your Ark server to save with the Save command.
2. Shut your server down with Shutdown.
3. Start it up with Startup.
4. Update your server (shutdown | update | startup)
5. Maintenance is working, assuming your conf.php is set up correctly.
6. Restart your server if something seems wrong.
7. Clear Op resets the operation flag for your ark server in case another operation gets hung up.

## Okay, so what do you have planned for the future?

Lots of things!
* A real authentication system of some sort... this needs to happen. In the meantime, use htpasswd or a similar tool
* Access control, so you can grant control over individual servers instead of all of them.
* Web based configuration of services.
* UI Clustering (one UI to rule multiple TameTheArk UIs)

## I've got this great idea...

I'm listening!
