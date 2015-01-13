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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class zwave extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    public static function sick() {
        echo "Server zwave : " . config::byKey('zwaveAddr', 'zwave') . "\n";
        echo "Port : 8083\n";
        echo "Is openZwave : " . config::byKey('isOpenZwave', 'zwave', 0) . "\n";
        echo "Test connection to zwave server...";
        try {
            self::callRazberry('/ZWaveAPI/Data/0');
            echo "OK\n";
        } catch (Exception $e) {
            echo "NOK\n";
            echo "Description : " . $e->getMessage();
            echo "\n";
        }
    }

    public static function callRazberry($_url) {
        $url = 'http://' . config::byKey('zwaveAddr', 'zwave') . ':8083' . $_url;
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true
            ));
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $curl_error = curl_error($ch);
            curl_close($ch);
            throw new Exception(__('Echec de la requete http : ', __FILE__) . $url . ' Curl error : ' . $curl_error, 404);
        }
        curl_close($ch);
        if (strpos($result, 'Error 500: Server Error') === 0 || strpos($result, 'Error 500: Internal Server Error') === 0) {
            if (strpos($result, 'Code took too long to return result') === false) {
                throw new Exception('Echec de la commande : ' . $_url . '. Erreur : ' . $result, 500);
            }
        }
        if (is_json($result)) {
            return json_decode($result, true);
        } else {
            return $result;
        }
    }

    public static function start() {
        sleep(10);
        foreach (self::byType('zwave') as $eqLogic) {
            try {
                $eqLogic->InterviewForce();
            } catch (Exception $e) {

            }
        }
    }

    public static function getZwaveInfo($_path) {
        $results = self::callRazberry('/ZWaveAPI/Data/0');
        $paths = explode('::', $_path);
        foreach ($paths as $path) {
            if (isset($results[$path])) {
                $results = $results[$path];
            } else {
                return null;
            }
        }
        return $results;
    }

    public static function pull() {
        $cache = cache::byKey('zwave::lastUpdate');
        $results = self::callRazberry('/ZWaveAPI/Data/' . $cache->getValue(0));
        if (!is_array($results)) {
            return;
        }
        foreach ($results as $key => $result) {
            if ($key == 'controller.data.controllerState') {
                nodejs::pushUpdate('zwave::' . $key, $result['value']);
            } else if ($key == 'controller.data.lastExcludedDevice') {
                if ($result['value'] != null) {
                    nodejs::pushUpdate('jeedom::alert', array(
                        'level' => 'warning',
                        'message' => __('Un périphérique Z-Wave vient d\'être exclu. Logical ID : ', __FILE__) . $result['value']
                        ));
                    sleep(5);
                    self::syncEqLogicWithRazberry();
                }
            } else if ($key == 'controller.data.lastIncludedDevice') {
                if ($result['value'] != null) {
                    $eqLogic = self::byLogicalId($result['value'], 'zwave');
                    if (!is_object($eqLogic)) {
                        nodejs::pushUpdate('jeedom::alert', array(
                            'level' => 'warning',
                            'message' => __('Nouveau module Z-Wave détecté. Début de l\'intégration', __FILE__)
                            ));
                        sleep(5);
                        self::syncEqLogicWithRazberry();
                    }
                }
            } else if ($key == 'controller') {
                if (isset($result['controllerState'])) {
                    nodejs::pushUpdate('zwave::controller.data.controllerState', $result['controllerState']['value']);
                }
                if (isset($result['lastIncludedDevice']) && $result['lastIncludedDevice']['value'] != null) {
                    $eqLogic = self::byLogicalId($result['value'], 'zwave');
                    if (!is_object($eqLogic)) {
                        nodejs::pushUpdate('jeedom::alert', array(
                            'level' => 'warning',
                            'message' => __('Nouveau module Z-Wave détecté. Début de l\'intégration', __FILE__)
                            ));
                        sleep(5);
                        self::syncEqLogicWithRazberry();
                    }
                }
                if (isset($result['lastExcludedDevice']) && $result['lastExcludedDevice']['value'] != null) {
                    nodejs::pushUpdate('jeedom::alert', array(
                        'level' => 'warning',
                        'message' => __('Un périphérique Z-Wave vient d\'être exclu. Logical ID : ', __FILE__) . $result['value']
                        ));
                    sleep(5);
                    self::syncEqLogicWithRazberry();
                }
            } else if ($key == 'devices') {
                foreach ($result as $device_id => $data) {
                    $eqLogic = self::byLogicalId($device_id, 'zwave');
                    if (is_object($eqLogic)) {
                        if ($eqLogic->getConfiguration('device') == 'fibaro.fgs221.pilote') {
                            foreach ($eqLogic->searchCmdByConfiguration('pilotWire', 'info') as $cmd) {
                                $cmd->event($cmd->getPilotWire(), 0);
                                break;
                            }
                            continue;
                        }
                        foreach ($eqLogic->getCmd('info') as $cmd) {
                            $class_id = hexdec($cmd->getConfiguration('class'));
                            $instance_id = $cmd->getConfiguration('instanceId', 0);
                            if (isset($data['instances'][$instance_id]['commandClasses'][$class_id])) {
                                $data_values = explode('.', str_replace(array(']', '['), array('', '.'), $cmd->getConfiguration('value')));
                                $value = $data['instances'][$instance_id]['commandClasses'][$class_id];
                                foreach ($data_values as $data_value) {
                                    if (isset($value[$data_value])) {
                                        $value = $value[$data_value];
                                    }
                                }
                                if (!isset($value['updateTime']) || $value['updateTime'] >= $cache->getValue(0)) {
                                    $cmd->handleUpdateValue($value);
                                }
                            }
                        }
                    }
                }
            } else {
                $explodeKey = explode('.', $key);
                if (!isset($explodeKey[1])) {
                    continue;
                }
                if ($explodeKey[1] == 1) {
                    if (isset($results['devices.1.instances.0.commandClasses.' . $explodeKey[5] . '.data.srcNodeId'])) {
                        $explodeKey[1] = $results['devices.1.instances.0.commandClasses.' . $explodeKey[5] . '.data.srcNodeId']['value'];
                        $eqLogic = self::byLogicalId($results['devices.1.instances.0.commandClasses.' . $explodeKey[5] . '.data.srcNodeId']['value'], 'zwave');
                        if (is_object($eqLogic)) {
                            foreach ($eqLogic->getCmd('info') as $cmd) {
                                if ($cmd->getConfiguration('instanceId') == $explodeKey[3]) {
                                    try {
                                        $cmd->forceUpdate();
                                    } catch (Exception $e) {

                                    }
                                }
                            }
                        }
                    }
                }
                $eqLogic = self::byLogicalId($explodeKey[1], 'zwave');
                if (is_object($eqLogic)) {
                    if (isset($value['hasCode'])) {
                        foreach ($eqLogic->searchCmdByConfiguration('code', 'info') as $cmd) {
                            $cmd->event($cmd->execute(), 0);
                            break;
                        }
                        continue;
                    }
                    if (count($explodeKey) == 5) {
                        foreach ($result as $class => $value) {
                            if ($eqLogic->getConfiguration('device') == 'fibaro.fgs221.pilote') {
                                foreach ($eqLogic->searchCmdByConfiguration('pilotWire', 'info') as $cmd) {
                                    $cmd->event($cmd->getPilotWire(), 0);
                                    break;
                                }
                                continue;
                            }
                            foreach ($eqLogic->getCmd('info') as $cmd) {
                                foreach ($eqLogic->getCmd('info', $explodeKey[3] . '.0x' . dechex($explodeKey[5]), null, true) as $cmd) {
                                    $configurationValues = explode('.', str_replace(array(']', '['), array('', '.'), $cmd->getConfiguration('value')));
                                    foreach ($configurationValues as $configurationValue) {
                                        if (isset($value[$configurationValue])) {
                                            $value = [$configurationValue];
                                        }
                                    }
                                    $cmd->handleUpdateValue($value);
                                }
                            }
                        }
                    } else if (count($explodeKey) > 5) {
                        if ($eqLogic->getConfiguration('device') == 'fibaro.fgs221.pilote') {
                            foreach ($eqLogic->searchCmdByConfiguration('pilotWire', 'info') as $cmd) {
                                $cmd->event($cmd->getPilotWire(), 0);
                                break;
                            }
                            continue;
                        }
                        $attribut = implode('.', array_slice($explodeKey, 6));
                        foreach ($eqLogic->getCmd('info', $explodeKey[3] . '.0x' . dechex($explodeKey[5]), null, true) as $cmd) {
                            if (strpos(str_replace(array(']', '['), array('', '.'), $cmd->getConfiguration('value')), $attribut) !== false) {
                                $cmd->handleUpdateValue($result);
                            }
                        }
                    }
                }
            }
        }
        if (isset($results['updateTime'])) {
            cache::set('zwave::lastUpdate', $results['updateTime'], 0);
        } else {
            cache::set('zwave::lastUpdate', 0, 0);
        }
    }

    public static function syncEqLogicWithRazberry() {
        $results = self::callRazberry('/ZWaveAPI/Data/0');
        $findDevice = array();
        $include_device = '';
        $razberry_id = zwave::getZwaveInfo('controller::data::nodeId::value');
        $findConfiguration = true;
        foreach ($results['devices'] as $nodeId => $result) {
            $findDevice[$nodeId] = $nodeId;
            if ($nodeId != $razberry_id) {
             if (!is_object(self::byLogicalId($nodeId, 'zwave'))) {
                $eqLogic = new eqLogic();
                $eqLogic->setEqType_name('zwave');
                $eqLogic->setIsEnable(1);
                $eqLogic->setName('Device ' . $nodeId);
                $eqLogic->setLogicalId($nodeId);
                $eqLogic->setIsVisible(1);
                $eqLogic->save();
                $eqLogic = zwave::byId($eqLogic->getId());
                $eqLogic->InterviewForce();
                for ($i = 0; $i < 60; $i++) {
                    nodejs::pushUpdate('jeedom::alert', array(
                        'level' => 'warning',
                        'message' => __('Pause de ', __FILE__) . (60 - $i) . __(' pour interview forcé du module', __FILE__)
                        ));
                    sleep(1);
                }
                $include_device = $eqLogic->getId();
                $findConfiguration = false;
                $result = self::callRazberry('/ZWaveAPI/Run/devices[' . $eqLogic->getLogicalId() . ']');
                $data = $result['data'];
                print_r($data);
                /* Reconnaissance du module */
                foreach (self::devicesParameters() as $device_id => $device) {
                    if ($device['manufacturerId'] == $data['manufacturerId']['value'] && $device['manufacturerProductType'] == $data['manufacturerProductType']['value'] && $device['manufacturerProductId'] == $data['manufacturerProductId']['value']) {
                        $findConfiguration = true;
                        nodejs::pushUpdate('jeedom::alert', array(
                            'level' => 'warning',
                            'message' => __('Périphérique reconnu : ', __FILE__) . $device['name'] . '!! (Manufacturer ID : ' . $data['manufacturerId']['value'] . ', Product type : ' . $data['manufacturerProductType']['value'] . ', Product ID : ' . $data['manufacturerProductId']['value'] . __('). Configuration en cours veuillez patienter...', __FILE__)
                            ));
                        $eqLogic->setConfiguration('device', $device_id);
                        $eqLogic->save();
                        for ($i = 0; $i < 5; $i++) {
                            nodejs::pushUpdate('jeedom::alert', array(
                                'level' => 'warning',
                                'message' => __('Pause de ', __FILE__) . (5 - $i) . __(' secondes pour synchronisation avec le module', __FILE__)
                                ));
                            sleep(1);
                        }
                        nodejs::pushUpdate('jeedom::alert', array(
                            'level' => 'warning',
                            'message' => __('Mise à jour forcée des valeurs des commandes', __FILE__)
                            ));
                        $eqLogic->forceUpdate();
                        break;
                    }
                }
            }
        }
    }
    if (config::byKey('autoRemoveExcludeDevice', 'zwave') == 1 && count($findDevice) > 1) {
        foreach (self::byType('zwave') as $eqLogic) {
            if (!isset($findDevice[$eqLogic->getLogicalId()])) {
                $eqLogic->remove();
            }
        }
    }
    nodejs::pushUpdate('zwave::includeDevice', $include_device);
    if (!$findConfiguration) {
        nodejs::pushUpdate('jeedom::alert', array(
            'level' => 'warning',
            'message' => __('Votre module n\'est pas reconnu, veuillez récupérer sa configuration sur le market si celle ci est disponible', __FILE__)
            ));
    } else {
        nodejs::pushUpdate('jeedom::alert', array(
            'level' => 'warning',
            'message' => ''
            ));
    }
}

