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
$eqLogic = zwave::byId(init('id'));
if (!is_object($eqLogic)) {
	throw new Exception(__('Equipement Z-Wave introuvable : ', __FILE__) . init('id'));
}
$results = zwave::callRazberry('/ZWaveAPI/Run/devices[' . $eqLogic->getLogicalId() . ']', $eqLogic->getConfiguration('serveurID', 1));
?>
<div id='div_zwaveInterviewResult' style="display: none;"></div>
<table class="table table-condensed">
	<thead>
		<tr>
			<th>Instance</th>
			<th>Class</th>
			<th>Statut</th>
			<th>Action</th>
		</tr>
	</thead>
	<tbody>
		<?php
foreach ($results['instances'] as $instanceID => $instance) {
	foreach ($instance['commandClasses'] as $ccId => $commandClasses) {
		if (($ccId == 96 && $instanceID != 0) || (($ccId == 134 || $ccId == 114 || $ccId == 96) && $instanceID == 0)) {
			continue;
		}
		if (isset($commandClasses['data']) && isset($commandClasses['data']['supported']) && (!isset($commandClasses['data']['supported']['value']) || $commandClasses['data']['supported']['value'] != true)) {
			continue;
		}
		echo "<tr>";
		echo "<td>$instanceID</td>";
		echo "<td>" . $commandClasses['name'] . "</td>";
		if (isset($commandClasses['data']) && isset($commandClasses['data']['interviewDone']) && (!isset($commandClasses['data']['interviewDone']['value']) || $commandClasses['data']['interviewDone']['value'] != true)) {
			echo "<td><span class='label label-danger'>{{Incomplet}}</span></td>";
		} else {
			echo "<td><span class='label label-success'>{{Complet}}</span></td>";
		}
		echo "<td><a class='btn btn-primary btn-xs forceInterview' data-instance='$instanceID' data-class='$ccId' data-id='" . $eqLogic->getId() . "'><i class='fa fa-retweet'></i> {{Forcer (re)interview}}</a></td>";
		echo "</tr>";
	}
}
?>
	</tbody>
</table>

<script>
	$('.forceInterview').on('click',function(){
		$.ajax({// fonction permettant de faire de l'ajax
		        type: "POST", // méthode de transmission des données au fichier php
		        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
		        data: {
		        	action: "deviceAdministation",
		        	command : 'InterviewForce',
		        	id: $(this).attr('data-id'),
		        	instanceId: $(this).attr('data-instance'),
		        	classId: $(this).attr('data-class')
		        },
		        dataType: 'json',
		        global: false,
		        error: function (request, status, error) {
		        	handleAjaxError(request, status, error,$('#div_zwaveInterviewResult'));
		        },
		        success: function (data) { // si l'appel a bien fonctionné
		        if (data.state != 'ok') {
		        	$('#div_zwaveInterviewResult').showAlert({message: data.result, level: 'danger'});
		        	return;
		        }
		        $('#div_zwaveInterviewResult').showAlert({message: "Demande envoyée, la mise à jour peut prendre plusieurs minutes", level: 'success'});
		    }
		});
	});
</script>