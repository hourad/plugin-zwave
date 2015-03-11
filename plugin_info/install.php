<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function zwave_install() {
	if (config::byKey('jeeNetwork::mode') != 'slave') {
		$cron = cron::byClassAndFunction('zwave', 'pull');
		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass('zwave');
			$cron->setFunction('pull');
			$cron->setEnable(1);
			$cron->setDeamon(1);
			$cron->setSchedule('* * * * *');
			$cron->save();
		}
	}
}

function zwave_update() {
	$cron = cron::byClassAndFunction('zwave', 'pull');
	if (config::byKey('jeeNetwork::mode') != 'slave') {
		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass('zwave');
			$cron->setFunction('pull');
			$cron->setEnable(1);
			$cron->setDeamon(1);
			$cron->setSchedule('* * * * *');
			$cron->save();
		}
		$cron->stop();
	} else {
		if (is_object($cron)) {
			$cron->remove();
		}
	}
	foreach (zwave::byType('zwave') as $zwave) {
		if ($zwave->getConfiguration('serverID') == '') {
			$zwave->setConfiguration('serverID', 1);
		}
		$zwave->save();
	}
	config::save('zwaveAddr1', config::byKey('zwaveAddr', 'zwave'), 'zwave');
	config::save('zwavePort1', config::byKey('zwavePort', 'zwave'), 'zwave');
	config::save('isOpenZwave1', config::byKey('isOpenZwave', 'zwave'), 'zwave');
}

function zwave_remove() {
	$cron = cron::byClassAndFunction('zwave', 'pull');
	if (is_object($cron)) {
		$cron->remove();
	}
}

?>