public static function restartZwayServer($_debug = false) {
    if ($_debug) {
        $cmd = 'sudo su -; ';
        $cmd .= 'killall -9 z-way-server; ';
        $cmd .= 'export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/opt/z-way-server/libs; ';
        $cmd .= '&& cd /opt/z-way-server; ';
        $cmd .= './z-way-server >> ' . log::getPathToLog('zwavecmd') . ' 2>&1 &';
    } else {
        $output = array();
        $retval = 0;
        exec('sudo killall -9 z-way-server; sudo service z-way-server start', $output, $retval);
        if ($retval != 0) {
            throw new Exception(__('Impossible de redémarrer le serveur zway (problème de droits ?) : ', __FILE__) . print_r($output, true));
        }
    }
}

public static function changeIncludeState($_mode, $_state) {
    if ($_mode == 1) {
        self::callRazberry('/ZWaveAPI/Run/controller.AddNodeToNetwork(' . $_state . ')');
    } else {
        self::callRazberry('/ZWaveAPI/Run/controller.RemoveNodeFromNetwork(' . $_state . ')');
    }
}

public static function getCommandClassInfo($_class) {
    global $listClassCommand;
    include_file('core', 'class.command', 'config', 'zwave');
    if (isset($listClassCommand[$_class])) {
        return $listClassCommand[$_class];
    }
    return array();
}

