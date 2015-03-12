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
include_file('3rdparty', 'jsonTree/jsonTree', 'css', 'zwave');
include_file('3rdparty', 'jsonTree/jsonTree', 'js', 'zwave');

$json = zwave::callRazberry('/ZWaveAPI/Run/devices[' . $eqLogic->getLogicalId() . ']', $eqLogic->getConfiguration('serverID', 1));
sendVarToJs('zwaveDataTree', $json);
?>
<div id="div_zwaveDataTree"></div>
<script>
    $('#div_zwaveDataTree').html(JSONTree.create(zwaveDataTree));
</script>
