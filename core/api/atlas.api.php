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

header('Content-Type: application/json');

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
global $jsonrpc;
global $_USER_GLOBAL;
if (!is_object($jsonrpc)) {
  throw new Exception(__('Objet JSONRPC non défini', __FILE__), -32699);
}

$params = $jsonrpc->getParams();
$methode = $jsonrpc->getMethod();
log::add('atlas', 'debug', __('Appel API Atlas', __FILE__) . ' > ' . $methode);
log::add('atlas', 'debug', __('Paramêtres passés', __FILE__) . ' > ' . json_encode($params));

if ($methode == 'ddUSB') {
  $usb = atlas::startMigration('usb');
  if ($usb == 'ok') {
    $jsonrpc->makeSuccess();
  } else {
    throw new Exception(__('La clé USB n\'est pas détectée, veuillez vérifier qu\'il n\'y ait aucun autre périphérique USB branché.', __FILE__));
  }
}

if ($methode == 'ddEMMC') {
  $emmc = atlas::startMigration('emmc');
  if ($emmc == 'ok') {
    $jsonrpc->makeSuccess();
  } else {
    throw new Exception(__('Le support EMMC n\'est pas détecté.', __FILE__));
  }
}

if ($methode == 'pourcMigrate') {
  atlas::loopPercentage();
  $jsonrpc->makeSuccess();
}

if ($methode == 'standby') {
  config::save('migration', 0);
  jeedom::haltSystem();

  $jsonrpc->makeSuccess();
}

if ($methode == 'testtest') {
  log::add('atlas', 'debug', __('TEST OU PAS', __FILE__));
}

if ($methode == 'activeHotSpot') {
  $atlas = eqLogic::byLogicalId('wifi', 'atlas');
  $atlas->setConfiguration('hotspotEnabled', true);
  if ($params['ssidHotspot']) {
    $atlas->setConfiguration('ssidHotspot', $params['ssidHotspot']);
  }
  if ($params['mdpHotspot']) {
    $atlas->setConfiguration('mdpHotspot', $params['mdpHotspot']);
  }
  $atlas->save();
  atlas::testHotspot();
  $jsonrpc->makeSuccess();
}

throw new Exception(__('Aucune demande', __FILE__));