public static function cron() {
        //Rafraichissement des valeurs des modules
    foreach (eqLogic::byType('zwave') as $eqLogic) {
        if ($eqLogic->getIsEnable() == 1) {
            $scheduler = $eqLogic->getConfiguration('refreshDelay', '');
            if ($scheduler != '') {
                try {
                    $c = new Cron\CronExpression($scheduler, new Cron\FieldFactory);
                    if ($c->isDue()) {
                        try {
                            foreach ($eqLogic->getCmd() as $cmd) {
                                $cmd->forceUpdate();
                            }
                        } catch (Exception $exc) {
                            log::add('zwave', 'error', __('Erreur pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $exc->getMessage());
                        }
                    }
                } catch (Exception $exc) {
                    log::add('zwave', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $scheduler);
                }
            }
        }
    }
    if (config::byKey('jeeNetwork::mode') == 'slave') {
        $cron = cron::byClassAndFunction('zwave', 'pull');
        if (is_object($cron)) {
            $cron->remove();
        }
    }
}

public static function cronDaily() {
    foreach (zwave::byType('zwave') as $eqLogic) {
        if ($eqLogic->getConfiguration('noBatterieCheck') != 1) {
            try {
                self::callRazberry('/ZWaveAPI/Run/devices[' . $eqLogic->getLogicalId() . '].instances[0].commandClasses[0x80].Get()');
                $info = $eqLogic->getInfo();
                if (isset($info['battery']) && $info['battery'] !== '') {
                    $eqLogic->batteryStatus($info['battery']['value']);
                }
            } catch (Exception $exc) {

            }
        }
    }
}

public static function inspectQueue() {
    $results = self::callRazberry('/ZWaveAPI/InspectQueue');
    $return = array();
    foreach ($results as $result) {
        $queue = array();
        $queue['timeout'] = $result[0];
        $queue['id'] = $result[2];
        $eqLogic = zwave::byLogicalId($queue['id'], 'zwave');
        $queue['name'] = '';
        if (is_object($eqLogic)) {
            $queue['name'] = $eqLogic->getHumanName();
        }
        $queue['description'] = $result[3];
        $queue['status'] = $result[4];
        if ($queue['status'] == null) {
            $queue['status'] = '';
        }
        $status = $result[1];
        if ($status[1] == 1) {
            $queue['status'] .= ' [Wait wakeup]';
        }
        $queue['sendCount'] = $status[0];
        $return[] = $queue;
    }
    return $return;
}

public static function getRoutingTable() {
    $results = self::callRazberry('/ZWaveAPI/Data/0');
    $razberry_id = zwave::getZwaveInfo('controller::data::nodeId::value');
    $return = array();
    $nb = count($results['devices']);
    foreach ($results['devices'] as $id => $device) {
        $return[$id] = $device;
        if ($id == $razberry_id) {
            $return[$id]['name'] = 'Razberry';
        } else {
            $return[$id]['name'] = $id;
            if ($nb < 25) {
                $eqLogic = zwave::byLogicalId($id, 'zwave');
                if (is_object($eqLogic)) {
                    $return[$id]['name'] = $eqLogic->getHumanName();
                }
            }
        }
    }
    return $return;
}

public static function updateRoute() {
    self::callRazberry('/ZWaveAPI/Run/controller.RequestNetworkUpdate()');
    foreach (eqLogic::byType('zwave') as $eqLogic) {
        self::callRazberry('/ZWaveAPI/Run/devices[' . $eqLogic->getLogicalId() . '].RequestNodeNeighbourUpdate()');
        if (config::byKey('isOpenZwave', 'zwave', 0) == 1) {
            sleep(20);
        }
    }
}

public static function devicesParameters($_device = '') {
    $path = dirname(__FILE__) . '/../config/devices';
    if (isset($_device) && $_device != '') {
        $files = ls($path, $_device . '.json', false, array('files', 'quiet'));
        if (count($files) == 1) {
            try {
                $content = file_get_contents($path . '/' . $files[0]);
                if (is_json($content)) {
                    $deviceConfiguration = json_decode($content, true);
                    return $deviceConfiguration[$_device];
                }
                return array();
            } catch (Exception $e) {
                return array();
            }
        }
    }
    $files = ls($path, '*.json', false, array('files', 'quiet'));
    $return = array();
    foreach ($files as $file) {
        try {
            $content = file_get_contents($path . '/' . $file);
            if (is_json($content)) {
                $return += json_decode($content, true);
            }
        } catch (Exception $e) {

        }
    }

    if (isset($_device) && $_device != '') {
        if (isset($return[$_device])) {
            return $return[$_device];
        }
        return array();
    }
    return $return;
}

/*     * *************************MARKET**************************************** */

public static function shareOnMarket(&$market) {
    $moduleFile = dirname(__FILE__) . '/../config/devices/' . $market->getLogicalId() . '.json';
    if (!file_exists($moduleFile)) {
        throw new Exception('Impossible de trouver le fichier de configuration ' . $moduleFile);
    }
    $tmp = dirname(__FILE__) . '/../../../../tmp/' . $market->getLogicalId() . '.zip';
    if (file_exists($tmp)) {
        if (!unlink($tmp)) {
            throw new Exception(__('Impossible de supprimer : ', __FILE__) . $tmp . __('. Vérifiez les droits', __FILE__));
        }
    }
    if (!create_zip($moduleFile, $tmp)) {
        throw new Exception(__('Echec de création du zip. Répertoire source : ', __FILE__) . $moduleFile . __(' / Répertoire cible : ', __FILE__) . $tmp);
    }
    return $tmp;
}

public static function getFromMarket(&$market, $_path) {
    $cibDir = dirname(__FILE__) . '/../config/devices/';
    if (!file_exists($cibDir)) {
        throw new Exception(__('Impossible d\'installer la configuration du module le répertoire n\'existe pas : ', __FILE__) . $cibDir);
    }
    $zip = new ZipArchive;
    if ($zip->open($_path) === TRUE) {
        $zip->extractTo($cibDir . '/');
        $zip->close();
    } else {
        throw new Exception('Impossible de décompresser le zip : ' . $_path);
    }
    $moduleFile = dirname(__FILE__) . '/../config/devices/' . $market->getLogicalId() . '.json';
    if (!file_exists($moduleFile)) {
        throw new Exception(__('Echec de l\'installation. Impossible de trouver le module ', __FILE__) . $moduleFile);
    }

    foreach (eqLogic::byTypeAndSearhConfiguration('zwave', $market->getLogicalId()) as $eqLogic) {
        $eqLogic->applyModuleConfiguration();
    }
}

public static function removeFromMarket(&$market) {
    $moduleFile = dirname(__FILE__) . '/../config/devices/' . $market->getLogicalId() . '.json';
    if (!file_exists($moduleFile)) {
        throw new Exception(__('Echec lors de la suppression. Impossible de trouver le module ', __FILE__) . $moduleFile);
    }
    if (!unlink($moduleFile)) {
        throw new Exception(__('Impossible de supprimer le fichier :  ', __FILE__) . $moduleFile . '. Veuillez vérifier les droits');
    }
}

public static function listMarketObject() {
    $return = array();
    foreach (zwave::devicesParameters() as $logical_id => $name) {
        $return[] = $logical_id;
    }
    return $return;
}

/*     * *************************BACKUP/RESTORATION**************************************** */

public static function backup($_path) {
    if (config::byKey('isOpenZwave', 'zwave', 0) == 0) {
        file_put_contents($_path . '/zway.zbk', fopen('http://' . config::byKey('zwaveAddr', 'zwave') . ':8083/ZWaveAPI/Backup', 'r'));
    }
}

public static function restore() {
    self::adminRazberry('RequestNodeInformation', true);
    self::adminRazberry('InterviewForce', true);
}

/*     * ************************************************************* */

public static function adminRazberry($_command, $_ignoreError = false) {
    if ($_command == 'RequestNodeInformation()') {
        foreach (zwave::byType('zwave') as $eqLogic) {
            if ($eqLogic->getLogicalId() != 1) {
                try {
                    self::callRazberry('/ZWaveAPI/Run/devices[' . $eqLogic->getLogicalId() . '].RequestNodeInformation()');
                } catch (Exception $e) {
                    if (!$_ignoreError) {
                        throw $e;
                    }
                }
            }
        }
        return true;
    }
    if ($_command == 'SerialAPISoftReset()') {
        try {
            self::callRazberry('/ZWaveAPI/Run/' . $_command);
        } catch (Exception $e) {
            if (!$_ignoreError) {
                throw $e;
            }
        }
        return true;
    }
    if ($_command == 'InterviewForce') {
        foreach (eqLogic::byType('zwave') as $eqLogic) {
            try {
                $eqLogic->InterviewForce();
            } catch (Exception $e) {
                if (!$_ignoreError) {
                    throw $e;
                }
            }
        }
        return true;
    }
    try {
        self::callRazberry('/ZWaveAPI/Run/controller.' . $_command);
    } catch (Exception $e) {
        if (!$_ignoreError) {
            throw $e;
        }
    }
    return true;
}

/*     * *********************Methode d'instance************************* */

public function forceUpdate($_commandOnly = false) {
    foreach ($this->getCmd() as $cmd) {
        try {
            $cmd->forceUpdate();
        } catch (Exception $e) {

        }
    }
    if (!$_commandOnly) {
        try {
            self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[0].commandClasses[0x80].Get()');
        } catch (Exception $e) {

        }
    }
}

public function getAssociation() {
    $results = self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[0].commandClasses[133].data');
    if (!isset($results['supported']) || !isset($results['supported']['value']) || $results['supported']['value'] == false) {
        throw new Exception(__('Ce module ne supporte pas la notion de groupe', __FILE__));
    }
    $hasGroup = false;
    $razberry_id = zwave::getZwaveInfo('controller::data::nodeId::value');
    foreach ($results as $group => &$values) {
        if (is_numeric($group)) {
            $hasGroup = true;
            $info_group = array();
            foreach ($values['nodes']['value'] as $node) {
                if ($node == $razberry_id) {
                    $info_group[] = array('id' => $node, 'name' => 'Jeedom');
                } else {
                    $eqLogic = zwave::byLogicalId($node);
                    if (is_object($eqLogic)) {
                        $info_group[] = array('id' => $node, 'name' => $eqLogic->getHumanName());
                    } else {
                        $info_group[] = array('id' => $node, 'name' => $node);
                    }
                }
            }
            $values['nodes']['value'] = $info_group;
        }
    }
    if (!$hasGroup) {
        self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[0].commandClasses[133].Get()');
        throw new Exception(__('Aucun groupe trouvé, veuillez retester dans 10 min le temps d\'interroger le module', __FILE__));
    }
    return $results;
}

public function changeAssociation($_mode, $_group, $_node = 1) {
    if ($_node == '' || !is_numeric($_node)) {
        throw new Exception(__('Vous devez mettre un node ID non vide et qui soit numérique', __FILE__));
    }
    if ($_group == '' || !is_numeric($_group)) {
        throw new Exception(__('Vous devez mettre un groupe non vide et qui soit numérique', __FILE__));
    }
    if ($_mode == 'remove') {
        self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[0].commandClasses[0x85].Remove(' . $_group . ',' . $_node . ')');
    }
    if ($_mode == 'add') {

        self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[0].commandClasses[0x85].Set(' . $_group . ',' . $_node . ')');
    }
    sleep(2);
}

public function getAvailableCommandClass() {
    $results = self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].commandClasses');
    $return = array();
    foreach ($results as $class => $value) {
        $return[] = '0x' . dechex(intval($class));
    }
    return $return;
}

public function ping() {
    $info = $this->getInfo();
    if ($info['state']['value'] == 'Réveillé') {
        $cmds = $this->getCmd();
        $cmds[0]->forceUpdate();
        if ($this->getStatus('lastCommunication', date('Y-m-d H:i:s')) < date('Y-m-d H:i:s', strtotime('-2 minutes' . date('Y-m-d H:i:s')))) {
            sleep(5);
        }
        if ($this->getStatus('lastCommunication', date('Y-m-d H:i:s')) < date('Y-m-d H:i:s', strtotime('-2 minutes' . date('Y-m-d H:i:s')))) {
            return false;
        }
    } else {
        if ($this->getStatus('lastCommunication', date('Y-m-d H:i:s')) < date('Y-m-d H:i:s', strtotime('-' . $this->getTimeout() . ' minutes' . date('Y-m-d H:i:s')))) {
            return false;
        }
    }
    return true;
}

public function getInfo($_infos = '') {
    $deviceConf = self::devicesParameters($this->getConfiguration('device'));
    $return = array();
    if (!is_numeric($this->getLogicalId())) {
        return $return;
    }
    if($_infos == ''){
        $results = self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . ']');
    }else{
        $results = $_infos['devices'][$this->getLogicalId()];
    }
    if ($this->getConfiguration('noBatterieCheck') != 1 && isset($results['instances']) && isset($results['instances'][0]) &&
        isset($results['instances'][0]['commandClasses']) && isset($results['instances'][0]['commandClasses'][128]) &&
        isset($results['instances'][0]['commandClasses'][128]['data']['supported']) && $results['instances'][0]['commandClasses'][128]['data']['supported']['value'] === true) {
        $return['battery'] = array(
            'value' => $results['instances'][0]['commandClasses'][128]['data']['last']['value'],
            'datetime' => date('Y-m-d H:i:s', $results['instances'][0]['commandClasses'][128]['data']['last']['updateTime']),
            'unite' => '%',
            );
}

if (isset($results['data'])) {
    if (isset($results['data']['isAwake'])) {
        $return['state'] = array(
            'value' => ($results['data']['isAwake']['value']) ? 'Réveillé' : 'Endormi',
            'datetime' => date('Y-m-d H:i:s', $results['data']['isAwake']['updateTime']),
            );
    }
    if (isset($results['data']['isFailed'])) {
        $return['state']['value'] = ($results['data']['isFailed']['value']) ? 'Dead' : $return['state']['value'];
    }
    if (isset($deviceConf['name'])) {
        $return['name'] = array(
            'value' => $deviceConf['name'],
            'datetime' => date('Y-m-d H:i:s'),
            );
    }
    if (isset($deviceConf['vendor'])) {
        $return['brand'] = array(
            'value' => $deviceConf['vendor'],
            'datetime' => date('Y-m-d H:i:s'),
            );
    } else {
        if (isset($results['data']['vendorString'])) {
            $return['brand'] = array(
                'value' => $results['data']['vendorString']['value'],
                'datetime' => date('Y-m-d H:i:s', $results['data']['vendorString']['updateTime']),
                );
        }
    }

    if (isset($results['data']['lastReceived'])) {
        $return['lastReceived'] = array(
            'value' => date('Y-m-d H:i:s', $results['data']['lastReceived']['updateTime']),
            'datetime' => date('Y-m-d H:i:s', $results['data']['lastReceived']['updateTime']),
            );
    }
    if (isset($results['data']['manufacturerId'])) {
        $return['manufacturerId'] = array(
            'value' => $results['data']['manufacturerId']['value'],
            );
    }
    if (isset($results['data']['manufacturerProductType'])) {
        $return['manufacturerProductType'] = array(
            'value' => $results['data']['manufacturerProductType']['value'],
            );
    }
    if (isset($results['data']['manufacturerProductId'])) {
        $return['manufacturerProductId'] = array(
            'value' => $results['data']['manufacturerProductId']['value'],
            );
    }
}
$return['interviewComplete'] = array(
    'value' => __('Complet', __FILE__),
    );

if (isset($results['instances']) && is_array($results['instances'])) {
    foreach ($results['instances'] as $instanceID => $instance) {
        foreach ($instance['commandClasses'] as $ccId => $commandClasses) {
            if (($ccId == 96 && $instanceID != 0) || (($ccId == 134 || $ccId == 114 || $ccId == 96) && $instanceID == 0)) {
                continue;
            }
            if (isset($commandClasses['data']) && isset($commandClasses['data']['supported']) && (!isset($commandClasses['data']['supported']['value']) || $commandClasses['data']['supported']['value'] != true)) {
                continue;
            }
            if (isset($commandClasses['data']) && isset($commandClasses['data']['interviewDone']) && (!isset($commandClasses['data']['interviewDone']['value']) || $commandClasses['data']['interviewDone']['value'] != true)) {
                $return['interviewComplete']['value'] = __('Incomplet', __FILE__);
            }
        }
    }
}
return $return;
}

