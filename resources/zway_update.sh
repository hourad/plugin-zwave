#!/bin/sh
if [ -z "$1" ]; then
	wget -q -O - razberry.z-wave.me/install zway-install
else
	wget -q -O - razberry.z-wave.me/install/$1 zway-install
fi

for i in mongoose zbw_connect
do
	if [ -f "/etc/init.d/${i}" ]; then
		service ${i} stop
		update-rc.d -f ${i} remove
	fi
done
ps aux | grep mongoose | awk '{print $2}' | xargs kill -9
ps aux | grep zbw_connect | awk '{print $2}' | xargs kill -9 