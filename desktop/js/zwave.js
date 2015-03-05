
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


 $('#bt_uploadConfZwave').fileupload({
    replaceFileInput: false,
    dataType: 'json',
    done: function (e, data) {
        if (data.result.state != 'ok') {
            $('#div_alert').showAlert({message: data.result.result, level: 'danger'});
            return;
        }
        if (modifyWithoutSave) {
            $('#div_alert').showAlert({message: '{{Fichier ajouté avec succès. Vous devez rafraîchir pour vous en servir}}', level: 'success'});
        } else {
            window.location.reload();
        }
    }
});

 $(".li_eqLogic").on('click', function () {
    printModuleInfo($(this).attr('data-eqLogic_id'));
    return false;
});

 $('#bt_syncEqLogic').on('click', function () {
    syncEqLogicWithRazberry();
});
 $('.changeIncludeState').on('click', function () {
    changeIncludeState($(this).attr('data-mode'), $(this).attr('data-state'));
});

 $('#bt_showClass').on('click', function () {
    $('#md_modal').dialog({title: "{{Classes du périphérique}}"});
    $('#md_modal').load('index.php?v=d&plugin=zwave&modal=show.class&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

 $('#bt_healthRazberry').on('click', function () {
    $('#md_modal').dialog({title: "{{Santé du Z-Wave}}"});
    $('#md_modal').load('index.php?v=d&plugin=zwave&modal=network.health').dialog('open');
});

 $('#bt_showInterview').on('click', function () {
    $('#md_modal2').dialog({title: "{{Interview}}"});
    $('#md_modal2').load('index.php?v=d&plugin=zwave&modal=interview.result&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

 $('#bt_showZwayLog').on('click', function () {
    $('#md_modal').dialog({title: "{{Log du serveur Zway}}"});
    $('#md_modal').load('index.php?v=d&plugin=zwave&modal=show.log').dialog('open');
});

 $('#bt_configureDevice').on('click', function () {
    $('#md_modal').dialog({title: "{{Configuration du périphérique}}"});
    $('#md_modal').load('index.php?v=d&plugin=zwave&modal=configure.device&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

 $('#bt_inspectQueue').on('click', function () {
    $('#md_modal').dialog({title: "{{Queue Z-Wave}}"});
    $('#md_modal').load('index.php?v=d&plugin=zwave&modal=inspect.queue').dialog('open');
});

 $('#bt_routingTable').on('click', function () {
    $('#md_modal').dialog({title: "{{Table de routage}}"});
    $('#md_modal').load('index.php?v=d&plugin=zwave&modal=routing.table').dialog('open');
});

 $('#bt_displayZwaveData').on('click', function () {
    $('#md_modal').dialog({title: "{{Arbre Z-Wave de l'équipement}}"});
    $('#md_modal').load('index.php?v=d&plugin=zwave&modal=zwave.data&id=' + $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

 $('#bt_adminRazberry').on('click', function () {
    $('#md_modal').dialog({title: "{{Actions avancées}}"});
    $('#md_modal').load('index.php?v=d&plugin=zwave&modal=admin.razberry').dialog('open');
});

 $("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

 $('body').delegate('#bt_getFromMarket', 'click', function () {
    $('#md_modal').dialog({title: "{{Market module zwave}}"});
    $('#md_modal').load('index.php?v=d&modal=market.list&type=zwave').dialog('open');
});

 $('body').delegate('#bt_shareOnMarket', 'click', function () {
    var logicalId = $('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').value();
    if (logicalId == '') {
        $('#div_alert').showAlert({message: '{{Vous devez d\'abord sélectionner une configuration à partager}}', level: 'danger'});
        return;
    }
    $('#md_modal').dialog({title: "{{Partager sur le market}}"});
    $('#md_modal').load('index.php?v=d&modal=market.send&type=zwave&logicalId=' + encodeURI(logicalId) + '&name=' + encodeURI($('.eqLogicAttr[data-l1key=configuration][data-l2key=device] option:selected').text())).dialog('open');
});

 $('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').on('change', function () {
    var logicalId = $(this).value();
   $('#img_device').attr('src', 'core/img/no_image.gif');
   $("<img>", {
    src: marketAddr + '/market/zwave/images/' + logicalId + '.jpg',
    error: function () {
        $("<img>", {
            src: marketAddr + '/market/zwave/images/' + logicalId + '_icon.png',
            error: function () {
                $("<img>", {
                    src: marketAddr + '/market/zwave/images/' + logicalId + '_icon.jpg',
                    error: function () {

                    },
                    load: function () {
                        $('#img_device').attr("data-original", marketAddr + '/market/zwave/images/' + logicalId + '_icon.jpg');
                        $('#img_device').lazyload({
                            event: "sporty"
                        });
                        $('#img_device').trigger("sporty");
                    }
                });
            },
            load: function () {
                $('#img_device').attr("data-original", marketAddr + '/market/zwave/images/' + logicalId + '_icon.png');
                $('#img_device').lazyload({
                    event: "sporty"
                });
                $('#img_device').trigger("sporty");
            }
        });
    },
    load: function () {
        $('#img_device').attr("data-original", marketAddr + '/market/zwave/images/' + logicalId + '.jpg');
        $('#img_device').lazyload({
            event: "sporty"
        });
        $('#img_device').trigger("sporty");
    }
});
});

$('body').delegate('.cmd .cmdAttr[data-l1key=type]', 'change', function () {
    if ($(this).value() == 'info') {
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=returnStateValue]').show();
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=returnStateTime]').show();
    } else {
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=returnStateValue]').hide();
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=returnStateTime]').hide();
    }
});

$('#bt_cronGenerator').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=refreshDelay]').value(result.value);
    });
});

/**********************Node js requests *****************************/
$('body').one('nodeJsConnect', function () {
    socket.on('zwave::controller.data.controllerState', function (_options) {
        $('.changeIncludeState').addClass('btn-default').removeClass('btn-success btn-danger').attr('data-state', 1);
        $('.changeIncludeState[data-mode=0]').html('<i class="fa fa-sign-in fa-rotate-90"></i> Mode exclusion');
        $('.changeIncludeState[data-mode=1]').html('<i class="fa fa-sign-in fa-rotate-90"></i> Mode inclusion');
        $.hideAlert();
        if (_options == 1) {
            $('.changeIncludeState[data-mode=1]').removeClass('btn-default').addClass('btn-success');
            $('.changeIncludeState[data-mode=1]').attr('data-state', 0);
            $('.changeIncludeState[data-mode=1]').html('<i class="fa fa-sign-in fa-rotate-90"></i> Arrêter l\'inclusion');
            $('#div_inclusionAlert').showAlert({message: '{{Vous êtes en mode inclusion. Cliquez à nouveau sur le bouton d\'inclusion pour sortir de ce mode}}', level: 'warning'});
        }
        if (_options == 5) {
            $('.changeIncludeState[data-mode=0]').removeClass('btn-default').addClass('btn-danger');
            $('.changeIncludeState[data-mode=0]').attr('data-state', 0);
            $('.changeIncludeState[data-mode=0]').html('<i class="fa fa-sign-out fa-rotate-90"></i> Arrêter l\'exclusion');
            $('#div_inclusionAlert').showAlert({message: '{{Vous êtes en mode exclusion. Cliquez à nouveau sur le bouton d\'exclusion pour sortir de ce mode}}', level: 'warning'});
        }
    });

setTimeout(function () {
    socket.on('zwave::includeDevice', function (_options) {
        if (modifyWithoutSave) {
            $('#div_inclusionAlert').showAlert({message: '{{Un périphérique vient d\'être inclus/exclu. Veuillez réactualiser la page}}', level: 'warning'});
        } else {
            if (_options == '') {
                window.location.reload();
            } else {
                window.location.href = 'index.php?v=d&p=zwave&m=zwave&id=' + _options;
            }
        }
    });
}, 3000);
});

$('#bt_autoDetectModule').on('click',function(){
 $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
        data: {
            action: "autoDetectModule",
            id: $('.eqLogicAttr[data-l1key=id]').value(),
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
        if (data.state != 'ok') {
            $('#div_alert').showAlert({message: data.result, level: 'danger'});
            return;
        }
    }
});
});

function printModuleInfo(_id) {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
        data: {
            action: "getModuleInfo",
            id: _id,
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
        if (data.state != 'ok') {
            $('#div_alert').showAlert({message: data.result, level: 'danger'});
            return;
        }
        $('.zwaveInfo').value('');
        for (var i in data.result) {
            var value = data.result[i]['value'];
            if (isset(data.result[i]['unite'])) {
                value += ' ' + data.result[i]['unite'];
            }
            $('.zwaveInfo[data-l1key=' + i + ']').value(value);
            $('.zwaveInfo[data-l1key=' + i + ']').attr('title', data.result[i]['datetime']);
        }
    }
});
}

