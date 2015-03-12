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
<div id='div_adminRazberryAlert' style="display: none;"></div>


    <?php
foreach (zwave::listServerZway() as $id => $server) {
	if (isset($server['name'])) {
		$infos = zwave::callRazberry('/ZWaveAPI/Data/0', $id);
		?>
        <center>
        <span class="label label-success" style="font-size : 1em;margin-right : 3px;"> {{Serveur}} : <?php echo $server['name'];?></span>
        <span class="label label-primary" style="font-size : 1em;margin-right : 3px;"> {{Version Z-Way}} : <?php echo $infos['controller']['data']['softwareRevisionVersion']['value'];?></span>
        <span class="label label-primary" style="font-size : 1em;margin-right : 3px;"> {{Version puce zwave}} : <?php echo $infos['controller']['data']['ZWaveChip']['value'];?> </span>
        <span class="label label-primary" style="font-size : 1em;margin-right : 3px;"> {{SDK}} : <?php echo $infos['controller']['data']['SDK']['value'];?> </span>
        <span class="label label-primary" style="font-size : 1em;margin-right : 3px;"> {{API Version}} : <?php echo $infos['controller']['data']['APIVersion']['value'];?> </span>
        </center><br/>
        <?php	}
}
?>

<span class='pull-right expertModeVisible'>
    <select class="form-control" style="width : 200px;" id="sel_adminRazberryServerId">
        <?php
foreach (zwave::listServerZway() as $id => $server) {
	if (isset($server['name'])) {
		echo '<option value="' . $id . '">' . $server['name'] . '</option>';
	}
}
?>
  </select>
</span>
<br/><br/><br/>