public function getSameDevice() {
    return self::byTypeAndSearhConfiguration('zwave', $this->getConfiguration('device'));
}

public function getDeviceConfiguration($_forcedRefresh = false, $_parameters_id = null) {
    if ($_parameters_id == null) {
        $device = zwave::devicesParameters($this->getConfiguration('device'));
        if (!is_array($device) || count($device) == 0) {
            throw new Exception(__('Equipement inconnu : ', __FILE__) . $this->getConfiguration('device'));
        }
    } else {
        $device = array(
            'parameters' => array(
                $_parameters_id => array()
                ),
            );
    }
    $return = array();

    if (count($device['parameters']) > 0) {
        $needRefresh = false;
        if ($_forcedRefresh) {
            foreach ($device['parameters'] as $id => $parameter) {
                self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].commandClasses[0x70].Get(' . $id . ')');
            }
            sleep(4);
        }
        $data = self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].commandClasses[0x70].data');
        foreach ($device['parameters'] as $id => $parameter) {
            if (isset($data[$id])) {
                $return[$id] = array();
                $return[$id]['value'] = $data[$id]['val']['value'];
                $return[$id]['datetime'] = date('Y-m-d H:i:s', $data[$id]['val']['updateTime']);
                $return[$id]['size'] = $data[$id]['size']['value'];
                if ($data[$id]['val']['updateTime'] < $data[$id]['val']['invalidateTime']) {
                    $return[$id]['status'] = 'invalide';
                } else {
                    $return[$id]['status'] = 'ok';
                }
            } else {
                $needRefresh = true;
                self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].commandClasses[0x70].Get(' . $id . ')');
            }
        }
        if ($needRefresh) {
            sleep(2);
            $data = self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].commandClasses[0x70].data');
            foreach ($device['parameters'] as $id => $parameter) {
                if (isset($data[$id])) {
                    $return[$id] = array();
                    $return[$id]['value'] = $data[$id]['val']['value'];
                    $return[$id]['datetime'] = date('Y-m-d H:i:s', $data[$id]['val']['updateTime']);
                    $return[$id]['size'] = $data[$id]['size']['value'];
                    if ($data[$id]['val']['updateTime'] < $data[$id]['val']['invalidateTime']) {
                        $return[$id]['status'] = __('Invalide', __FILE__);
                    } else {
                        $return[$id]['status'] = __('OK', __FILE__);
                    }
                }
            }
        }
    }
    return $return;
}

