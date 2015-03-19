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
?>
<div id='div_showNotificationAlert' style="display: none;"></div>
<span class='pull-left'>
	<select class="form-control expertModeVisible" style="width : 200px;" id="sel_showNotificationServerId">
		<?php
foreach (zwave::listServerZway() as $id => $server) {
	if (isset($server['name']) && $server['isOpenZwave'] != 1) {
		echo '<option value="' . $id . '">' . $server['name'] . '</option>';
	}
}
?>
	</select>
</span>

<table id="table_showNotification" class="table table-condensed">
	<thead>
		<tr>
			<th>{{Date}}</th>
			<th>{{Niveau}}</th>
			<th>{{Type}}</th>
			<th>{{Message}}</th>
		</tr>
	</thead>
	<tbody>

	</tbody>
</table>

<script>
	$('#sel_showNotificationServerId').on('change',function(){
		showNotification($('#sel_showNotificationServerId').value());
	});

	showNotification($('#sel_showNotificationServerId').value());

	function showNotification(_serverId){
		$.ajax({// fonction permettant de faire de l'ajax
		        type: "POST", // méthode de transmission des données au fichier php
		        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
		        data: {
		        	action: "showNotification",
		        	serverID: _serverId,
		        },
		        dataType: 'json',
		        global: false,
		        error: function (request, status, error) {
		        	handleAjaxError(request, status, error,$('#div_showNotificationAlert'));
		        },
		        success: function (data) { // si l'appel a bien fonctionné
		        if (data.state != 'ok') {
		        	$('#div_showNotificationAlert').showAlert({message: data.result, level: 'danger'});
		        	return;
		        }
		        if(isset(data.result.data) && isset(data.result.data.notifications)){
		        	var html  = '';
		        	for(var i in data.result.data.notifications.reverse()){
		        		var date = new Date(data.result.data.notifications[i].timestamp);
		        		html += '<tr>';
		        		html += '<td>';
		        		html +=date.getFullYear()+'-'+(date.getMonth()+1)+'-'+date.getDate()+' '+date.getHours()+':'+date.getMinutes();
		        		html += '</td>';
		        		html += '<td>';
		        		html +=data.result.data.notifications[i].level
		        		html += '</td>';
		        		html += '<td>';
		        		html +=data.result.data.notifications[i].type
		        		html += '</td>';
		        		html += '<td>';
		        		html +=data.result.data.notifications[i].message
		        		html += '</td>';
		        		html += '</tr>';
		        	}
		        	$('#table_showNotification tbody').empty().html(html);
		        }
		    }
		});
}



</script>