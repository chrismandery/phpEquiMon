#!/bin/sh
#
# Monitoring script belonging to phpEquiMon.
# Deploy this to the machines you want to monitor and run it regularly (e.g. via /etc/cron.daily/).
#

MONDIR=/mnt/nfsserver/monfiles/  # Fill in monfiles directory here

/bin/uname -a > $MONDIR/`hostname`
/sbin/ifconfig -a > $MONDIR/`hostname`