public function setDeviceConfigurationFromDevice($_device_id) {
    $device = self::byId($_device_id);
    if (!is_object($device)) {
        throw new Exception(__('Impossible de trouver l\'équipement source : ', __FILE__) . $_device_id);
    }
    try {
        $this->setDeviceConfiguration($device->getDeviceConfiguration());
    } catch (Exception $e) {
        log::add('zwave', 'error', $e->getMessage());
    }
    try {
        if(is_numeric($device->getWakeUp()) && $device->getWakeUp() > 0){
            $this->setWakeUp($device->getWakeUp());
        }
    } catch (Exception $e) {
        log::add('zwave', 'error', $e->getMessage());
    }
    try {
        foreach ($this->getAssociation() as $group => $values) {
            if(is_numeric($group)){
                foreach ($values['nodes']['value'] as $node) {
                    $this->changeAssociation('remove', $group, $node['id']);
                }
            }
        }
        foreach ($device->getAssociation() as $group => $values) {
            if(is_numeric($group)){
                foreach ($values['nodes']['value'] as $node) {
                    $this->changeAssociation('add', $group, $node['id']);
                }
            }
        }
    } catch (Exception $e) {
        log::add('zwave', 'error', $e->getMessage());
    }

    if (config::byKey('isOpenZwave', 'zwave', 0) == 1) {
        try {
            $this->setPolling($device->getPolling());
        } catch (Exception $exc) {
            log::add('zwave', 'error', $e->getMessage());
        }
    }
}

