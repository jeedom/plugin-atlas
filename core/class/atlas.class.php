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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class atlas extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

  public static function dependancy_info() {
    $return = array();
    $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';
    $return['state'] = 'ok';
    log::add(__CLASS__, 'debug', 'sys > ' . system::getCmdSudo() . system::get('cmd_check') . '-E "rsync|cloud\-guest\-utils" | wc -l');
    if (exec(system::getCmdSudo() . system::get('cmd_check') . '-E "rsync|cloud\-guest\-utils" | wc -l') < 4) {
      $return['state'] = 'nok';
    }
    return $return;
  }

  public static function dependancy_install() {
    log::remove(__CLASS__ . '_update');
    return array('script' => __DIR__ . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
  }

  /*     * ***********************Methode static*************************** */

  /* ----- RECOVERY et MIGRATION ----- */
  public static function put_ini_file($file, $array, $i = 0) {
    $str = "[core]\n";
    foreach ($array as $k => $v) {
      if (is_array($v)) {
        $str .= str_repeat(" ", $i * 2) . "[$k]" . PHP_EOL;
        $str .= put_ini_file("", $v, $i + 1);
      } else
        $str .= str_repeat(" ", $i * 2) . "$k = $v" . PHP_EOL;
    }
    if ($file)
      return file_put_contents($file, $str);
    else
      return $str;
  }

  public static function startMigration($target = 'emmc') {
    log::clear('migrate');
    log::clear('downloadImage');
    config::save('migrationText', 'prelancement');
    config::save('migration', 0);
    config::save('migrationTextfine', __('Détection en cours...', __FILE__));
    sleep(5);
    $path_target = '';
    log::add(__CLASS__, 'debug', 'PATH TARGET');
    if (file_exists('/dev/mmcblk2') && $target == 'emmc') {
      $path_target = '/dev/mmcblk2';
      config::save('migrationText', 'emmc');
      config::save('migrationTextfine', __('Support eMMC détecté sur mmcblk2.', __FILE__));
      sleep(3);
      if (atlas::ddImg($path_target)) {
        sleep(3);
        atlas::recoveryemmcMount($path_target);
        return 'ok';
      }
      return 'nok';
    } elseif (file_exists('/dev/mmcblk1') && $target == 'emmc') {
      $path_target = '/dev/mmcblk1';
      config::save('migrationText', 'emmc');
      config::save('migrationTextfine', __('Support eMMC détecté sur mmcblk1.', __FILE__));
      sleep(3);
      if (atlas::ddImg($path_target)) {
        sleep(3);
        atlas::recoveryemmcMount($path_target);
        return 'ok';
      }
      return 'nok';
    } elseif (file_exists('/dev/sda') && $target == 'usb') {
      $path_target = '/dev/sda';
      config::save('migrationText', 'usb');
      config::save('migrationTextfine', __('Clé USB détectée sur sda.', __FILE__));
      sleep(3);
      if (atlas::ddImg($path_target)) {
        sleep(3);
        atlas::recoveryUsbMount($path_target);
        return 'ok';
      };
      return 'nok';
    } else {
      log::add(__CLASS__, 'debug', 'ERREUR TARGET DEVICE');
      config::save('migrationText', 'errorTarget');
      return 'nok';
    }
  }

  public static function recoveryUsbMount($devusb = '/dev/sda') {
    config::save('migrationText', 'finalUSB');
    config::save('migration', 101);
    if (!file_exists('/mnt/usb')) {
      log::add(__CLASS__, 'debug', 'creation /mnt/usb');
      shell_exec('sudo mkdir /mnt/usb');
    }
    shell_exec('sudo umount /mnt/usb');
    log::add(__CLASS__, 'debug', 'FSDISK -d');
    config::save('migrationTextfine', __('Vérification de l'espace de stockage.', __FILE__));
    sleep(2);
    shell_exec('sudo sfdisk -d ' . $devusb . ' > sda_partition_bak.dmp');
    config::save('migration', 110);
    log::add(__CLASS__, 'debug', __('Création de la partition.', __FILE__));
    config::save('migrationTextfine', __('Création de la partition.', __FILE__));
    sleep(2);
    shell_exec('sudo growpart -N ' . $devusb . ' 1');
    shell_exec('sudo growpart ' . $devusb . ' 1');
    config::save('migration', 120);
    log::add(__CLASS__, 'debug', __('Vérification de la partition de démarrage.', __FILE__));
    config::save('migrationTextfine', __('Vérification de la partition de démarrage.', __FILE__));
    sleep(2);
    shell_exec('sudo e2fsck -fy ' . $devusb . '1');
    log::add(__CLASS__, 'debug', __('Redimensionnement de la partition de démarrage.', __FILE__));
    config::save('migrationTextfine', __('Redimensionnement de la partition de démarrage.', __FILE__));
    sleep(2);
    shell_exec('sudo resize2fs ' . $devusb . '1 12G');
    config::save('migration', 130);
    log::add(__CLASS__, 'debug', __('Montage de la partition.', __FILE__));
    config::save('migrationTextfine', __('Montage de la partition pour modification.', __FILE__));
    sleep(2);
    shell_exec('sudo mount ' . $devusb . '1 /mnt/usb');
    if (!file_exists('/mnt/usb/var/www/html/data/imgOs')) {
      shell_exec('sudo mkdir /mnt/usb/var/www/html/data/imgOs');
    } else {
      if (file_exists('/mnt/usb/var/www/html/data/imgOs/jeedomAtlas.img.gz')) {
        config::save('migrationTextfine', __('Suppression de l'ancienne image Jeedom.', __FILE__));
        sleep(2);
        shell_exec('sudo rm /mnt/usb/var/www/html/data/imgOs/jeedomAtlas.img.gz');
      }
    }
    log::add(__CLASS__, 'debug', __('Montage de la clé USB', __FILE__));
    config::save('migrationTextfine', __('Ajout du fichier de configuration de restauration.', __FILE__));
    sleep(2);
    $ini_array = parse_ini_file('/mnt/usb/var/www/html/data/custom/custom.config.ini');
    log::add(__CLASS__, 'debug', '--------------');
    $ini_array['product_name'] = 'Jeedom Atlas Recovery';
    $ini_array['path_wizard'] = 'data/custom/atlasRecoveryWizard.json';
    $ini_array['product_connection_image'] = 'core/img/logo-jeedom-atlas-recovery-grand-nom-couleur.svg';
    atlas::put_ini_file('/mnt/usb/var/www/html/data/custom/custom.config.ini', $ini_array);
    config::save('migration', 140);
    log::add(__CLASS__, 'debug', __('Changement du HostName.', __FILE__));
    config::save('migrationTextfine', __('Changement du HostName.', __FILE__));
    sleep(2);
    shell_exec('sudo bash -c \'echo "JeedomAtlasRecovery" > /mnt/usb/etc/hostname\'');
    log::add(__CLASS__, 'debug', __('Changement du HostName réalisé.', __FILE__));
    shell_exec('sudo cp /var/www/html/plugins/atlas/data/recovery/atlasRecoveryWizard.json /mnt/usb/var/www/html/' . $ini_array['path_wizard']);
    shell_exec('sudo cp /var/www/html/plugins/atlas/data/recovery/logo-jeedom-atlas-recovery-grand-nom-couleur.svg /mnt/usb/var/www/html/' . $ini_array['product_connection_image']);
    config::save('migration', 150);
    log::add(__CLASS__, 'debug', '--------------');
    log::add(__CLASS__, 'debug', __('Ajout de l'image Jeedom Atlas sur la clé USB.', __FILE__));
    config::save('migrationTextfine', __('Ajout de l'image Jeedom Atlas sur la clé USB.', __FILE__));
    sleep(2);
    shell_exec('sudo cp /var/www/html/data/imgOs/jeedomAtlas.img.gz /mnt/usb/var/www/html/data/imgOs/jeedomAtlas.img.gz');
    log::add(__CLASS__, 'debug', 'Fin');
    config::save('migrationText', 'endUSB');
    config::save('migrationTextfine', __('Fin.', __FILE__));
    config::save('migration', 200);
  }

  public static function recoveryemmcMount($devemmc = '/dev/mmcblk1') {
    config::save('migrationText', 'finalUSB');
    config::save('migration', 101);
    if (!file_exists('/mnt/usb')) {
      log::add(__CLASS__, 'debug', __('Création', __FILE__) . ' /mnt/usb');
      shell_exec('sudo mkdir /mnt/usb');
    }
    config::save('migration', 120);
    shell_exec('sudo umount /mnt/usb');
    log::add(__CLASS__, 'debug', 'FSDISK -d');
    config::save('migrationTextfine', __('Vérification de l'espace de stockage.', __FILE__));
    sleep(2);
    shell_exec('sudo sfdisk -d ' . $devemmc . ' > mmcblk1_partition_bak.dmp');
    log::add(__CLASS__, 'debug', __('Création de la partition.', __FILE__));
    config::save('migrationTextfine', __('Création de la partition.', __FILE__));
    sleep(2);
    config::save('migration', 140);
    shell_exec('sudo growpart -N ' . $devemmc . ' 1');
    shell_exec('sudo growpart ' . $devemmc . ' 1');
    config::save('migrationTextfine', __('Vérification de la partition de démarrage.', __FILE__));
    sleep(2);
    config::save('migration', 150);
    log::add(__CLASS__, 'debug', __('Vérification de la partition de démarrage.', __FILE__));
    shell_exec('sudo e2fsck -fy ' . $devemmc . '1');
    config::save('migration', 180);
    config::save('migrationTextfine', __('Redimentionnement de la partition de démarrage.', __FILE__));
    sleep(2);
    log::add(__CLASS__, 'debug', __('Redimentionnement de la partition de démarrage.', __FILE__));
    shell_exec('sudo resize2fs ' . $devemmc . 'p1');
    log::add(__CLASS__, 'debug', __('Montage de la partition.', __FILE__));
    log::add(__CLASS__, 'debug', 'Fin');
    config::save('migrationText', 'endEMMC');
    config::save('migrationTextfine', __('Fin.', __FILE__));
    sleep(2);
    config::save('migration', 200);
  }

  public static function ddImg($target) {
    log::add(__CLASS__, 'debug', 'IN CREATE LOG');
    config::save('migrationText', 'verifdd');
    if (atlas::downloadImage()) {
      config::save('migrationText', 'dd');
      sleep(3);
      config::save('migrationTextfine', __('Gravure de l'image.', __FILE__));
      log::add(__CLASS__, 'debug', '(sudo cat /var/www/html/data/imgOs/jeedomAtlas.img.gz | sudo gunzip | sudo dd of=' . $target . ' bs=512 status=progress) > ' . log::getPathToLog('migrate') . ' 2>&1');
      shell_exec('(sudo cat /var/www/html/data/imgOs/jeedomAtlas.img.gz | sudo gunzip | sudo dd of=' . $target . ' bs=512 status=progress) > ' . log::getPathToLog('migrate') . ' 2>&1');
      return true;
    } else {
      log::add(__CLASS__, 'debug', 'ERREUR IMAGE MIGRATE');
      config::save('migrationText', 'errorDd');
      return false;
    }
  }

  public static function marketImg($text = true) {
    log::add(__CLASS__, 'debug', __('Demande d'informations au Market.', __FILE__));
    if ($text == true) {
      config::save('migrationTextfine', __('Demande d'informations au Market.', __FILE__));
      sleep(2);
    }
    $jsonrpc = repo_market::getJsonRpc();
    if (!$jsonrpc->sendRequest('box::atlas_image_url')) {
      throw new Exception($jsonrpc->getErrorMessage());
    }
    $urlArray = $jsonrpc->getResult();
    if ($urlArray['url'] && $urlArray['SHA256'] && $urlArray['size']) {
      return $urlArray;
    }
    return false;
  }

  public static function downloadImage() {
    $urlArray = atlas::marketImg();
    if (!$urlArray) {
      log::add(__CLASS__, 'debug', __('Problème avec le Market.', __FILE__));
      return false;
    }
    $url = $urlArray['url'];
    $size = $urlArray['SHA256'];
    //$size = 'a0159ba90745ba72822bc3fc1e6aa2943ae0dccff545b9dcf20e17a4898fe751';
    log::add(__CLASS__, 'debug', __('Téléchargement', __FILE__) . ' > ' . $size);
    exec('sudo pkill -9 wget');
    $path_imgOs = '/var/www/html/data/imgOs';
    if (!file_exists($path_imgOs)) {
      mkdir($path_imgOs, 0644);
    }
    $find = false;
    $fichier = $path_imgOs . '/jeedomAtlas.img.gz';
    log::add(__CLASS__, 'debug', __('Fichier', __FILE__) . ' > ' . $fichier);
    if (file_exists($fichier)) {
      log::add(__CLASS__, 'debug', __('Test de l'image (vérification SHA).', __FILE__));
      config::save('migrationTextfine', __('Test de l'image (vérification SHA).', __FILE__));
      $sha_256 = hash_file('sha256', $fichier);
      log::add(__CLASS__, 'debug', __('Taille', __FILE__) . ' > ' . $size);
      log::add(__CLASS__, 'debug', __('SHA', __FILE__) . ' > ' . $sha_256);
      if ($size == $sha_256) {
        log::add(__CLASS__, 'debug', __('Image OK.', __FILE__));
        config::save('migrationTextfine', __('Image OK.', __FILE__));
        sleep(2);
        $find = true;
      } else {
        log::add(__CLASS__, 'debug', __('Image NOK.', __FILE__));
        config::save('migrationTextfine', __('Image NOK.', __FILE__));
        sleep(2);
        //RM fichier
        unlink($fichier);
      }
    }
    if ($find == false) {
      config::save('migrationText', 'upload');
      log::add(__CLASS__, 'debug', 'find a False');
      config::save('migrationTextfine', __('Téléchargement de l'image sur nos serveurs en cours.', __FILE__));
      log::add(__CLASS__, 'debug', 'URL > ' . $url);
      log::add(__CLASS__, 'debug', 'shell > sudo wget --progress=dot --dot=mega ' . $url . ' -a ' . log::getPathToLog('downloadImage') . ' -O ' . $path_imgOs . '/jeedomAtlas.img.gz >> ' . log::getPathToLog('downloadImage') . ' 2&>1');
      shell_exec('sudo wget --progress=dot --dot=mega ' . $url . ' -a ' . log::getPathToLog('downloadImage') . ' -O ' . $path_imgOs . '/jeedomAtlas.img.gz >> ' . log::getPathToLog('downloadImage'));
      sleep(10);
      $sha_256 = hash_file('sha256', $fichier);
      if ($size == $sha_256) {
        return true;
      } else {
        return false;
      }
    }
    return true;
  }

  public static function loopPercentage() {
    $urlArray = atlas::marketImg(false);
    $size = $urlArray['size'];
    $GO = $size;
    $MO = $GO * 1024;
    $KO = $MO * 1024;
    $BytesGlobal = $KO * 1024;
    $level_percentage = 0;
    config::save('migration', $level_percentage);
    while (config::byKey('migration') < 100) {
      log::add(__CLASS__, 'debug', $level_percentage);
      sleep(1);
      $level_percentage = atlas::percentageProgress($BytesGlobal);
      if (config::byKey('migration') < 101) {
        config::save('migration', $level_percentage);
      } else {
        log::add(__CLASS__, 'debug', 'NON save pour le 100%');
      }
    }
  }

  public static function percentageProgress($BytesGlobal) {
    $logMigrate = log::get('migrate', 0, 1);
    $logMigrateAll = log::get('migrate', 0, 10);

    $pos = self::posOut($logMigrateAll);
    $firstln = $logMigrate[0];
    log::add(__CLASS__, 'debug', __('AVANCEMENT', __FILE__) . ' : ' . $firstln);

    if ($pos == false) {
      $valueByte = stristr($firstln, 'bytes', true);
      $pourcentage = round((100 * $valueByte) / $BytesGlobal, 2);
      log::add(__CLASS__, 'debug', __('ETAT', __FILE__) . ' : ' . $pourcentage . '%');
      log::clear('migrate');
      if ($valueByte == '' || $valueByte == null) {
      } else {
        return $pourcentage;
      }
    } else {
      log::add(__CLASS__, 'debug', __('FIN', __FILE__) . ' 100%');
      return 100;
    }
  }


  public static function posOut($needles) {
    foreach ($needles as $needle) {
      $rep = strpos($needle, 'records');
      if ($rep != false) {
        log::add(__CLASS__, 'debug', __('Fin de migration.', __FILE__));
        return true;
      }
    }
    return false;
  }

  /* ------ FIN RECOVERY et MIGRATION ------ */


  public static function cron5($_eqlogic_id = null) {
    if ($_eqlogic_id !== null) {
      $eqLogics = array(eqLogic::byId($_eqlogic_id));
    } else {
      $eqLogics = eqLogic::byType('wifip');
    }
    foreach ($eqLogics as $atlas) {
      log::add(__CLASS__, 'debug', 'Pull Cron Atlas');
      $atlas->wifiConnect();
      if ($atlas->getIsEnable() != 1) {
        continue;
      };
      if (!file_exists("/sys/class/net/eth0/operstate")) {
        $ethup = 0;
      } else {
        $ethup = (trim(file_get_contents("/sys/class/net/eth0/operstate")) == 'up') ? 1 : 0;
      }
      if (!file_exists("/sys/class/net/wlan0/operstate")) {
        $wifiup = 0;
      } else {
        $wifiup = (trim(file_get_contents("/sys/class/net/wlan0/operstate")) == 'up') ? 1 : 0;
      }
      $wifisignal = str_replace('.', '', shell_exec("sudo tail -n +3 /proc/net/wireless | awk '{ print $3 }'"));
      $wifiIp = shell_exec("sudo ifconfig wlan0 | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1'");
      $lanIp = shell_exec("sudo ifconfig eth0 | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1'");
      log::add(__CLASS__, 'debug', 'Lan Ip is :' . $lanIp);
      log::add(__CLASS__, 'debug', 'Wifi Ip is :' . $wifiIp);
      $atlas->checkAndUpdateCmd('isconnect', $wifiup);
      $atlas->checkAndUpdateCmd('isconnecteth', $ethup);
      $atlas->checkAndUpdateCmd('signal', $wifisignal);
      $atlas->checkAndUpdateCmd('lanip', $lanIp);
      $atlas->checkAndUpdateCmd('wifiip', $wifiIp);
      if ($atlas->getConfiguration('wifiEnabled', 0) == 1) {
        $atlas->checkAndUpdateCmd('ssid', $atlas->getConfiguration('wifiSsid', ''));
      } else {
        $atlas->checkAndUpdateCmd('ssid', 'Aucun');
      }
    }
  }

  /* ----- SECURITY IP ----- */

  public static function securityIp($type = 'eth0') {
    //verif ip
    $ipEth = trim(shell_exec('ip addr show ' . $type . ' | grep "inet\b" | awk \'{print $2}\' | cut -d/ -f1'));
    if ($ipEth == '' || !$ipEth) {
      log::add(__CLASS__, 'debug', __('Aucune adresse IP détectée sur', __FILE__) . ' ' . $type . '. ' .  __('Passage en 100M/TX', __FILE__));
      shell_exec('sudo ethtool -s ' . $type . ' speed 100 duplex full autoneg on');
    } else {
      log::add(__CLASS__, 'debug', 'ip ok sur ' . $type . ' / ' . $ipEth);
    }
  }

  /* ----- START ----- */

  public static function start() {
    log::add(__CLASS__, 'debug', __('Jeedom est démarré, vérification des connexions.', __FILE__));
    atlas::securityIp();
    $atlas = eqLogic::byLogicalId('wifi', __CLASS__);
    if (is_object($atlas)) {
      $atlas->wifiConnect();
    }
  }

  /* ----- WIFI ----- */

  public static function isWificonnected($ssid) {
    $result = shell_exec("sudo nmcli d | grep '" . $ssid . "'");
    log::add(__CLASS__, 'debug', $result);
    if (strpos($result, 'connected') === false && strpos($result, 'connecté') === false) {
      return false;
    }
    return true;
  }

  public static function isWifiProfileexist($ssid) {
    $result = shell_exec("nmcli --fields NAME con show");
    $countProfile = substr_count($result, $ssid);
    if ($countProfile > 1) {
      log::add(__CLASS__, 'debug', __('Suppression des profils.', __FILE__));
      shell_exec("nmcli --pretty --fields UUID,TYPE con show | grep wifi | awk '{print $1}' | while read line; do nmcli con delete uuid  $line; done");
      return true;
    } else if ($countProfile == 1) {
      return true;
    } else {
      return false;
    }
  }

  public static function listWifi($forced = false) {
    $eqLogic = eqLogic::byType(__CLASS__);
    log::add(__CLASS__, 'debug', 'Wifi enabled : ' . $eqLogic[0]->getConfiguration('wifiEnabled'));
    $return = [];
    if ($eqLogic[0]->getConfiguration('wifiEnabled') == true || $forced == true) {
      $scanresult = shell_exec('sudo nmcli -f SSID,SIGNAL,SECURITY,CHAN -t -m tabular dev wifi list');
      $results = explode("\n", $scanresult);
      $return = array();
      foreach ($results as $result) {
        log::add(__CLASS__, 'debug', $result);
        $result = str_replace('\:', '$%$%', $result);
        $wifiDetail = explode(':', $result);
        $chan = $wifiDetail[3];
        $security = $wifiDetail[2];
        if ($security == '') {
          $security = 'Aucune';
        }
        $signal =  $wifiDetail[1];
        $ssid = str_replace('$%$%', '\:', $wifiDetail[0]);
        if ($ssid != '') {
          log::add(__CLASS__, 'debug', $ssid . ' with signal ' . $signal . ' and security ' . $security . ' on channel ' . $chan);
          if (isset($return[$ssid]) && $return[$ssid]['signal'] > $signal) {
            continue;
          }
          $return[$ssid] = array('ssid' => $ssid, 'signal' => $signal, 'security' => $security, 'channel' => $chan);
        }
      }
    }
    return $return;
  }

  public static function getMac($_interface = 'eth0') {
    $interfaceIp = shell_exec("sudo ifconfig $_interface | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1'");
    $interfaceMac = shell_exec("sudo ip addr show $_interface | grep -i 'link/ether' | grep -o -E '([[:xdigit:]]{1,2}:){5}[[:xdigit:]]{1,2}' | sed -n 1p");
    return [$interfaceMac, $interfaceIp];
  }

  public function wifiConnect() {
    if ($this->getConfiguration('wifiEnabled') == true) {
      atlas::activeHotSpot();
      if ($this->getConfiguration('hotspotEnabled') == true) {
        return;
      } else {
        $ssid = $this->getConfiguration('wifiSsid', '');
      }
      if (self::isWificonnected($ssid) === false) {
        log::add(__CLASS__, 'debug', __('Non connecté à', __FILE__) . ' ' . $ssid . '. ' . __('Connexion en cours...', __FILE__));
        shell_exec("sudo ip link set wlan0");
        if (self::isWifiProfileexist($ssid) === true) {
          $exec = "sudo nmcli con up '" . $ssid . "'";
        } else {
          $password = $this->getConfiguration('wifiPassword', '');
          if ($password != '') {
            $exec = "sudo nmcli dev wifi connect '" . $ssid . "' password '" . $password . "'";
          } else {
            $exec = "sudo nmcli dev wifi connect '" . $ssid . "'";
          }
        }
        log::add(__CLASS__, 'debug', 'Executing ' . $exec);
        shell_exec($exec);
      }
    } else {
      log::add(__CLASS__, 'debug', 'Executing sudo nmcli dev disconnect wlan0');
      shell_exec('sudo nmcli dev disconnect wlan0');
    }
  }

  /* ----- FIN WIFI ----- */

  /* ----- HotSpot ----- */

  public function testHotspot() {
    $linkForHotspot = __DIR__ . '/../../resources/lnxrouter';
    if ($this->getConfiguration('hotspotEnabled') == true) {
      $pid = shell_exec("sudo bash " . $linkForHotspot . " -l");
      if ($pid != "") {
        atlas::activeHotSpot();
      }
    }
  }

  public static function activeHotSpot() {
    log::add(__CLASS__, 'debug', __('Activation du Hotspot.', __FILE__));
    $linkForHotspot = __DIR__ . '/../../resources/lnxrouter';
    $wlanLink = 'wlan0';
    $atlas = eqLogic::byLogicalId('wifi', __CLASS__);
    $interfaceInfo = atlas::getMac();
    $macAddress = $interfaceInfo[1];
    $strMac = str_replace(':', '', $macAddress);
    $wifiPostFix = substr($strMac, -4);
    if (!is_object($atlas)) {
      log::add(__CLASS__, 'debug', __('Hotspot : erreur 1.', __FILE__));
      return;
    }
    if ($atlas->getConfiguration('hotspotEnabled') == true) {

      log::add(__CLASS__, 'debug', __('Hotspot activé.', __FILE__));
      log::add(__CLASS__, 'debug', 'Executing sudo nmcli dev disconnect wlan0');

      shell_exec('sudo nmcli dev disconnect wlan0');
      shell_exec('sudo systemctl daemon-reload');
      $pid = shell_exec("sudo bash " . $linkForHotspot . " -l");
      $log = shell_exec("sudo bash " . $linkForHotspot . " --stop " . $pid . " > /dev/null 2>&1");
      log::add(__CLASS__, 'debug', 'Hotspot > ' . $log);
      $atlas->setConfiguration('dns', 'wlan0');
      $atlas->setConfiguration('forwardingIPV4', true);
      $ssid = $atlas->getConfiguration('ssidHotspot', 'JeedomAtlas-' . $wifiPostFix);
      $mdp = $atlas->getConfiguration('mdpHotspot', $strMac);
      if ($ssid == 'JeedomAtlas-' . $wifiPostFix) {
        $atlas->setConfiguration('ssidHotspot', 'JeedomAtlas-' . $wifiPostFix);
      }
      if ($mdp == $strMac) {
        $atlas->setConfiguration('mdpHotspot', $strMac);
      }
      $atlas->save();

      log::add(__CLASS__, 'debug', __('Mise en plance du Profil Hotspot.', __FILE__));
      log::add(__CLASS__, 'debug', 'Hotspot > ' . $log);
      $log = shell_exec('sudo bash ' . $linkForHotspot . ' --daemon --ap ' . $wlanLink . ' ' . $ssid . ' -p ' . $mdp . ' > /dev/null 2>&1');
      log::add(__CLASS__, 'debug', 'Hotspot > ' . $log);
    } else {
      shell_exec('sudo systemctl daemon-reload');
      shell_exec('sudo ifconfig wlan0 up');
      $pid = shell_exec("sudo bash " . $linkForHotspot . " -l");
      $log = shell_exec("sudo bash " . $linkForHotspot . " --stop " . $pid . " > /dev/null 2>&1");
    }
  }



  /* ----- FIN ----- */

  public function postSave() {
    $connect = $this->getCmd(null, 'connect');
    if (!is_object($connect)) {
      $connect = new atlasCmd();
      $connect->setLogicalId('connect');
      $connect->setIsVisible(1);
      $connect->setName(__('Connecter Wifi', __FILE__));
    }
    $connect->setType('action');
    $connect->setSubType('other');
    $connect->setEqLogic_id($this->getId());
    $connect->save();

    $disconnect = $this->getCmd(null, 'disconnect');
    if (!is_object($disconnect)) {
      $disconnect = new atlasCmd();
      $disconnect->setLogicalId('disconnect');
      $disconnect->setIsVisible(1);
      $disconnect->setName(__('Déconnecter Wifi', __FILE__));
    }
    $disconnect->setType('action');
    $disconnect->setSubType('other');
    $disconnect->setEqLogic_id($this->getId());
    $disconnect->save();

    $isconnect = $this->getCmd(null, 'isconnect');
    if (!is_object($isconnect)) {
      $isconnect = new atlasCmd();
      $isconnect->setName(__('Etat Wifi', __FILE__));
    }
    $isconnect->setEqLogic_id($this->getId());
    $isconnect->setLogicalId('isconnect');
    $isconnect->setType('info');
    $isconnect->setSubType('binary');
    $isconnect->save();

    $signal = $this->getCmd(null, 'signal');
    if (!is_object($signal)) {
      $signal = new atlasCmd();
      $signal->setName(__('Signal', __FILE__));
    }
    $signal->setEqLogic_id($this->getId());
    $signal->setLogicalId('signal');
    $signal->setType('info');
    $signal->setSubType('numeric');
    $signal->save();

    $lanip = $this->getCmd(null, 'lanip');
    if (!is_object($lanip)) {
      $lanip = new atlasCmd();
      $lanip->setName(__('Lan IP', __FILE__));
    }
    $lanip->setEqLogic_id($this->getId());
    $lanip->setLogicalId('lanip');
    $lanip->setType('info');
    $lanip->setSubType('string');
    $lanip->save();

    $wifiip = $this->getCmd(null, 'wifiip');
    if (!is_object($wifiip)) {
      $wifiip = new atlasCmd();
      $wifiip->setName(__('Wifi IP', __FILE__));
    }
    $wifiip->setEqLogic_id($this->getId());
    $wifiip->setLogicalId('wifiip');
    $wifiip->setType('info');
    $wifiip->setSubType('string');
    $wifiip->save();

    $ssid = $this->getCmd(null, 'ssid');
    if (!is_object($ssid)) {
      $ssid = new atlasCmd();
      $ssid->setName(__('SSID', __FILE__));
    }
    $ssid->setEqLogic_id($this->getId());
    $ssid->setLogicalId('ssid');
    $ssid->setType('info');
    $ssid->setSubType('string');
    $ssid->save();

    $refresh = $this->getCmd(null, 'refresh');
    if (!is_object($refresh)) {
      $refresh = new atlasCmd();
    }
    $refresh->setName(__('Rafraichir', __FILE__));
    $refresh->setLogicalId('refresh');
    $refresh->setEqLogic_id($this->getId());
    $refresh->setType('action');
    $refresh->setSubType('other');
    $refresh->save();
  }

  public function postAjax() {
    $this->wifiConnect();
  }
}

class atlasCmd extends cmd {

  public function execute($_options = array()) {
    if ($this->getType() == '') {
      return '';
    }
    $eqLogic = $this->getEqlogic();
    $action = $this->getLogicalId();
    switch ($action) {
      case 'connect':
        $eqLogic->setConfiguration('wifiEnabled', true);
        $eqLogic->save();
        break;
      case 'disconnect':
        $eqLogic->setConfiguration('wifiEnabled', false);
        $eqLogic->save();
        break;
      case 'repair':
        $ssidConf = $eqLogic->getConfiguration('wifiSsid');
        if ($ssidConf == "") {
          $eqLogic->setConfiguration('wifiSsid', shell_exec('iwgetid -r'));
          $eqLogic->save();
          message::add('wifip', __('Sauvegarde du SSID', __FILE__));
        }
        $connFile = shell_exec('nmcli --fields TYPE,FILENAME con show --active | grep -i wifi | cut -c46-600');
        message::add('atlas', __('Suppression des profils pour', __FILE__) . ' ' . $connFile);
        shell_exec('sudo find /etc/NetworkManager/system-connections -type f ! -name "' . $connFile . '" -delete');
        message::add('atlas', __('Suppression effectuée, veuillez redémarrer.', __FILE__));
        break;
    }
    $eqLogic->cron5($eqLogic->getId());
  }

  /*     * **********************Getteur Setteur*************************** */
}
