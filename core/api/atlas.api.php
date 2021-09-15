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
GLOBAL $_USER_GLOBAL;
if (!is_object($jsonrpc)) {
	throw new Exception(__('JSONRPC object not defined', __FILE__), -32699);
}

$params = $jsonrpc->getParams();
$methode = $jsonrpc->getMethod();
log::add('atlas', 'debug', 'Appel API Atlas > ' . $methode);
log::add('atlas', 'debug', 'parametres passés > ' . json_encode($params));

if($methode == 'ddUSB'){
  $usb = atlas::startMigration('usb');
  if($usb == 'ok'){
    $jsonrpc->makeSuccess();
  }else{
    throw new Exception(__('USB pas trouvé verifier si il est bien tout seul !', __FILE__));
  }
}

if($methode == 'ddEMMC'){
  $emmc = atlas::startMigration('emmc');
  if($emmc == 'ok'){
    $jsonrpc->makeSuccess();
  }else{
    throw new Exception(__('Emmc pas trouvé', __FILE__));
  }
}

if($methode == 'pourcMigrate'){
  atlas::loopPercentage();
  $jsonrpc->makeSuccess();
}

if($methode == 'standby'){
  config::save('migration', 0);
	jeedom::haltSystem();

  $jsonrpc->makeSuccess();
}

if($methode == 'testtest'){
  log::add('atlas', 'debug', 'TEST OU PAS');
}

throw new Exception(__('Aucune demande', __FILE__));
?>
