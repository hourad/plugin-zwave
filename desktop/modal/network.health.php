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

$infos = array();
$communicationStatistics = array();
$serverList = zwave::listServerZway();
foreach ($serverList as $id => $server) {
	if (isset($server['name'])) {
		$infos[$id] = zwave::callRazberry('/ZWaveAPI/Data/0', $id);
		try {
			$communicationStatistics[$id] = zwave::callRazberry('/ZWaveAPI/CommunicationStatistics', $id);
		} catch (Exception $e) {

		}
	}
}
?>
<div id='div_networkHealthAlert' style="display: none;"></div>
<table class="table table-condensed">
	<thead>
		<tr>
			<th>{{Module}}</th>
			<th>{{ID}}</th>
			<th>{{Serveur}}</th>
			<th>{{Interview}}</th>
			<th>{{Statut}}</th>
			<th>{{Batterie}}</th>
			<th>{{Wakeup time}}</th>
			<th>{{Paquet total}}</th>
			<th>{{% OK}}</th>
			<th>{{Temporisation (ms)}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Ping}}</th>
		</tr>
	</thead>
	<tbody>
		<?php
foreach (zwave::byType('zwave') as $eqLogic) {
	$info = $eqLogic->getInfo($infos[$eqLogic->getConfiguration('serverID')]);
	echo "<tr>";
	echo "<td><a href='index.php?v=d&m=zwave&p=zwave&id=" . $eqLogic->getId() . "'>" . $eqLogic->getHumanName() . "</a></td>";
	echo "<td>" . $eqLogic->getLogicalId() . "</td>";
	echo "<td>" . $serverList[$eqLogic->getConfiguration('serverID')]['name'] . "</td>";
	if (isset($info['interviewComplete']['value']) && $info['interviewComplete']['value'] != '') {
		if ($info['interviewComplete']['value'] == __('Complete', __FILE__)) {
			echo "<td><a class='btn btn-xs btn-success bt_showInterview' data-id='" . $eqLogic->getId() . "'>" . $info['interviewComplete']['value'] . "</a></td>";
		} else {
			echo "<td><a class='btn btn-xs btn-warning bt_showInterview' data-id='" . $eqLogic->getId() . "'>" . $info['interviewComplete']['value'] . "</a></td>";
		}
	} else {
		echo "<td></td>";
	}
	if ($info['state']['value'] == 'Dead') {
		echo "<td><span class='label label-danger' title=" . $info['state']['datetime'] . ">" . $info['state']['value'] . "</span></td>";
	} else {
		echo "<td><span class='label label-success' title=" . $info['state']['datetime'] . ">" . $info['state']['value'] . "</span></td>";
	}
	if (!isset($info['battery']) || $info['battery']['value'] == '') {
		echo "<td>NA</td>";
	} else {
		if ($info['battery']['value'] < 10) {
			echo "<td><span class='label label-danger' title=" . $info['battery']['datetime'] . ">" . $info['battery']['value'] . " %</span></td>";
		} else {
			echo "<td><span class='label label-success' title=" . $info['battery']['datetime'] . ">" . $info['battery']['value'] . " %</span></td>";
		}
	}
	if (isset($info['wakup']['value'])) {
		echo "<td>" . $info['wakup']['value'] . "</td>";
	} else {
		echo "<td>-</td>";
	}
	echo "<td>";
	if (isset($communicationStatistics[$eqLogic->getConfiguration('serverID')][$eqLogic->getLogicalId()])) {
		echo count($communicationStatistics[$eqLogic->getConfiguration('serverID')][$eqLogic->getLogicalId()]);
	}
	echo "</td>";
	echo "<td>";
	if (isset($communicationStatistics[$eqLogic->getConfiguration('serverID')][$eqLogic->getLogicalId()]) && count($communicationStatistics[$eqLogic->getConfiguration('serverID')][$eqLogic->getLogicalId()]) > 0) {
		$nbOk = 0;
		$mintime = 99999999;
		$avgtime = 0;
		$maxtime = 0;
		foreach ($communicationStatistics[$eqLogic->getConfiguration('serverID')][$eqLogic->getLogicalId()] as $packet) {
			if ($packet['delivered']) {
				$nbOk++;
				$avgtime += $packet['deliveryTime'];
				if ($maxtime < $packet['deliveryTime']) {
					$maxtime = $packet['deliveryTime'];
				}
				if ($mintime > $packet['deliveryTime']) {
					$mintime = $packet['deliveryTime'];
				}
			}
		}
		$avgtime = round($avgtime / $nbOk);
		$pourcentOk = round($nbOk / count($communicationStatistics[$eqLogic->getConfiguration('serverID')][$eqLogic->getLogicalId()]) * 100);
		if ($pourcentOk == 100) {
			echo "<span class='label label-success'>" . $pourcentOk . " %</span>";
		} elseif ($pourcentOk > 75) {
			echo "<span class='label label-warning'>" . $pourcentOk . " %</span>";
		} else {
			echo "<span class='label label-danger'>" . $pourcentOk . " %</span>";
		}
	}
	echo "<td>";
	if (isset($communicationStatistics[$eqLogic->getConfiguration('serverID')][$eqLogic->getLogicalId()]) && count($communicationStatistics[$eqLogic->getConfiguration('serverID')][$eqLogic->getLogicalId()]) > 0) {
		echo "<span class='label label-success tooltips' title='Temps de livraison minimum'>" . $mintime . "</span> ";
		echo "<span class='label label-primary tooltips' title='Temps de livraison moyen'>" . $avgtime . "</span> ";
		echo "<span class='label label-danger tooltips' title='Temps de livraison maximum'>" . $maxtime . "</span>";
	}
	echo "</td>";
	echo "<td>" . $info['lastReceived']['value'] . "</td>";
	echo "<td>";
	if ((!isset($info['battery']) || $info['battery']['value'] == '') && is_numeric($eqLogic->getLogicalId())) {
		echo "<a class='btn btn-primary btn-xs bt_pingDevice' data-id='" . $eqLogic->getId() . "'><i class='fa fa-eye'></i> {{Ping}}</a>";
	}
	echo "</td>";
	echo "</tr>";
}
?>
	</tbody>
</table>

<script>
	initTooltips();
	$('.bt_showInterview').on('click',function(){
		$('#md_modal2').dialog({title: "{{Interview}}"});
		$('#md_modal2').load('index.php?v=d&plugin=zwave&modal=interview.result&id=' + $(this).attr('data-id')).dialog('open');
	});

	$('.bt_pingDevice').on('click',function(){
		$.ajax({// fonction permettant de faire de l'ajax
		        type: "POST", // méthode de transmission des données au fichier php
		        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
		        data: {
		        	action: "sendNoOperation",
		        	id: $(this).attr('data-id'),
		        },
		        dataType: 'json',
		        global: false,
		        error: function (request, status, error) {
		        	handleAjaxError(request, status, error,$('#div_networkHealthAlert'));
		        },
		        success: function (data) { // si l'appel a bien fonctionné
		        if (data.state != 'ok') {
		        	$('#div_networkHealthAlert').showAlert({message: data.result, level: 'danger'});
		        	return;
		        }
		        $('#div_networkHealthAlert').showAlert({message: "{{Ping envoyé avec succès}}", level: 'success'});
		    }
		});

});
</script>