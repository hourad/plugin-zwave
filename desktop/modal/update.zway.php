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

if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$localZwayServer = false;
foreach (zwave::listServerZway() as $id => $server) {
	if (($server['addr'] == '127.0.0.1' || $server['addr'] == 'localhost') && $server['isOpenZwave'] != 1) {
		$localZwayServer = true;
	}
}
if (!$localZwayServer) {
	throw new Exception(__('Le serveur z-way n\'est pas en local.', __FILE__));
}
sendVarToJs('zway_version', init('version'));
?>
<div id='div_updateZwayAlert' style="display: none;"></div>
<div class="alert alert-warning">{{Attention la mise à jour peut être longue (30 min)}}</div>
<pre id='pre_zwaveupdate' style='overflow: auto; height: 90%;with:90%;'></pre>


<script>
	$.ajax({
		type: 'POST',
		url: 'plugins/zwave/core/ajax/zwave.ajax.php',
		data: {
			action: 'updateZwayServer',
			version : zway_version,
		},
		dataType: 'json',
		global: false,
		error: function (request, status, error) {
			handleAjaxError(request, status, error, $('#div_updateZwayAlert'));
		},
		success: function () {
			getZwaveLog(1);
		}
	});

	function getZwaveLog(_autoUpdate) {
		$.ajax({
			type: 'POST',
			url: 'core/ajax/log.ajax.php',
			data: {
				action: 'get',
				logfile: 'zway_update',
			},
			dataType: 'json',
			global: false,
			error: function (request, status, error) {
				setTimeout(function () {
					getJeedomLog(_autoUpdate, _log)
				}, 1000);
			},
			success: function (data) {
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: data.result, level: 'danger'});
					return;
				}
				var log = '';
				var regex = /<br\s*[\/]?>/gi;
				for (var i in data.result.reverse()) {
					log += data.result[i][2].replace(regex, "\n");
				}
				$('#pre_zwaveupdate').text(log);
				$('#pre_zwaveupdate').scrollTop($('#pre_zwaveupdate').height() + 200000);
				if (!$('#pre_zwaveupdate').is(':visible')) {
					_autoUpdate = 0;
				}

				if (init(_autoUpdate, 0) == 1) {
					setTimeout(function () {
						getZwaveLog(_autoUpdate)
					}, 1000);
				}
			}
		});
	}

</script>