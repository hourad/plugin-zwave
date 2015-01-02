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
if (init('id') == '') {
    throw new Exception('{{EqLogic ID ne peut être vide}}');
}
$eqLogic = eqLogic::byId(init('id'));
if (!is_object($eqLogic)) {
    throw new Exception('{{EqLogic non trouvé}}');
}
$device = zwave::devicesParameters($eqLogic->getConfiguration('device'));
sendVarToJS('configureDeviceId', init('id'));
$sameDevices = $eqLogic->getSameDevice();
$info = $eqLogic->getInfo();
?>
<div id='div_configureDeviceAlert' style="display: none;"></div>
<ul class="nav nav-tabs" role="tablist">
    <li class="active"><a href="#tab_general" role="tab" data-toggle="tab">{{Général}}</a></li>
    <li><a href="#tab_group" role="tab" data-toggle="tab">{{Groupe}}</a></li>
</ul>
<div class="tab-content">
    <div class="tab-pane active" id="tab_general"><br/>
        <?php
        if (is_array($device) && count($device) != 0 && $eqLogic->getConfiguration('device') != '') {
            ?>

            <form class="form-horizontal">
                <fieldset>
                    <legend>Informations                 

                        <?php if (count($sameDevices) > 1) { ?>
                        <a class="btn btn-warning btn-xs pull-right" style="color : white;" id="bt_copyDeviceConfiguration"><i class="fa fa-files-o"></i> {{Copier}}</a>
                        <select class='form-control input-sm pull-right' id='sel_copyDeviceConfiguration' style='display: inline-block;width : 250px;font-size : 0.6em;'>
                            <?php
                            foreach ($sameDevices as $sameDevice) {
                                if ($eqLogic->getId() != $sameDevice->getId()) {
                                    echo '<option value="' . $sameDevice->getId() . '">' . $sameDevice->getHumanName() . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <?php } ?>
                    </legend>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                                <div class="col-sm-8">
                                    <span class="tooltips label label-default"><?php echo $eqLogic->getHumanName() ?></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Nom du module}}</label>
                                <div class="col-sm-8">
                                    <span class="tooltips label label-default"><?php echo $device['name'] ?></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Marque}}</label>
                                <div class="col-sm-8">
                                    <span class="tooltips label label-default"><?php echo $device['vendor'] ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <?php
                            $wakeup = $eqLogic->getWakeUp();
                            if ($wakeup != '-') {
                                ?>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Wakeup (seconde)}}</label>
                                    <div class="col-sm-2">
                                        <input class="form-control" id="in_wakeUpTime" value="<?php echo $wakeup; ?>" /> 
                                    </div>
                                    <div class="col-sm-2">
                                        <a class="btn btn-success" id="bt_valideWakeup"><i class="fa fa-check"></i> Valider</a>
                                    </div>
                                </div>
                                <?php
                            }
                            if (config::byKey('isOpenZwave', 'zwave', 0) == 1) {
                                ?>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Polling (par pas de 30sec)}}</label>
                                    <div class="col-sm-2">
                                        <input class="form-control" id="in_pollingTime" value="<?php echo $eqLogic->getPolling(); ?>" /> 
                                    </div>
                                    <div class="col-sm-2">
                                        <a class="btn btn-success" id="bt_validePolling"><i class="fa fa-check"></i> Valider</a>
                                    </div>
                                </div>
                                <?php } ?>
                                <?php
                                if (count($device['configure']) > 0) {
                                    echo ' <a class="btn btn-default expertModeVisible tooltips" id="bt_deviceConfigureResendConfigurationCommand" style="margin-left: 5px;"><i class="fa fa-magnet"></i> Renvoyer commande(s) de configuration</a>';
                                }
                                ?>
                                <a class="btn btn-success expertModeVisible bt_deviceConfigurationAdministration" data-risk="{{sans risque}}" data-command="InterviewForce" style="color: white;" title="Force le module à renvoyer toutes ses données : configuration, valeurs, statut..."><i class="fa fa-refresh"></i> Forcer re-interview</a>
                                <a class="btn btn-warning expertModeVisible bt_deviceConfigurationAdministration" data-risk="{{sans risque}}" data-command="markBatteryFailed" style="color: white;"><i class="fa fa-times"></i> Marquer comme sans batterie</a>
                                <?php
                                if ($info['state']['value'] == 'Dead') {
                                    echo ' <a class="btn btn-danger expertModeVisible bt_deviceConfigurationAdministration tooltips" data-risk="{{risquée}}" data-command="removeFailed" style="color: white;margin-left: 5px;" title="Vous devez d\'abord marquer l\'équipement comme sans batterie avant de pouvoir le supprimer"><i class="fa fa-trash"></i> Supprimer le module défaillant</a>';
                                }
                                ?>
                            </div>
                        </div>

                        <legend>{{Configuration}}</legend>
                        <div class="alert alert-info">{{Certaines valeurs de configuration peuvent mettre plusieurs minutes à être reçues lors de la première récupération}}
                            <a class="btn btn-warning bt_forceRefresh pull-right btn-xs" style="color : white;" title="{{Force le module à renvoyer sa configuration et uniquement sa configuration}}"><i class="fa fa-refresh"></i> {{Forcer la mise à jour}}</a>
                        </div>
                        <div id="div_configureDeviceParameters">
                            <?php
                            if (count($device['parameters']) == 0) {
                                echo '<div class="alert alert-info">{{Il n\'y a aucun paramètre de configuration pour ce module}}</div>';
                            } else {
                                foreach ($device['parameters'] as $id => $parameter) {
                                    echo '<div class="form-group">';
                                    echo '<label class="col-sm-1 control-label tooltips" title="' . $parameter['description'] . '"><span class="tooltips label label-warning zwaveParameters">' . $id . '</span></label>';
                                    echo '<label class="col-sm-3 control-label tooltips" title="' . $parameter['description'] . '">' . $parameter['name'] . '</span></label>';
                                    echo '<div class="col-sm-3">';
                                    switch ($parameter['type']) {
                                        case 'input':
                                        echo '<input class="zwaveParameters form-control" data-l1key="' . $id . '" data-l2key="value"/>';
                                        break;
                                        case 'select':
                                        echo '<select class = "zwaveParameters form-control" data-l1key="' . $id . '" data-l2key="value">';
                                        foreach ($parameter['value'] as $value => $details) {
                                            echo '<option value="' . $value . '" data-description="' . $details['description'] . '">' . $details['name'] . '</option>';
                                        }
                                        echo '</select>';
                                        break;
                                    }
                                    echo '</div>';
                                    echo '<div class="col-sm-2">';
                                    if (isset($parameter['unite'])) {
                                        echo '<span class="tooltips label label-primary tooltips" title="Unité">' . $parameter['unite'] . '</span> ';
                                    }
                                    if (isset($parameter['min']) || isset($parameter['max'])) {
                                        echo '<span class="tooltips label label-primary tooltips" title="[min-max]">[' . $parameter['min'] . '-' . $parameter['max'] . ']</span> ';
                                    }

                                    if (isset($parameter['default'])) {
                                        echo '<span class="tooltips label label-primary tooltips" title="Défaut">' . $parameter['default'] . '</span> ';
                                    }
                                    echo '<span class="tooltips label label-default zwaveParameters" data-l1key="' . $id . '" data-l2key="size" title="Taille en byte"></span> ';
                                    echo '<span class="tooltips label label-info zwaveParameters" data-l1key="' . $id . '" data-l2key="datetime" title="Date"></span> ';
                                    echo '<span class="tooltips label label-warning zwaveParameters" data-l1key="' . $id . '" data-l2key="status" title="Status"></span>';
                                    echo '</div>';
                                    echo '<div class="col-sm-3">';
                                    echo '<span class="tooltips description"></span> ';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </fieldset>
                </form>
                <a class="btn btn-success pull-right" style="color : white;" id="bt_configureDeviceSend"><i class="fa fa-check"></i> {{Appliquer}}</a>

                <?php } else { ?>
                <legend>{{Informations}} </legend>
                <div id='div_configureDeviceAlert' style="display: none;"></div>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group alert alert-warning">
                            <label class="col-sm-2 control-label tooltips">{{Opération}}</label>
                            <div class="col-sm-6">
                               <a class="btn btn-warning expertModeVisible bt_deviceConfigurationAdministration" data-risk="{{sans risque}}" data-command="markBatteryFailed" style="color: white;">Marquer comme sans batterie</a>
                               <a class="btn btn-danger expertModeVisible bt_deviceConfigurationAdministration tooltips" data-risk="{{risquée}}" data-command="removeFailed" style="color: white;margin-left: 5px;" title="Vous devez d'abord marquer l'équipement comme sans batterie avant de pouvoir le supprimer">Supprimer le module défaillant</a>
                           </div>
                       </div>
                       <div id="div_configureDeviceParameters">
                        <div class="form-group alert alert-warning">
                            <label class="col-sm-2 control-label tooltips">{{Ecrire paramètre}}</label>
                            <div class="col-sm-1">
                                <input class="form-control" id="in_parametersId"/>
                            </div>
                            <label class="col-sm-1 control-label tooltips">{{Taille}}</label>
                            <div class="col-sm-1">
                                <input class="zwaveParameters form-control" data-l2key="size" />
                            </div>
                            <label class="col-sm-1 control-label tooltips">{{Valeur}}</label>
                            <div class="col-sm-1">
                                <input class="zwaveParameters form-control" data-l2key="value" />
                            </div>
                            <div class="col-sm-3">
                                <a class="btn btn-success pull-right" style="color : white;" id="bt_configureDeviceSendGeneric"><i class="fa fa-check"></i> {{Appliquer}}</a>
                            </div>
                        </div>
                        <div class="form-group alert alert-success">
                            <label class="col-sm-2 control-label tooltips">{{Lire paramètre}}</label>
                            <div class="col-sm-1">
                                <input class="form-control" id="in_parametersReadId" />
                            </div>
                            <label class="col-sm-1 control-label tooltips">{{Taille}}</label>
                            <div class="col-sm-1">
                                <span class="zwaveParameters label label-primary" data-l2key="size" ></span>
                            </div>
                            <label class="col-sm-1 control-label tooltips">{{Valeur}}</label>
                            <div class="col-sm-1">
                                <span class="zwaveParameters label label-primary" data-l2key="value" ></span>
                            </div>
                            <div class="col-sm-3">
                                <a class="btn btn-success pull-right bt_configureReadParameter" style="color : white;" data-force="0"><i class="fa fa-refresh"></i> {{Rafraîchir}}</a>
                                <a class="btn btn-warning pull-right bt_configureReadParameter" style="color : white;" data-force="1"><i class="fa fa-refresh"></i> {{Demander}}</a>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
            <?php } ?>
        </div>
        <div class="tab-pane" id="tab_group"><br/>
            <div id='div_configureDeviceAssociation' style="display: none;"></div>
            <?php
            if (isset($device['groups']) && isset($device['groups']['description'])) {
                echo '<div class="alert alert-info">' . $device['groups']['description'] . '</div>';
            }
            ?>
            <legend>{{Association}}</legend>
            <form class="form-horizontal">
                <fieldset>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">{{Ajouter association}}</label>
                        <div class="col-sm-2">
                            <select class="form-control" id="in_configureDeviceAddAssociationGroup"></select>
                        </div>
                        <div class="col-sm-2">
                            <select class="form-control" id="in_configureDeviceAddAssociationNode">
                                <?php
                                echo '<option value="' . zwave::getZwaveInfo('controller::data::nodeId::value') . '">Jeedom</option>';
                                foreach (zwave::byType('zwave') as $zwave) {
                                    echo '<option value="' . $zwave->getLogicalId() . '">' . $zwave->getHumanName() . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <a class="btn btn-success" id="bt_configureDeviceAddAssociation"><i class="fa fa-check-circle"></i> {{Ok}}</a>
                        </div>
                    </div>
                </fieldset>
            </form>
            <table id="table_configureDeviceAssociation" class="table table-bordered table-condensed">
                <thead>
                    <tr>
                        <th>{{Numéro de groupe}}</th>
                        <th>{{Membre}}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <script>
                $('#bt_configureDeviceAddAssociation').on('click', function () {
                    changeAssociation('add', $('#in_configureDeviceAddAssociationGroup').value(), $('#in_configureDeviceAddAssociationNode').value())
                });

                loadAssociation();

                function changeAssociation(_mode, _group, _node) {
                $.ajax({// fonction permettant de faire de l'ajax
                    type: "POST", // méthode de transmission des données au fichier php
                    url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
                    data: {
                        action: "changeAssociation",
                        id: configureDeviceId,
                        group: _group,
                        node: _node,
                        mode: _mode
                    },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
                    },
                    success: function (data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    $('#div_configureDeviceAlert').showAlert({message: '{{Opération reussi}}', level: 'success'});
                    loadAssociation();
                }
            });
}

function loadAssociation() {
                $.ajax({// fonction permettant de faire de l'ajax
                    type: "POST", // méthode de transmission des données au fichier php
                    url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
                    data: {
                        action: "getAssociation",
                        id: configureDeviceId,
                    },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
                    },
                    success: function (data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_configureDeviceAssociation').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    var tr = '';
                    $('#in_configureDeviceAddAssociationGroup').empty();
                    for (var i in data.result) {
                        if (!isNaN(i)) {
                            tr += '<tr>';
                            tr += '<td>';
                            tr += i;
                            tr += '</td>';
                            tr += '<td>';
                            for (var j in data.result[i].nodes.value) {
                                tr += data.result[i].nodes.value[j].name;
                                tr += ' <a class="cursor removeAssociation" data-group="' + i + '" data-node="' + data.result[i].nodes.value[j].id + '"><i class="fa fa-minus-circle"></i></a>';
                                tr += ' ';
                            }
                            tr += '</td>';
                            tr += '</tr>';
                            $('#in_configureDeviceAddAssociationGroup').append('<option value="' + i + '">{{Groupe}} ' + i + '</option>')
                        }
                    }
                    $('#table_configureDeviceAssociation tbody').empty().append(tr);
                }
            });
}