function syncEqLogicWithRazberry() {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
        data: {
            action: "syncEqLogicWithRazberry",
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
        window.location.reload();
    }
});
}

function changeIncludeState(_mode, _state) {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
        data: {
            action: "changeIncludeState",
            mode: _mode,
            state: _state,
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
    }
});
}

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<div class="row">';
    tr += '<div class="col-sm-6">';
    tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>';
    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '</div>';
    tr += '<div class="col-sm-6">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '</div>';
    tr += '</div>';
    tr += '<select class="cmdAttr form-control tooltips input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{La valeur de la commande vaut par défaut la commande}}">';
    tr += '<option value="">Aucune</option>';
    tr += '</select>';
    tr += '</td>';
    tr += '<td class="expertModeVisible">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td class="expertModeVisible"><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="instanceId" value="0"></td>';
    tr += '<td class="expertModeVisible"><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="class" ></td>';
    tr += '<td class="expertModeVisible">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" >';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateValue" placeholder="{{Valeur retour d\'état}}" style="margin-top : 5px;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateTime" placeholder="{{Durée avant retour d\'état (min)}}" style="margin-top : 5px;">';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" /> {{Historiser}}<br/></span>';
    tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
    tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr" data-l1key="eventOnly" /> {{Evénement}}<br/></span>';
    tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary" /> {{Inverser}}<br/></span>';
    tr += '<input style="width : 150px;" class="tooltips cmdAttr form-control expertModeVisible input-sm" data-l1key="cache" data-l2key="lifetime" placeholder="{{Lifetime cache}}">';
    tr += '</td>';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control tooltips input-sm" data-l1key="unite"  style="width : 100px;" placeholder="Unité" title="{{Unité}}">';
    tr += '<input class="tooltips cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="margin-top : 5px;"> ';
    tr += '<input class="tooltips cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="margin-top : 5px;">';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        filter: {type: 'info'},
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result);
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });
}
