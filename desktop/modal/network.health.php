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
$infos = zwave::callRazberry('/ZWaveAPI/Data/0');
?>
<table class="table table-condensed">
	<thead>
		<tr>
			<th>{{Module}}</th>
			<th>{{ID}}</th>
			<th>{{Interview}}</th>
			<th>{{Statut}}</th>
			<th>{{Batterie}}</th>
			<th>{{Wakeup time}}</th>
			<th>{{Dernière communication}}</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach (zwave::byType('zwave') as $eqLogic) {
			$info = $eqLogic->getInfo($infos);
			echo "<tr>";
			echo "<td><a href='index.php?v=d&m=zwave&p=zwave&id=".$eqLogic->getId()."'>".$eqLogic->getHumanName()."</a></td>";
			echo "<td>".$eqLogic->getLogicalId()."</td>";
			if($info['interviewComplete']['value'] == __('Complet', __FILE__)){
				echo "<td><a class='btn btn-xs btn-success bt_showInterview' data-id='".$eqLogic->getId()."'>".$info['interviewComplete']['value']."</a></td>";
			}else{
				echo "<td><a class='btn btn-xs btn-danger bt_showInterview' data-id='".$eqLogic->getId()."'>".$info['interviewComplete']['value']."</a></td>";
			}
			echo "<td>".$info['state']['value']."</td>";
			if(!isset($info['battery']) || $info['battery']['value'] == ''){
				echo "<td>NA</td>";
			}else{
				if($info['battery']['value'] < 10){
					echo "<td><span class='label label-danger' title=".$info['battery']['datetime'].">".$info['battery']['value']." %</span></td>";
				}else{
					echo "<td><span class='label label-success' title=".$info['battery']['datetime'].">".$info['battery']['value']." %</span></td>";
				}
			}
			echo "<td>".$info['wakup']['value']."</td>";
			echo "<td>".$info['lastReceived']['value']."</td>";
			echo "</tr>";
		}
		?>
	</tbody>
</table>

<script>
	$('.bt_showInterview').on('click',function(){
		$('#md_modal2').dialog({title: "{{Interview}}"});
		$('#md_modal2').load('index.php?v=d&plugin=zwave&modal=interview.result&id=' + $(this).attr('data-id')).dialog('open');
	});
</script>