<?php
require_once dirname(__FILE__) . "/../../../../../core/php/core.inc.php";
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	echo '<div class="alert alert-danger div_alert">';
	echo translate::exec('401 - Accès non autorisé');
	echo '</div>';
	die();
}

?>
<legend>Mémoires du clavier</legend>
<div class="alert alert-info">
            Info : <br/>
            - Ce tableau vous permet de visualiser les mémoires occupées sur votre clavier<br/>
			- Pour enregistrer un nouveau code cliquez sur le bouton Vert sur la mémoire désirée et suivez les étapes<br/>
			- Le zipato ne permet pas de supprimer une mémoire, cependant vous pouvez remplacer le code d'une mémoire exsitante<br/>
			- Il est impossible d'enregistrer le même code/badge sur deux mémoires différentes<br/>
			- Il est est impossible (par mesure de sécurité) de lire la valeur d'un code enregistré<br/>
			- Pour vider toutes vos mémoires, vous pouvez exclure et inclure le module<br/>
        </div>
<table class="table table-condensed table-bordered">
	<thead>
		<tr>
			<th>1</th>
			<th>2</th>
			<th>3</th>
			<th>4</th>
			<th>5</th>
			<th>6</th>
			<th>7</th>
			<th>8</th>
			<th>9</th>
			<th>10</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<?php
$data = zwave::callRazberry('/ZWaveAPI/Run/devices[' . init('logical_id') . '].instances[0].commandClasses[0x63].data');
for ($i = 1; $i < 11; $i++) {
	echo '<td>';
	echo '<a class="btn btn-success pull-right btn-xs bt_ziptatoKeypadSaveNewCode" data-position="' . $i . '"><i class="fa fa-floppy-o"></i></a>';
	if (isset($data[$i])) {
		echo '<i class="fa fa-check"></i>';
		//echo '<a class="btn btn-danger pull-right btn-xs"><i class="fa fa-times"></i></a>';
	} else {
		echo '<i class="fa fa-times"></i>';
	}
	echo '</td>';
}
?>
		</tr>
	</tbody>
</table>

<script>
	$('.bt_ziptatoKeypadSaveNewCode').on('click',function(){
		var datetime = Math.floor(Date.now() / 1000);
		var position = $(this).attr('data-position');
		bootbox.confirm('Allez vers votre clavier et appuyez sur Home et passez votre badge en étant sur d\'entendre le bip (dans le cas d\'un code appuyez sur Home , tapez le code et appuyez sur Enter) . Une fois fait cliquer sur D\'ACCORD !', function (result) {
			$.ajax({// fonction permettant de faire de l'ajax
                    type: "POST", // méthode de transmission des données au fichier php
                    url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
                    data: {
                    	action: "callRazberry",
                    	call: '/ZWaveAPI/Run/devices['+configureDeviceLogicalId+'].instances[0].commandClasses[0x63].data[0].code',
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
                    if((datetime-300) > data.result.updateTime){
                    	$('#div_configureDeviceAlert').showAlert({message: 'Erreur de lecture de badge/code, ou badge/code déjà utilisé dans une autre mémoire ', level: 'danger'});
                    	return;
                    }
                    var code = data.result.value;
                    var call = '/ZWaveAPI/Run/devices['+configureDeviceLogicalId+'].instances[0].commandClasses[0x63].SetRaw('+position+','+json_encode(code)+',1)';
                    $.ajax({// fonction permettant de faire de l'ajax
                    type: "POST", // méthode de transmission des données au fichier php
                    url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
                    data: {
                    	action: "callRazberry",
                    	call: call,
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
                    $('#div_configureDeviceAlert').showAlert({message: 'Code enregistré, merci de réveiller votre clavier pour qu\'il soit pris en compte (touche HOME puis 1 puis Enter) ', level: 'success'});
                    $('#tab_spe').load('plugins/zwave/core/config/devices/zipato.minikeypad.php?id='+configureDeviceId+'&logical_id='+configureDeviceLogicalId);
                }
            });
}
});
});
});
</script>