public function setDeviceConfiguration($_configurations) {
    if (count($_configurations) > 0) {
        foreach ($_configurations as $id => $configuration) {
            if (isset($configuration['size']) && isset($configuration['value']) && is_numeric($configuration['size']) && is_numeric($configuration['value'])) {
                self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].commandClasses[0x70].Set(' . $id . ',' . $configuration['value'] . ',' . $configuration['size'] . ')');
            }
        }
    }
    $this->applyDeviceConfigurationCommand();
    return true;
}

public function postSave() {
    if ($this->getConfiguration('device') != $this->getConfiguration('applyDevice')) {
        $this->applyModuleConfiguration();
    }
}

public function applyModuleConfiguration() {
    $this->setConfiguration('applyDevice', $this->getConfiguration('device'));
    if ($this->getConfiguration('device') == '') {
        $this->save();
        return true;
    }
    $device = self::devicesParameters($this->getConfiguration('device'));
    if (!is_array($device) || !isset($device['commands'])) {
        return true;
    }
    if (isset($device['configuration'])) {
        foreach ($device['configuration'] as $key => $value) {
            try {
                $this->setConfiguration($key, $value);
            } catch (Exception $e) {

            }
        }
    }

    $cmd_order = 0;
    $link_cmds = array();
    $razberry_id = zwave::getZwaveInfo('controller::data::nodeId::value');
    nodejs::pushUpdate('jeedom::alert', array(
        'level' => 'warning',
        'message' => __('Mise en place des groupes par défaut', __FILE__)
        ));
    if (isset($device['groups']) && isset($device['groups']['associate'])) {
        foreach ($device['groups']['associate'] as $group) {
            try {
                $this->changeAssociation('add', $group, $razberry_id);
            } catch (Exception $e) {

            }
        }
    }
    nodejs::pushUpdate('jeedom::alert', array(
        'level' => 'warning',
        'message' => __('Création des commandes', __FILE__)
        ));

    if (isset($device['commands_openzwave']) && config::byKey('isOpenZwave', 'zwave', 0) == 1) {
        $commands = $device['commands_openzwave'];
    } else {
        $commands = $device['commands'];
    }

    foreach ($commands as $command) {
        if (!isset($command['configuration']['instanceId'])) {
            $command['configuration']['instanceId'] = 0;
        }
        if (!isset($command['configuration']['class'])) {
            $command['configuration']['class'] = '';
        }
        $cmd = null;
        foreach ($this->getCmd() as $liste_cmd) {
            if ($liste_cmd->getConfiguration('instanceId', 0) == $command['configuration']['instanceId'] &&
                $liste_cmd->getConfiguration('class') == $command['configuration']['class'] &&
                $liste_cmd->getConfiguration('value') == $command['configuration']['value']) {
                $cmd = $liste_cmd;
            break;
        }
    }

    try {
        if ($cmd == null || !is_object($cmd)) {
            $cmd = new zwaveCmd();
            $cmd->setOrder($cmd_order);
            $cmd->setEqLogic_id($this->getId());
        } else {
            $command['name'] = $cmd->getName();
        }
        utils::a2o($cmd, $command);
        if (isset($command['value'])) {
            $cmd->setValue(null);
        }
        $cmd->save();
        if (isset($command['value'])) {
            $link_cmds[$cmd->getId()] = $command['value'];
        }
        $cmd_order++;
    } catch (Exception $exc) {

    }
}

if (count($link_cmds) > 0) {
    foreach ($this->getCmd() as $eqLogic_cmd) {
        foreach ($link_cmds as $cmd_id => $link_cmd) {
            if ($link_cmd == $eqLogic_cmd->getName()) {
                $cmd = cmd::byId($cmd_id);
                if (is_object($cmd)) {
                    $cmd->setValue($eqLogic_cmd->getId());
                    $cmd->save();
                }
            }
        }
    }
}

try {
    nodejs::pushUpdate('jeedom::alert', array(
        'level' => 'warning',
        'message' => __('Récupération de la configuration d\'origine du module', __FILE__)
        ));
    $configuration = $this->getDeviceConfiguration(true);
    $optimiseConfigFound = false;
    foreach ($configuration as $id => &$parameter) {
        if (isset($device['parameters'][$id]['set'])) {
            $optimiseConfigFound = true;
            $configuration[$id]['value'] = $device['parameters'][$id]['set'];
        }
    }
    if ($optimiseConfigFound) {
        nodejs::pushUpdate('jeedom::alert', array(
            'level' => 'warning',
            'message' => __('Envoi de la configuration optimisée Jeedom', __FILE__)
            ));
        $this->setDeviceConfiguration($configuration);
    }
} catch (Exception $ex) {

}
if (isset($device['configure']) && is_array($device['configure'])) {
    try {
        nodejs::pushUpdate('jeedom::alert', array(
            'level' => 'warning',
            'message' => __('Execution des commandes post-configuration', __FILE__)
            ));
        $this->applyDeviceConfigurationCommand();
    } catch (Exception $ex) {

    }
}

$this->save();
nodejs::pushUpdate('jeedom::alert', array(
    'level' => 'warning',
    'message' => ''
    ));
}

