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
            echo '<div class="alert alert-success">{{Le z-way-server est en marche}}</div>';
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">{{Le z-way-server ne tourne pas}}</div>';
        }
        ?>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Zway IP}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="zwaveAddr" />
            </div>
        </div>
         <div class="form-group">
            <label class="col-lg-4 control-label">{{Zway port}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="zwavePort" value="8083" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Supprimer automatiquement les périphériques exclus}}</label>
            <div class="col-lg-4">
                <input type="checkbox" class="configKey" data-l1key="autoRemoveExcludeDevice" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{J'utilise un serveur openzwave}}</label>
            <div class="col-lg-4">
                <input type="checkbox" class="configKey" data-l1key="isOpenZwave" />
            </div>
        </div>
        <?php if (config::byKey('zwaveAddr', 'zwave') == '127.0.0.1' || config::byKey('zwaveAddr', 'zwave') == 'localhost') { ?>
            <div class="form-group">
                <label class="col-lg-4 control-label">{{Arrêt/Redémarrage}}</label>
                <div class="col-lg-2">
                    <a class="btn btn-warning" id="bt_restartZwayServer"><i class='fa fa-stop'></i> {{Arrêter/Redemarrer le z-way-server}}</a> 
                </div>
            </div>
            <div class="form-group expertModeVisible">
                <label class="col-lg-4 control-label">{{Lancer en debug}}</label>
                <div class="col-lg-2">
                    <a class="btn btn-danger" id="bt_launchZwayServerInDebug"><i class="fa fa-exclamation-triangle"></i> {{Lancer en mode debug}}</a> 
                </div>
            </div>
            <script>
                $('#bt_restartZwayServer').on('click', function () {
                    $.ajax({// fonction permettant de faire de l'ajax
                        type: "POST", // methode de transmission des données au fichier php
                        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
                        data: {
                            action: "restartZwayServer",
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
                            $('#div_alert').showAlert({message: '{{Le z-way-server a été correctement arrêté : il se relancera automatiquement dans 1 minute}}', level: 'success'});
                            $('#ul_plugin .li_plugin[data-plugin_id=zwave]').click();
                        }
                    });
                });

                $('#bt_launchZwayServerInDebug').on('click', function () {
                    bootbox.confirm('{{Etes-vous sur de vouloir lancer le z-way-ser en mode debug ? N\'oubliez pas d\arrêter/redémarrer le démon une fois terminé}}', function (result) {
                        if (result) {
                            $('#md_modal').dialog({title: "{{Z-Way-Server en mode debug}}"});
                            $('#md_modal').load('index.php?v=d&plugin=zwave&modal=show.debug').dialog('open');
                        }
                    });
                });
            </script>
        <?php } ?>
    </fieldset>
</form>

