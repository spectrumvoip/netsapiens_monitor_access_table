# netsapiens_monitor_access_table

This php script can be run from crontab to monitor Admin accounts added to your NetSapiens system

It will send an if:

1.	A new Admin User Account is added.
2.	An email address is changed on an existing Admin user.
3.	An existing Admin user’s password is changed.
4.	An Admin user is changed from inactive to active.

I run it from /etc/crontab like so:

# Run every minute to check for changes to NetSapiens access table
#* * *  *  * root /usr/local/scripts/monitor_access_table.php 0

Remove the comment (#) from the above line.

When the script runs, it pulls current info and saves it that data to a file in /tmp.  When it runs again, it pulls current info and compares that info to the file created last time it ran

You should also make it executable:
chmod +x /usr/local/scripts/monitor_access_table.php
