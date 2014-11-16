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

<center>
    <span class="label label-info"> Version Z-Way : <?php echo zwave::getZwaveInfo('controller::data::softwareRevisionVersion::value'); ?> </span>
</center><br/>

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
        <a class='btn btn-default btn-xs bt_adminRazberryAction' data-command="SerialAPISoftReset()" data-risk="{{Faible}}">{{Redemarrer le razberry}}</a>
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
                        command: command
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
