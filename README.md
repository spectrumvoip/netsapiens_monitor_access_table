# netsapiens_monitor_access_table

This php script can be run from crontab to monitor Admin accounts added to your NetSapiens system

It will send an if:

1.	A new Admin User Account is added.
2.	An email address is changed on an existing Admin user.
3.	An existing Admin user’s password is changed.
4.	An Admin user is changed from inactive to active.

I run it from /etc/crontab like so:

# Check for changes to access table

#* * *  *  * root /usr/local/scripts/monitor_access_table.php 0

It creates a file in /tmp/ to track differences between the current db and the last time it ran.
