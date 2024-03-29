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

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init();

  if (init('action') == 'loop_percentage') {
     atlas::loopPercentage();
     ajax::success();
  }

  if (init('action') == 'startMigration') {
    atlas::startMigration();
    ajax::success();
  }

  if (init('action') == 'startUSB') {
    atlas::startMigration('usb');
    ajax::success();
  }

  if (init('action') == 'listWifi') {
		$forced = init('mode');
		ajax::success(atlas::listWifi($forced));
	}

	if (init('action') == 'macfinder') {
		ajax::success(atlas::getMac(init('interfa')));
	}

	if (init('action') == 'writeInterfaceFile') {
		ajax::success(atlas::writeInterfaceFile());
	}

	if (init('action') == 'wifiConnect') {
		ajax::success(atlas::wifiConnect());
	}

	if (init('action') == 'wifiDisConnect') {
		ajax::success(atlas::wifiDisConnect());
	}


    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
