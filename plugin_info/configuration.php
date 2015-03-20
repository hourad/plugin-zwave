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
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$localZwayServer = false;
$noServer = true;
foreach (zwave::listServerZway() as $id => $server) {
	if (isset($server['addr'])) {
		$noServer = false;
		if (($server['addr'] == '127.0.0.1' || $server['addr'] == 'localhost') && $server['isOpenZwave'] != 1) {
			$localZwayServer = true;
		}
	}
}

if (!$localZwayServer && $noServer) {
	$localZwayServer = true;
}
?>
<form class="form-horizontal">
    <fieldset>
        <?php
foreach (zwave::listServerZway() as $id => $server) {
	if (isset($server['name'])) {
		echo ' <div class="form-group"><label class="col-sm-2 control-label">{{Serveur }}' . $server['name'] . '</label>';
		try {
			$controlerState = zwave::getZwaveInfo('', $id);
			if (!is_array($controlerState)) {
				echo '<div class="col-sm-1"><span class="label label-warning tooltips" title="{{Serveur z-wave démarré mais erreur lors de la récuperation des données}}">NOK {{voir cette <a target="_blank" href="http://doc.jeedom.fr/fr_FR/zwave.html#configuration_du_port_dans_le_zway_server">doc</a>}}</span></div>';
			} else {
				echo '<div class="col-sm-1"><span class="label label-success">OK</span></div>';
			}
		} catch (Exception $e) {
			echo '<div class="col-sm-1"><span class="label label-danger">NOK</span></div>';
		}
		echo '</div>';
	}
}
?>  </fieldset>
</form>
<form class="form-horizontal">
    <fieldset>
        <legend>{{Paramètres}}</legend>
        <div class="form-group">
            <label class="col-lg-2 control-label">{{Serveur Z-wave nom}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="zwaveName1" />
            </div>
            <label class="col-lg-1 control-label">{{IP}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="zwaveAddr1" />
            </div>
            <label class="col-lg-1 control-label">{{Port}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="zwavePort1" value="8083" />
            </div>
            <label class="col-lg-1 control-label">{{Openzwave}}</label>
            <div class="col-lg-1">
                <input type="checkbox" class="configKey" data-l1key="isOpenZwave1" />
            </div>
        </div>
        <div class="form-group">
          <label class="col-lg-2 control-label">{{Serveur Z-wave nom}}</label>
          <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="zwaveName2" />
        </div>
        <label class="col-lg-1 control-label">{{IP}}</label>
        <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="zwaveAddr2" />
        </div>
        <label class="col-lg-1 control-label">{{Port}}</label>
        <div class="col-lg-2">
            <input class="configKey form-control" data-l1key="zwavePort2" value="8083" />
        </div>
        <label class="col-lg-1 control-label">{{Openzwave}}</label>
        <div class="col-lg-1">
            <input type="checkbox" class="configKey" data-l1key="isOpenZwave2" />
        </div>
    </div>
    <div class="form-group">
        <label class="col-lg-4 control-label">{{Supprimer automatiquement les périphériques exclus}}</label>
        <div class="col-lg-3">
            <input type="checkbox" class="configKey" data-l1key="autoRemoveExcludeDevice" />
        </div>
    </div>
     <div class="form-group">
        <label class="col-lg-4 control-label">{{Ne pas remonter les notifications}}</label>
        <div class="col-lg-3">
            <input type="checkbox" class="configKey" data-l1key="noAlertOnNotification" />
        </div>
    </div>
    <div class="form-group expertModeVisible">
        <label class="col-lg-2 control-label">{{Notifications}}</label>
        <div class="col-lg-3">
            <a class="btn btn-default" id="bt_showNotification"><i class="fa fa-eye"></i> {{Afficher}}</a>
        </div>
    </div>
    <?php if ($localZwayServer) {?>

    <div class="form-group expertModeVisible">
        <label class="col-lg-2 control-label">{{Installer/Mettre à jour le serveur zway}}</label>
        <div class="col-lg-3">
            <a class="btn btn-danger" id="bt_updateZwayServer"><i class="fa fa-check"></i> {{Lancer}}</a>
        </div>
    </div>
    <?php }?>
    <script>
        $('#bt_showNotification').on('click',function(){
           $('#md_modal').dialog({title: "{{Notification du serveur Zway}}"});
           $('#md_modal').load('index.php?v=d&plugin=zwave&modal=show.notification').dialog('open');
       });


        $('#bt_updateZwayServer').on('click',function(){
            bootbox.confirm('{{Etes-vous sûr de vouloir installer/mettre à jour le serveur zway ? Ceci est une opération risquée !!!!!! Un <strong>razberry</strong> est nécessaire pour que le resulat fonctionne}}', function (result) {
              if (result) {
                bootbox.prompt("Version (laisser vide pour mettre la derniere stable) ?", function (result) {
                   if (result !== null) {
                      $('#md_modal').dialog({title: "{{Mise à jour du zway server}}"});
                      $('#md_modal').load('index.php?v=d&plugin=zwave&modal=update.zway&version='+result).dialog('open');
                  }
              });

            }
        });
        });

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

