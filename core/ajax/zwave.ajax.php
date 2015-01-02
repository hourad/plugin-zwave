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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    if (init('action') == 'syncEqLogicWithRazberry') {
        zwave::syncEqLogicWithRazberry();
        ajax::success();
    }

    if (init('action') == 'changeIncludeState') {
        zwave::changeIncludeState(init('mode'), init('state'));
        ajax::success();
    }

    if (init('action') == 'getCommandClassInfo') {
        ajax::success(zwave::getCommandClassInfo(init('class')));
    }

    if (init('action') == 'launchInDebug') {
        log::clear('zwavecmd');
        zwave::restartZwayServer(true);
        ajax::success();
    }

    if (init('action') == 'restartZwayServer') {
        zwave::restartZwayServer();
        ajax::success();
    }

    if (init('action') == 'getModuleInfo') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        ajax::success($eqLogic->getInfo());
    }

    if (init('action') == 'getDeviceConfiguration') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        ajax::success($eqLogic->getDeviceConfiguration(init('forceRefresh', false), init('parameter_id', null)));
    }

    if (init('action') == 'setDeviceConfiguration') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        ajax::success($eqLogic->setDeviceConfiguration(json_decode(init('configurations'), true)));
    }

    if (init('action') == 'inspectQueue') {
        ajax::success(zwave::inspectQueue());
    }

    if (init('action') == 'getRoutingTable') {
        ajax::success(zwave::getRoutingTable());
    }

    if (init('action') == 'updateRoute') {
        ajax::success(zwave::updateRoute());
    }

    if (init('action') == 'copyDeviceConfiguration') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        $eqLogic->setDeviceConfigurationFromDevice(init('copy_id'));
        ajax::success();
    }

    if (init('action') == 'adminRazberry') {
        ajax::success(zwave::adminRazberry(init('command')));
    }

    if (init('action') == 'setWakeUp') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        $eqLogic->setWakeUp(init('wakeup'));
        ajax::success();
    }

    if (init('action') == 'setPolling') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        $eqLogic->setPolling(init('polling'));
        ajax::success();
    }

    if (init('action') == 'getZwaveInfo') {
        ajax::success(zwave::getZwaveInfo(init('path')));
    }

    if (init('action') == 'getAssociation') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        ajax::success($eqLogic->getAssociation());
    }

    if (init('action') == 'changeAssociation') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        ajax::success($eqLogic->changeAssociation(init('mode'), init('group'), init('node')));
    }

    if (init('action') == 'resendDeviceConfiguration') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        ajax::success($eqLogic->applyDeviceConfigurationCommand());
    }

    if (init('action') == 'deviceAdministation') {
        $eqLogic = zwave::byId(init('id'));
        if (!is_object($eqLogic)) {
            throw new Exception(__('Zwave eqLogic non trouvé : ', __FILE__) . init('id'));
        }
        if (init('command') == 'removeFailed') {
            ajax::success($eqLogic->removeFailed());
        }
        if (init('command') == 'markBatteryFailed') {
            ajax::success($eqLogic->markAsBatteryFailed());
        }
        if (init('command') == 'InterviewForce') {
            ajax::success($eqLogic->InterviewForce(init('instanceId'),init('classId')));
        }
    }

    if (init('action') == 'uploadConfZwave') {
        $uploaddir = dirname(__FILE__) . '/../config';
        if (!file_exists($uploaddir)) {
            mkdir($uploaddir);
        }
        $uploaddir .= '/devices/';
        if (!file_exists($uploaddir)) {
            mkdir($uploaddir);
        }
        if (!file_exists($uploaddir)) {
            throw new Exception(__('Répertoire d\'upload non trouvé : ', __FILE__) . $uploaddir);
        }
        if (!isset($_FILES['file'])) {
            throw new Exception(__('Aucun fichier trouvé. Vérifiez le paramètre PHP (post size limit)', __FILE__));
        }
        if (filesize($_FILES['file']['tmp_name']) > 2000000) {
            throw new Exception(__('Le fichier est trop gros (maximum 2Mo)', __FILE__));
        }
        if (!is_json(file_get_contents($_FILES['file']['tmp_name']))) {
            throw new Exception(__('Le fichier json est invalide', __FILE__));
        }
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploaddir . '/' . $_FILES['file']['name'])) {
            throw new Exception(__('Impossible de déplacer le fichier temporaire', __FILE__));
        }
        ajax::success();
    }

    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