public function applyDeviceConfigurationCommand() {
    $device = self::devicesParameters($this->getConfiguration('device'));
    if (is_array($device) && isset($device['configure']) && is_array($device['configure'])) {
        try {
            $replace = array(
                '#logicalId#' => $this->getLogicalId()
                );
            foreach ($device['configure'] as $configure) {
                self::callRazberry(str_replace(array_keys($replace), $replace, $configure));
            }
        } catch (Exception $ex) {

        }
    }
}

public function markAsBatteryFailed() {
    self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].SendNoOperation()');
    self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].WakeupQueue()');
    self::callRazberry('/ZWaveAPI/Run/IsFailedNode(' . $this->getLogicalId() . ')');
}

public function removeFailed() {
    self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].RemoveFailedNode()');
    sleep(5);
    self::syncEqLogicWithRazberry();
}

public function InterviewForce($instanceId = '',$_classId = '') {
    if($instanceId !== '' && $_classId !== ''){
        self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[' . $instanceId . '].commandClasses[' . $_classId . '].Interview()');
    }else{
        $results = self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . ']');
        if (isset($results['instances'])) {
            foreach ($results['instances'] as $instance_id => $instance) {
                foreach ($instance['commandClasses'] as $commandClasses_id => $commandClasses) {
                    if (isset($commandClasses['data']['interviewDone']) && isset($commandClasses['data']['interviewDone']['value']) && $commandClasses['data']['interviewDone']['value'] != true) {
                        try {
                            self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[' . $instance_id . '].commandClasses[' . $commandClasses_id . '].Interview()');
                        } catch (Exception $e) {

                        }
                    }
                }
            }
        }
        $device = self::devicesParameters($this->getConfiguration('device'));
        if (isset($device['configure']) && is_array($device['configure'])) {
            try {
                $replace = array(
                    '#logicalId#' => $this->getLogicalId()
                    );
                foreach ($device['configure'] as $configure) {
                    self::callRazberry(str_replace(array_keys($replace), $replace, $configure));
                }
            } catch (Exception $ex) {

            }
        }
    }
}

public function getWakeUp() {
    try {
        return self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[0].commandClasses[132].data.interval.value');
    } catch (Exception $e) {
        return '-';
    }
}

public function setWakeUp($_time = null) {
    if ($_time === null || !is_numeric($_time) || $_time <= 0) {
        throw new Exception(__('La durée de wakeup doit être un nombre positif', __FILE__));
    }
    self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].instances[0].commandClasses[132].Set(' . $_time . ',1)');
}

public function setPolling($_polling = null) {
    if (config::byKey('isOpenZwave', 'zwave', 0) != 1) {
        throw new Exception(__('Cette fonction n\'est possible qu\'avec openZwave', __FILE__));
    }
    if ($_polling === null || !is_numeric($_polling) || $_polling < 0) {
        throw new Exception(__('La durée de polling doit être un nombre positif', __FILE__));
    }
    self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].SetPolling(' . $_polling . ')');
}

public function getPolling() {
    try {
        return self::callRazberry('/ZWaveAPI/Run/devices[' . $this->getLogicalId() . '].GetPolling()');
    } catch (Exception $e) {
        return '-';
    }
}

public function export($_withCmd = true) {
    if ($this->getConfiguration('device') != '') {
        return array(
            $this->getConfiguration('device') => self::devicesParameters($this->getConfiguration('device'))
            );
    } else {
        $export = parent::export();
        if (isset($export['configuration']['device'])) {
            unset($export['configuration']['device']);
        }
        if (isset($export['configuration']['applyDevice'])) {
            unset($export['configuration']['applyDevice']);
        }
        if (isset($export['configuration']) && count($export['configuration']) == 0) {
            unset($export['configuration']);
        }
        if (isset($export['cmd'])) {
            $export['commands'] = $export['cmd'];
            unset($export['cmd']);
        }
        return array(
            'todo.todo' => $export
            );
    }
}

/*     * **********************Getteur Setteur*************************** */
}

class zwaveCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    public static function handleResult($_val) {
        if (!is_array($_val)) {
            return '';
        }
        if (!isset($_val['value'])) {
            return '';
        }
        $value = $_val['value'];
        switch ($_val['type']) {
            case 'float':
            $value = round(floatval($value), 1);
            break;
            case 'int':
            $value = intval($value);
            break;
            case 'bool':
            if ($value === true || $value == 'true') {
                $value = 1;
            } else {
                $value = 0;
            }
            break;
            case 'binary':
            if (is_array($_val['value'])) {
                $value = '';
                foreach ($_val['value'] as $ascii) {
                    if ($ascii != 0) {
                        $value .=$ascii;
                    }
                }
            }
            break;
        }
        return $value;
    }

    /*     * *********************Methode d'instance************************* */

    public function handleUpdateValue($_result) {
        $updateTime = null;
        if (isset($_result['val'])) {
            $value = zwaveCmd::handleResult($_result['val']);
            if (isset($_result['val']['updateTime'])) {
                $updateTime = $_result['val']['updateTime'];
            }
        } else if (isset($_result['level'])) {
            $value = zwaveCmd::handleResult($_result['level']);
            if (isset($_result['level']['updateTime'])) {
                $updateTime = $_result['level']['updateTime'];
            }
        } else {
            $value = zwaveCmd::handleResult($_result);
            if (isset($_result['updateTime'])) {
                $updateTime = $_result['updateTime'];
            }
        }
        if ($updateTime != null) {
            $this->setCollectDate(date('Y-m-d H:i:s', $updateTime));
        }
        if ($value === '') {
            try {
                $value = $this->execute();
            } catch (Exception $e) {
                return;
            }
        }
        $this->event($value, 0);
    }

    public function setRGBColor($_color) {
        if ($_color == '') {
            throw new Exception('Couleur non définie');
        }
        $request = '/ZWaveAPI/Run/devices[' . $this->getEqLogic()->getLogicalId() . ']';

        $hex = str_replace("#", "", $_color);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        //Convertion pour sur une echelle de 0-99
        $r = ($r / 255) * 99;
        $g = ($g / 255) * 99;
        $b = ($b / 255) * 99;

        $eqLogic = $this->getEqLogic();
        if ($eqLogic->getConfiguration('device') == 'fibaro.fgrgb101') {
            /* Set GREEN color */
            zwave::callRazberry($request . '.instances[3].commandClasses[0x26].Set(' . str_replace(',', '%2C', $g) . ')');
            /* Set BLUE color */
            zwave::callRazberry($request . '.instances[4].commandClasses[0x26].Set(' . str_replace(',', '%2C', $b) . ')');
            /* Set RED color */
            zwave::callRazberry($request . '.instances[2].commandClasses[0x26].Set(' . str_replace(',', '%2C', $r) . ')');
        } else {
            zwave::callRazberry($request . '.instances[0].commandClasses[0x33].Set(0,0)');
            zwave::callRazberry($request . '.instances[0].commandClasses[0x33].Set(1,0)');
            /* Set GREEN color */
            zwave::callRazberry($request . '.instances[0].commandClasses[0x33].Set(3,' . str_replace(',', '%2C', $g) . ')');
            /* Set BLUE color */
            zwave::callRazberry($request . '.instances[0].commandClasses[0x33].Set(4,' . str_replace(',', '%2C', $b) . ')');
            /* Set RED color */
            zwave::callRazberry($request . '.instances[0].commandClasses[0x33].Set(2,' . str_replace(',', '%2C', $r) . ')');
            zwave::callRazberry($request . '.instances[0].commandClasses[0x26].Set(255)');
        }
        return true;
    }

    public function getRGBColor() {
        $request = '/ZWaveAPI/Run/devices[' . $this->getEqLogic()->getLogicalId() . ']';
        /* Get RED color */
        $r = zwave::callRazberry($request . '.instances[2].commandClasses[0x26].data.level.value');
        /* Get GREEN color */
        $g = zwave::callRazberry($request . '.instances[3].commandClasses[0x26].data.level.value');
        /* Get BLUE color */
        $b = zwave::callRazberry($request . '.instances[4].commandClasses[0x26].data.level.value');
        //Convertion pour sur une echelle de 0-255
        $r = dechex(($r / 99) * 255);
        $g = dechex(($g / 99) * 255);
        $b = dechex(($b / 99) * 255);
        if (strlen($r) == 1) {
            $r = '0' . $r;
        }
        if (strlen($g) == 1) {
            $g = '0' . $g;
        }
        if (strlen($b) == 1) {
            $b = '0' . $b;
        }
        return '#' . $r . $g . $b;
    }

    public function getPilotWire() {
        $request = '/ZWaveAPI/Run/devices[' . $this->getEqLogic()->getLogicalId() . ']';
        $instancesId = explode('&&', $this->getConfiguration('instanceId'));
        if (!isset($instancesId[0])) {
            $instancesId[0] = 0;
        }
        if (!isset($instancesId[1])) {
            $instancesId[1] = 1;
        }
        $info1 = self::handleResult(zwave::callRazberry($request . '.instances[' . $instancesId[0] . '].commandClasses[0x25].data.level'));
        $info2 = self::handleResult(zwave::callRazberry($request . '.instances[' . $instancesId[1] . '].commandClasses[0x25].data.level'));
        return intval($info1) * 2 + intval($info2);
    }

    public function postSave() {
        try {
            $this->forceUpdate();
        } catch (Exception $exc) {

        }
    }

    public function preSave() {
        if ($this->getConfiguration('instanceId') === '') {
            $this->setConfiguration('instanceId', '0');
        }
        if(strpos($this->getConfiguration('class'), '0x') === false){
            $this->setConfiguration('class','0x'.dechex($this->getConfiguration('class')));
        }
        $this->setLogicalId($this->getConfiguration('instanceId') . '.' . $this->getConfiguration('class'));
    }

    public function forceUpdate() {
        zwave::callRazberry('/ZWaveAPI/Run/devices[' . $this->getEqLogic()->getLogicalId() . '].instances[' . $this->getConfiguration('instanceId', 0) . '].commandClasses[' . $this->getConfiguration('class') . '].Get()');
    }

    public function sendZwaveResquest($_url) {
        $result = zwave::callRazberry($_url);
        if ($this->getType() == 'action') {
            return;
        }
        if (is_array($result)) {
            $value = self::handleResult($result);
            if (isset($result['updateTime'])) {
                $this->setCollectDate(date('Y-m-d H:i:s', $result['updateTime']));
            }
        } else {
            $value = $result;
            if ($value === true || $value == 'true') {
                return 1;
            }
            if ($value === false || $value == 'false') {
                return 0;
            }
            if (is_numeric($value)) {
                return round($value, 1);
            }
        }
        return $value;
    }

    public function execute($_options = null) {
        if ($this->getLogicalId() == 'pilotWire' || $this->getConfiguration('value') == 'pilotWire') {
            return $this->getPilotWire();
        }
        $value = $this->getConfiguration('value');
        $request = '/ZWaveAPI/Run/devices[' . $this->getEqLogic()->getLogicalId() . ']';
        switch ($this->getType()) {
            case 'action' :
            switch ($this->getSubType()) {
                case 'slider':
                $value = str_replace('#slider#', $_options['slider'], $value);
                break;
                case 'color':
                $value = str_replace('#color#', $_options['color'], $value);
                return $this->setRGBColor($value);
            }
            break;
        }
        if (strpos($this->getConfiguration('instanceId'), '&&') !== false || strpos($value, '&&') !== false) {
            $lastInstanceId = $this->getConfiguration('instanceId');
            $instancesId = explode('&&', $this->getConfiguration('instanceId'));
            $lastValue = $value;
            $values = explode('&&', $value);
            $totalRequest = max(count($values), count($instancesId));
            $result = '';
            for ($i = 0; $i < $totalRequest; $i++) {
                if (strpos($values[$i], 'sleep(') !== false) {
                    $duration = str_replace(array('sleep(', ')'), '', $values[$i]);
                    if ($duration != '' && is_numeric($duration)) {
                        sleep($duration);
                    }
                } else {
                    $request_http = $request;
                    $value = $lastValue;
                    if (isset($values[$i]) && $values[$i] != '') {
                        $value = $values[$i];
                        $lastValue = $value;
                    }

                    $instanceId = $lastInstanceId;
                    if (isset($instancesId[$i])) {
                        $instanceId = $instancesId[$i];
                        $lastInstanceId = $instanceId;
                    }
                    if ($instanceId != '') {
                        $request_http .= '.instances[' . $instanceId . ']';
                    }
                    $request_http .= '.commandClasses[' . $this->getConfiguration('class') . ']';
                    $request_http .= '.' . $value;
                    $result .= $this->sendZwaveResquest($request_http);
                }
            }
            return $result;
        }
        if ($this->getConfiguration('instanceId') != '') {
            $request .= '.instances[' . $this->getConfiguration('instanceId') . ']';
        }
        $request .= '.commandClasses[' . $this->getConfiguration('class') . ']';
        $request .= '.' . str_replace(',', '%2C', $value);
        return $this->sendZwaveResquest($request);
    }

    /*     * **********************Getteur Setteur*************************** */
}