<table class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th>{{Action}}</th>
            <th>{{Explication}}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <center>
                    <a class='btn btn-default btn-xs bt_adminRazberryAction' data-command="SerialAPISoftReset()" data-risk="{{Faible}}">{{Redémarrer le razberry}}</a>
                </center>
            </td>
            <td>
                {{Cette fonction effectue un redémarrage logiciel du firmware de la puce du contrôleur Z-Wave sans
                suppression de toute information de réseau ou un réglage. Il peut être nécessaire pour recupérer la puce d'un état bloqué.
                Une situation typique d'une puce redémarrage requis est si la puce Z-Wave échoue à venir
                de retour de l'inclusion ou de l'état d'exclusion.}}
            </td>
        </tr>
        <tr>
            <td>
                <center>
                    <a class='btn btn-warning btn-xs bt_adminRazberryAction' data-command="cureZwaveNetwork" data-risk="{{Moyenne}}">{{Soigner le reseaux Zwave automatiquement}}</a>
                </center>
            </td>
            <td>
                {{Permet de lancer une tentative de soin automatique du reseaux zwave (envoi du NIF, demande de tous les NIF, interview de tous les modules et mise à jour des routes). Cela peut paralyser le reseaux zwave pendant plusieurs minutes/heures}}
            </td>
        </tr>
        <tr>
            <td>
                <center>
                    <a class='btn btn-default btn-xs bt_adminRazberryAction' data-command="SendNodeInformation()" data-risk="{{Faible}}">{{Envoyer mon NIF}}</a>
                </center>
            </td>
            <td>
                {{Dans certaines configurations réseau il peut être nécessaire d'envoyer le Node Id
                du contrôleur Z-Way. Ceci est particulièrement utile pour l'utilisation de certaines télécommandes pour la scène
                activation. Se référer au manuel de la télécommande pour donner plus d'
                informations quand et comment utiliser cette fonction.}}
            </td>
        </tr>

        <tr>
            <td>
                <center>
                    <a class='btn btn-default btn-xs bt_adminRazberryAction' data-command="RequestNodeInformation()" data-risk="{{Faible}}">{{Demander tous les NIF}}</a>
                </center>
            </td>
            <td>
                {{Cette fonction va appeler le nœud Informations cadre de tous les périphériques du réseau. Ceci peut
                être nécessaires en cas de changement de matériel ou lorsque tous les dispositifs où fournis avec un câble USB portable coller comme par exemple Aeon Labs Z-Stick. Les appareils fonctionnant sur secteur retourneront leur FNI immédiatement, les dispositifs à piles vont réagir après la prochaine activation.}}
            </td>
        </tr>

        <tr>
            <td>
                <center>
                    <a class='btn btn-default btn-xs bt_adminRazberryAction' data-command="InterviewForce" data-risk="{{Faible}}">{{Forcer re-interview}}</a>
                </center>
            </td>
            <td>
                {{Force tous les modules à renvoyer toutes leurs informations}}
            </td>
        </tr>

        <tr>
            <td>
                <center>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(0)" data-risk="{{Moyen}}"> {{EU}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(1)" data-risk="{{Moyen}}"> {{RU}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(2)" data-risk="{{Moyen}}"> {{IN}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(6)" data-risk="{{Moyen}}"> {{CN}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(10)" data-risk="{{Moyen}}"> {{MY}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(4)" data-risk="{{Moyen}}"> {{ANZ/BR}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(5)" data-risk="{{Moyen}}"> {{HK}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(5)" data-risk="{{Moyen}}"> {{KR}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(8)" data-risk="{{Moyen}}"> {{JP}}</a>
                    <a class='btn btn-primary btn-xs bt_adminRazberryAction' style="color : white;margin-top : 5px;" data-command="ZMEFreqChange(3)" data-risk="{{Moyen}}"> {{US}}</a>
                </center>
            </td>
            <td>
                {{Choix de la région (influe sur la fréquence du zwave)}}
            </td>
        </tr>

        <tr>
            <td>
                <center>
                    <a class='btn btn-danger btn-xs bt_adminRazberryAction' style="color : white;" data-command="ControllerChange(1)" data-risk="{{Elevé}}"> {{Démarrer le changement du contrôleur primaire}}</a><br/><br/>
                    <a class='btn btn-success btn-xs bt_adminRazberryAction' style="color : white;" data-command="ControllerChange(0)" data-risk="{{Faible}}"> {{Arrêter le changement du contrôleur primaire}}</a>
                </center>
            </td>
            <td>
                {{La fonction de changement de contrôleur permet le transfert de la fonction primaire à un autre contrôleur du
                réseau. La fonction fonctionne comme une fonction d'inclusion normale, mais remettra le primaire
                privilège de la nouvelle commande après l'inscription. Z-Way va devenir un contrôleur secondaire du
                réseau. Cette fonction peut être nécessaire lors de l'installation de réseaux plus importants sur la base de la télécommande des contrôles que lorsque Z-Way est uniquement utilisé pour faire une configuration de réseau pratique et le primaire
                Enfin fonction est remis une des télécommandes.}}
            </td>
        </tr>

        <tr>
            <td>
                <center>
                    <a class='btn btn-default btn-xs bt_adminRazberryAction' data-command="controller.SetLearnMode(1)" data-risk="{{Moyen}}">{{M'inclure dans un reseaux zwave}}</a>
                </center>
            </td>
            <td>
                {{M'inclure dans un nouveau reseaux Zwave (pour etre controleur secondaire par exemple)}}
            </td>
        </tr>
    </tbody>
</table>

<script>
    $('.bt_adminRazberryAction').on('click', function() {
        var risk = $(this).attr('data-risk');
        var command = $(this).attr('data-command');
        bootbox.confirm('{{Etes-vous sûr de vouloir effectuer l\'opération :}} <b>' + command + '</b>. {{Le risque de l\'opération est :}}  <b>' + risk + '</b> ?', function(result) {
            if (result) {
                $.ajax({// fonction permettant de faire de l'ajax
                    type: "POST", // methode de transmission des données au fichier php
                    url: "plugins/zwave/core/ajax/zwave.ajax.php", // url du fichier php
                    data: {
                        action: "adminRazberry",
                        command: command,
                        serverId: $('#sel_adminRazberryServerId').value(),
                    },
                    dataType: 'json',
                    error: function(request, status, error) {
                        handleAjaxError(request, status, error, $('#div_adminRazberryAlert'));
                    },
                    success: function(data) { // si l'appel a bien fonctionné
                    if (data.state != 'ok') {
                        $('#div_adminRazberryAlert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    $('#div_adminRazberryAlert').showAlert({message: '{{Opération lancée avec succès. En fonction de l\'opération celle-ci peut mettre plusieurs minutes à se réaliser.}}', level: 'success'});
                }
            });
}
});
});

</script>