$('#table_configureDeviceAssociation tbody').delegate('a.removeAssociation', 'click', function () {
    changeAssociation('remove', $(this).attr('data-group'), $(this).attr('data-node'));
});
</script>
</div>
</div>
<script>


    $('#bt_validePolling').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // méthode de transmission des données au fichier php
            url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
            data: {
                action: "setPolling",
                id: configureDeviceId,
                polling: $('#in_pollingTime').value()
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_configureDeviceAlert').showAlert({message: 'Opération réalisée avec succès (le temps de prise en compte par le périphérique peut être de plusieurs minutes)', level: 'success'});
        }
    });
});


$('#bt_valideWakeup').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // méthode de transmission des données au fichier php
            url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
            data: {
                action: "setWakeUp",
                id: configureDeviceId,
                wakeup: $('#in_wakeUpTime').value()
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_configureDeviceAlert').showAlert({message: 'Opération réalisée avec succès (le temps de prise en compte par le périphérique peut être de plusieurs minutes)', level: 'success'});
        }
    });
});


initTooltips();
$('.bt_deviceConfigurationAdministration').on('click', function () {
    var risk = $(this).attr('data-risk');
    var command = $(this).attr('data-command');
    bootbox.confirm('{{Etes-vous sûr de vouloir effectuer l\'opération :}} <b>' + command + '</b>. {{Le risque de l\'opération est :}}  <b>' + risk + '</b> ?', function (result) {
        if (result) {
                $.ajax({// fonction permettant de faire de l'ajax
                    type: "POST", // méthode de transmission des données au fichier php
                    url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
                    data: {
                        action: "deviceAdministation",
                        id: configureDeviceId,
                        command: command
                    },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
                    },
                    success: function (data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    if (command == 'removeFailed') {
                        window.location.reload();
                    } else {
                        $('#div_configureDeviceAlert').showAlert({message: 'Opération réalisée avec succès', level: 'success'});
                    }
                }
            });
}
});
});
$('select.zwaveParameters').on('change', function () {
    $(this).closest('.form-group').find('.description').html($(this).find('option:selected').attr('data-description'));
});
$('#bt_configureDeviceSendGeneric').on('click', function () {
    var param_id = $('#in_parametersId').value();
    $(this).closest('.form-group').find('.zwaveParameters[data-l2key=size]').attr('data-l1key', param_id);
    $(this).closest('.form-group').find('.zwaveParameters[data-l2key=value]').attr('data-l1key', param_id);
    var configurations = $('#div_configureDeviceParameters').getValues('.zwaveParameters');
    configureDeviceSave(configurations[0]);
});
$('.bt_forceRefresh').on('click', function () {
    configureDeviceLoad(true);
});
$('#bt_configureDeviceSend').on('click', function () {
    var configurations = $('#div_configureDeviceParameters').getValues('.zwaveParameters');
    configureDeviceSave(configurations[0]);
});
$('.bt_configureReadParameter').on('click', function () {
    var param_id = $('#in_parametersReadId').value();
    $(this).closest('.form-group').find('.zwaveParameters[data-l2key=size]').attr('data-l1key', param_id);
    $(this).closest('.form-group').find('.zwaveParameters[data-l2key=value]').attr('data-l1key', param_id);
    configureDeviceLoad($(this).attr('data-force'), $('#in_parametersReadId').value());
});
$('#bt_copyDeviceConfiguration').on('click', function () {
    bootbox.confirm('{{Etes-vous sûr de vouloir copier la configuration de}} <b>' + $('#sel_copyDeviceConfiguration option:selected').text() + '</b> ?', function (result) {
        if (result) {
                $.ajax({// fonction permettant de faire de l'ajax
                    type: "POST", // méthode de transmission des données au fichier php
                    url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
                    data: {
                        action: "copyDeviceConfiguration",
                        id: configureDeviceId,
                        copy_id: $('#sel_copyDeviceConfiguration').value()
                    },
                    dataType: 'json',
                    error: function (request, status, error) {
                        handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
                    },
                    success: function (data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    configureDeviceLoad(1);
                }
            });
}
});
});

