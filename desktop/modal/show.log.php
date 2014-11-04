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
sendVarToJs('jeeNetwork_id', '');
if (config::byKey('zwaveAddr', 'zwave') != '127.0.0.1' && config::byKey('zwaveAddr', 'zwave') != 'localhost') {
    $jeeNetwork = jeeNetwork::byPlugin('zwave');
    if (count($jeeNetwork) == 0) {
        throw new Exception(__('Le serveur zway n\'est pas en local ou le serveur distant n\'a pas Jeedom d\'installé en mode esclave', __FILE__));
    }
    sendVarToJs('jeeNetwork_id', $jeeNetwork[0]->getId());
}
?>
<div class="alert alert-warning">{{Attention ne marche que si le serveur z-way est en local}}</div>
<pre id='pre_zwavelog' style='overflow: auto; height: 95%;with:90%;'></pre>


<script>
    getZwaveLog(1);

    function getZwaveLog(_autoUpdate) {
        $.ajax({
            type: 'POST',
            url: 'core/ajax/log.ajax.php',
            data: {
                action: 'get',
                logfile: '/var/log/z-way-server.log',
                jeeNetwork_id: jeeNetwork_id
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
                $('#pre_zwavelog').text(log);
                $('#pre_zwavelog').scrollTop($('#pre_zwavelog').height() + 200000);
                if (!$('#pre_zwavelog').is(':visible')) {
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