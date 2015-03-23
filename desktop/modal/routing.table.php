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
<div id='div_routingTableAlert' style="display: none;"></div>
<span class='pull-left'>
    <select class="form-control expertModeVisible" style="width : 200px;" id="sel_routingTableServerId">
        <?php
foreach (zwave::listServerZway() as $id => $server) {
	if (isset($server['name'])) {
		echo '<option value="' . $id . '">' . $server['name'] . '</option>';
	}
}
?>
  </select>
</span>
<a class='btn btn-warning btn-xs pull-right updateRoute' data-id="" style='color : white;'><i class="fa fa-refresh"></i> {{Forcer la mise à jour des routes}}</a><br/><br/>

<div id="div_routingTable"></div>

<table class="table table-bordered table-condensed" style="width: 500px;">
    <thead>
        <tr><th colspan="2">{{Légende}}</th></tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="2">{{Nombre de [routes directes / avec 1 saut / 2 sauts / 3 sauts / 4 sauts]}}</td>
        </tr>
        <tr>
            <td class="alert alert-success" style="width: 40px"></td>
            <td>{{Communication directe}}</td>
        </tr>
        <tr>
            <td class="alert alert-info"></td>
            <td>{{Au moins 2 routes avec un saut}}</td>
        </tr>
        <tr>
            <td class="alert alert-warning"></td>
            <td>{{Moins de 2 routes avec un saut}}</td>
        </tr>
        <tr>
            <td class="alert alert-danger"></td>
            <td>{{Toutes les routes ont plus d'un saut}}</td>
        </tr>
    </tbody>
</table>
<script>
    var devicesRouting = '';
    displayRoutingTable();


    $('body').delegate('.updateRoute','click', function () {
        var id = $(this).attr('data-id');
        if(id == ''){
         bootbox.confirm('{{Etes-vous sûr de vouloir mettre à jour les routes ? Cette opération est risquée', function (result) {
          if (result) {
            $.ajax({
                type: "POST",
                url: "plugins/zwave/core/ajax/zwave.ajax.php",
                data: {
                    action: "updateRoute",
                    serverId: $('#sel_routingTableServerId').value(),
                },
                dataType: 'json',
                error: function (request, status, error) {
                    handleAjaxError(request, status, error, $('#div_routingTableAlert'));
                },
                success: function (data) {
                    if (data.state != 'ok') {
                        $('#div_routingTableAlert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    $('#div_routingTableAlert').showAlert({message: '{{Demande de mise à jour des routes envoyée (cela peut mettre jusqu\'à plusieurs minutes)}}', level: 'success'});
                }
            });
        }
    });
}else{
   $.ajax({
    type: "POST",
    url: "plugins/zwave/core/ajax/zwave.ajax.php",
    data: {
        action: "updateRoute",
        serverId: $('#sel_routingTableServerId').value(),
        id: id,
    },
    dataType: 'json',
    error: function (request, status, error) {
        handleAjaxError(request, status, error, $('#div_routingTableAlert'));
    },
    success: function (data) {
        if (data.state != 'ok') {
            $('#div_routingTableAlert').showAlert({message: data.result, level: 'danger'});
            return;
        }
        $('#div_routingTableAlert').showAlert({message: '{{Demande de mise à jour des routes envoyée (cela peut mettre jusqu\'à plusieurs minutes)}}', level: 'success'});
    }
});
}
});

$('#sel_routingTableServerId').on('change',function(){
    var devicesRouting = '';
    displayRoutingTable();
});

function displayRoutingTable(){
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
        data: {
            action: "getRoutingTable",
            serverId: $('#sel_routingTableServerId').value(),
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error, $('#div_routingTableAlert'));
        },
        success: function (data) { // si l'appel a bien fonctionné
        if (data.state != 'ok') {
            $('#div_routingTableAlert').showAlert({message: data.result, level: 'danger'});
            return;
        }
        devicesRouting = data.result;

            var skipPortableAndVirtual = true; // to minimize routing table by removing not interesting lines
            var routingTable = '';
            var routingTableHeader = '';
            $.each(devicesRouting, function (nodeId, node) {
                if (nodeId == 255)
                    return;
                if (skipPortableAndVirtual && (node.data.isVirtual.value || node.data.basicType.value == 1))
                    return;

                var routesCount = getRoutesCount(nodeId);
                routingTableHeader += '<th>' + nodeId + '</th>';
                routingTable += '<tr><td>' + node.name + '</td><td>' + nodeId + '</td>';
                $.each(devicesRouting, function (nnodeId, nnode) {
                    if (nnodeId == 255)
                        return;
                    if (skipPortableAndVirtual && (nnode.data.isVirtual.value || nnode.data.basicType.value == 1))
                        return;

                    var rtClass;
                    if (!routesCount[nnodeId])
                        routesCount[nnodeId] = new Array(); // create empty array to let next line work
                    var routeHops = (routesCount[nnodeId][0] || '0')+"/";
                    routeHops += (routesCount[nnodeId][1] || '0')+"/";
                    routeHops += (routesCount[nnodeId][2] || '0')+"/";
                    routeHops += (routesCount[nnodeId][3] || '0')+"/";
                    routeHops += (routesCount[nnodeId][4] || '0');
                    if (nodeId == nnodeId || node.data.isVirtual.value || nnode.data.isVirtual.value || node.data.basicType.value == 1 || nnode.data.basicType.value == 1) {
                        rtClass = 'rtUnavailable';
                        routeHops = '';
                    } else if ($.inArray(parseInt(nnodeId, 10), node.data.neighbours.value) != -1)
                    rtClass = 'alert alert-success';
                    else if (routesCount[nnodeId] && routesCount[nnodeId][1] > 1)
                        rtClass = 'alert alert-info';
                    else if (routesCount[nnodeId] && routesCount[nnodeId][1] == 1)
                        rtClass = 'alert alert-warning';
                    else
                        rtClass = 'alert alert-danger';
                    routingTable += '<td class="' + rtClass + ' tooltips" title="' + routeHops + '"></td>';
                });
routingTable += '<td class="rtInfo">' + timeConverter(node.data.neighbours.updateTime);
if(nodeId != 1){
    routingTable += ' <a class="btn btn-primary btn-xs updateRoute" data-id="'+nodeId+'"><i class="fa fa-refresh"></i> {{Mettre à jour}}</a>';
}
routingTable += '</td></tr>';
});
$('#div_routingTable').html('<table class="table table-bordered table-condensed"><thead><tr><th>{{Nom}}</th><th>ID</th>' + routingTableHeader + '<th>Date</th></tr></thead><tbody>' + routingTable + '</tbody></table>');
initTooltips();
}
});
}

function getRoutesCount(nodeId) {
    var routesCount = {};
    $.each(getFarNeighbours(nodeId), function (index, nnode) {
        if (nnode.nodeId in routesCount) {
            if (nnode.hops in routesCount[nnode.nodeId])
                routesCount[nnode.nodeId][nnode.hops]++;
            else
                routesCount[nnode.nodeId][nnode.hops] = 1;
        } else {
            routesCount[nnode.nodeId] = new Array();
            routesCount[nnode.nodeId][nnode.hops] = 1;
        }
    });
    return routesCount;
}

// returns a list of {nodeId, hops}. Can be used to calculate number of routes and minimal hops to a node
function getFarNeighbours(nodeId, exludeNodeIds, hops) {
    if (hops === undefined) {
        var hops = 0;
        var exludeNodeIds = [nodeId];
    }
        if (hops > 4) // Z-Wave allows only 4 routers, but we are interested in only 2, since network becomes unstable if more that 2 routers are used in communications
            return [];

        var nodesList = [];
        $.each(devicesRouting[nodeId].data.neighbours.value, function (index, nnodeId) {
            if (!(nnodeId in devicesRouting))
                return; // skip deviced reported in routing table but absent in reality. This may happen after restore of routing table.
            if (!in_array(nnodeId, exludeNodeIds)) {
                nodesList.push({nodeId: nnodeId, hops: hops});
                if (devicesRouting[nnodeId].data.isListening.value && devicesRouting[nnodeId].data.isRouting.value)
                    $.merge(nodesList, getFarNeighbours(nnodeId, $.merge([nnodeId], exludeNodeIds) /* this will not alter exludeNodeIds */, hops + 1));
            }
        });
        return nodesList;
    }

    function timeConverter(UNIX_timestamp) {
        var a = new Date(UNIX_timestamp * 1000);
        var months = ['Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juilllet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        var year = a.getFullYear();
        var month = months[a.getMonth()];
        var date = a.getDate();
        var hour = a.getHours();
        var min = a.getMinutes();
        var sec = a.getSeconds();
        var time = date + ' ' + month + ' ' + year;
        return time;
    }
</script>
