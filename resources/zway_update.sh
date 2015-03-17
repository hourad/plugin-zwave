#!/bin/sh
echo 'Start install/update of zway'
if [ ! -e /etc/z-way/box_type ]; then
	mkdir /etc/z-way/ 
	touch /etc/z-way/box_type
fi

if [ ! -e /usr/lib/arm-linux-gnueabihf/libarchive.so.12 ]; then
	if [ -e /usr/lib/arm-linux-gnueabihf/libarchive.so.13 ]; then
		ln -s /usr/lib/arm-linux-gnueabihf/libarchive.so.13 /usr/lib/arm-linux-gnueabihf/libarchive.so.12
	fi
fi

if [ -e /etc/systemd/system/getty.target.wants/getty@ttymxc0.service ]; then
	systemctl mask serial-getty@ttymxc0.service
fi

if [ -z "$1" ]; then
	wget -q -O - razberry.z-wave.me/install | sudo bash
else
	wget -q -O - razberry.z-wave.me/install/$1 | sudo bash
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

echo "INSTALL/UPDATE OF Z-WAY-SERVER SUCCESSFULL"