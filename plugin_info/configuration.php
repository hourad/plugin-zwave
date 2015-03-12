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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <?php
try {
	$controlerState = zwave::getZwaveInfo('controller::data::controllerState::value');
	echo '<div class="alert alert-success">{{Le z-way-server est en marche}}</div>';
} catch (Exception $e) {
	echo '<div class="alert alert-danger">{{Le z-way-server ne tourne pas}}</div>';
}
?>
       <div class="form-group">
        <label class="col-lg-3 control-label">{{Serveur Z-wave nom}}</label>
        <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="zwaveName1" />
        </div>
        <label class="col-lg-1 control-label">{{IP}}</label>
        <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="zwaveAddr1" />
        </div>
        <label class="col-lg-1 control-label">{{Port}}</label>
        <div class="col-lg-1">
            <input class="configKey form-control" data-l1key="zwavePort1" value="8083" />
        </div>
        <label class="col-lg-1 control-label">{{Openzwave}}</label>
        <div class="col-lg-1">
            <input type="checkbox" class="configKey" data-l1key="isOpenZwave1" />
        </div>
    </div>
    <div class="form-group">
      <label class="col-lg-3 control-label">{{Serveur Z-wave nom}}</label>
      <div class="col-lg-2">
        <input class="configKey form-control" data-l1key="zwaveName2" />
    </div>
    <label class="col-lg-1 control-label">{{IP}}</label>
    <div class="col-lg-2">
        <input class="configKey form-control" data-l1key="zwaveAddr2" />
    </div>
    <label class="col-lg-1 control-label">{{Port}}</label>
    <div class="col-lg-1">
        <input class="configKey form-control" data-l1key="zwavePort2" value="8083" />
    </div>
    <label class="col-lg-1 control-label">{{Openzwave}}</label>
    <div class="col-lg-1">
        <input type="checkbox" class="configKey" data-l1key="isOpenZwave2" />
    </div>
</div>
<div class="form-group">
    <label class="col-lg-3 control-label">{{Supprimer automatiquement les périphériques exclus}}</label>
    <div class="col-lg-3">
        <input type="checkbox" class="configKey" data-l1key="autoRemoveExcludeDevice" />
    </div>
</div>
<div class="form-group">

</div>

<script>
    function zwave_postSaveConfiguration(){
             $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
            data: {
                action: "restartDeamon",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#ul_plugin .li_plugin[data-plugin_id=zwave]').click();
        }
    });
         }
     </script>
 </fieldset>
</form>

