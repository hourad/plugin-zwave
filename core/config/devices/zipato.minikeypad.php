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
<legend>Mémoire</legend>
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
for ($i = 0; $i < 10; $i++) {
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
		bootbox.confirm('Allez vers votre clavier et appuyer sur home et passé votre badge en etant sur d\'entendre le bip (dans le cas d\'un code appuyer sur home , taper le code et appuyer sur enter) . Une fois fait cliquer sur OK !', function (result) {
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
                    if(datetime > data.result.updateTime){
                    	$('#div_configureDeviceAlert').showAlert({message: 'Erreur de lecture de badge', level: 'danger'});
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
                    $('#div_configureDeviceAlert').showAlert({message: 'Code enregistré, merci de reveiller votre clavier pour qu\'il soit pris en compte (touche HOME) ', level: 'success'});
                    $('#tab_spe').load('plugins/zwave/core/config/devices/zipato.minikeypad.php?id='+configureDeviceId+'&logical_id='+configureDeviceLogicalId);
                }
            });
}
});
});
});
</script>