$('#bt_deviceConfigureResendConfigurationCommand').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // méthode de transmission des données au fichier php
            url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
            data: {
                action: "resendDeviceConfiguration",
                id: configureDeviceId,
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_configureDeviceAlert').showAlert({message: 'Opération réussie', level: 'success'});
        }
    });
    });


function configureDeviceLoad(_forceRefresh, _parameter_id) {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // méthode de transmission des données au fichier php
            url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
            data: {
                action: "getDeviceConfiguration",
                id: configureDeviceId,
                forceRefresh: init(_forceRefresh, 0),
                parameter_id: init(_parameter_id, null)
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_configureDeviceParameters').setValues(data.result, '.zwaveParameters');
        }
    });
    }

    function configureDeviceSave(configurations) {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // méthode de transmission des données au fichier php
            url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
            data: {
                action: "setDeviceConfiguration",
                id: configureDeviceId,
                configurations: json_encode(configurations)
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error, $('#div_configureDeviceAlert'));
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_configureDeviceAlert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_configureDeviceAlert').showAlert({message: '{{Paramètres envoyés avec succès (la prise en compte peut prendre jusqu\'à plusieurs minutes)}}', level: 'success'});
            configureDeviceLoad(1);
        }
    });
}
</script>


<?php if (is_array($device) && count($device) != 0 && $eqLogic->getConfiguration('device') != '') { ?>
<script>
    configureDeviceLoad();
</script>
<?php } ?>
