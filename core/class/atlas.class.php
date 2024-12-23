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

  /* ----- RECOVERY BEGIN  ----- */

  public static function getRecoveryMode() {
    $hostname = strtolower(trim(shell_exec('cat /etc/hostname')));
    if ($hostname == 'jeedomatlasrecovery') {
      return 'emmc';
    }
    return 'usb';
  }

  public static function startRecovery(string $_mode) {
    cache::delete('atlasRecoveryCancellation');
    cache::set('atlasRecovery', false, 60);

    try {
      $targetDevice = self::getTargetDevice($_mode);
      $imageFilepath = self::downloadImage();
      self::ddImage($imageFilepath, $targetDevice);
      self::finalizeRecovery($targetDevice, $imageFilepath);
      return true;
    } catch (Exception $e) {
      self::setRecoveryProgress(['details' => $e->getMessage(), 'progress' => -1], 2);
      return false;
    }
  }

  public static function cancelRecovery() {
    cache::set('atlasRecoveryCancellation', true, 60);
  }

  private static function finalizeRecovery($_targetDevice, $_imageFilepath) {
    self::setRecoveryProgress(['step' => __("Finalisation de la procédure...", __FILE__), 'details' => '', 'progress' => 0], 2);

    $partitionNumber = (file_exists($_targetDevice . '2') || file_exists($_targetDevice . 'p2')) ? 2 : 1;

    // EMMC
    if (stripos($_targetDevice, '/dev/mmc') !== false) {
      self::setRecoveryProgress(['details' => __("Redimensionnement du support de stockage", __FILE__), 'progress' => 25], 1);
      $cmd = shell_exec('sudo growpart ' . $_targetDevice . ' ' . $partitionNumber);
      self::setRecoveryProgress(['details' => $cmd], 1);

      self::setRecoveryProgress(['details' => __("Vérification du système de fichier", __FILE__), 'progress' => 50], 1);
      $cmd = shell_exec('sudo e2fsck -fy ' . $_targetDevice . 'p' . $partitionNumber);
      self::setRecoveryProgress(['details' => $cmd], 1);

      self::setRecoveryProgress(['details' => __("Redimensionnement du système de fichier", __FILE__), 'progress' => 75], 1);
      $cmd = shell_exec('sudo resize2fs ' . $_targetDevice . 'p' . $partitionNumber);
      self::setRecoveryProgress(['details' => $cmd], 1);

      if (cache::exist('atlasRecoveryCancellation')) {
        throw new Exception(__("La restauration système est terminée, débrancher la clé USB avant de redémarrer la box électriquement", __FILE__));
      }
    }

    // USB
    else if (stripos($_targetDevice, '/dev/sd') !== false) {
      if (!file_exists('/mnt/usb')) {
        shell_exec('sudo mkdir /mnt/usb');
      } else {
        shell_exec('sudo umount /mnt/usb');
      }

      self::setRecoveryProgress(['details' => __("Redimensionnement de la clé USB", __FILE__), 'progress' => 20], 1);
      $cmd = shell_exec('sudo growpart ' . $_targetDevice . ' ' . $partitionNumber);
      self::setRecoveryProgress(['details' => $cmd], 1);

      self::setRecoveryProgress(['details' => __("Vérification du système de fichier", __FILE__), 'progress' => 30], 1);
      $cmd = shell_exec('sudo e2fsck -fy ' . $_targetDevice . $partitionNumber);
      self::setRecoveryProgress(['details' => $cmd], 1);

      self::setRecoveryProgress(['details' => __("Redimensionnement du système de fichier", __FILE__), 'progress' => 40], 1);
      $cmd = shell_exec('sudo resize2fs ' . $_targetDevice . $partitionNumber . ' 8G');
      self::setRecoveryProgress(['details' => $cmd], 1);

      self::setRecoveryProgress(['details' => __("Personnalisation de la clé USB", __FILE__), 'progress' => 50], 1);
      shell_exec('sudo mount ' . $_targetDevice . $partitionNumber . ' /mnt/usb');

      $imgDir = pathinfo($_imageFilepath, PATHINFO_DIRNAME);
      if (!file_exists('/mnt/usb' . $imgDir)) {
        shell_exec('sudo mkdir /mnt/usb' . $imgDir);
      }

      $coreDir = str_replace('/data/imgOs', '', $imgDir);
      $iniFile = '/mnt/usb' . $coreDir . '/data/custom/custom.config.ini';
      $iniArray = parse_ini_file($iniFile);
      $iniArray['product_name'] = 'Jeedom Atlas Recovery';
      $iniArray['product_connection_image'] = 'core/img/logo-jeedom-atlas-recovery-grand-nom-couleur.svg';
      self::put_ini_file($iniFile, $iniArray);
      shell_exec('sudo bash -c \'echo "JeedomAtlasRecovery" > /mnt/usb/etc/hostname\'');
      shell_exec('sudo cp ' . $coreDir . '/plugins/atlas/data/recovery/logo-jeedom-atlas-recovery-grand-nom-couleur.svg /mnt/usb' . $coreDir . '/' . $iniArray['product_connection_image']);

      if (file_exists('/mnt/usb' . $_imageFilepath)) {
        self::setRecoveryProgress(['details' => __("Suppression de l'ancienne image système", __FILE__), 'progress' => 60], 1);
        shell_exec('sudo rm /mnt/usb' . $_imageFilepath);
      }

      self::setRecoveryProgress(['details' => __("Copie de l'image système", __FILE__), 'progress' => 75], 1);
      shell_exec('sudo scp -p ' . $_imageFilepath . ' /mnt/usb' . $_imageFilepath);

      if (stripos($cmd, 'error') !== false || !is_file('/mnt/usb' . $_imageFilepath)) {
        throw new Exception(__("Erreur lors de la copie de l'image système", __FILE__) . ' : ' . $cmd);
      }

      if (cache::exist('atlasRecoveryCancellation')) {
        throw new Exception(__("La restauration système est prête, redémarrer la box sans débrancher la clé USB", __FILE__));
      }
    }

    self::setRecoveryProgress(['details' => __("Procédure finalisée avec succès", __FILE__), 'progress' => 100], 2);
  }

  private static function ddImage($_imageFilepath, $_targetDevice) {
    self::setRecoveryProgress(['step' => __("Gravure de l'image système...", __FILE__), 'details' => __('Préparation de la gravure', __FILE__), 'progress' => 0], 2);

    $ext = pathinfo($_imageFilepath, PATHINFO_EXTENSION);
    if ($ext == 'gz') {
      $extract = 'gunzip';
      $uncompressed = shell_exec($extract . ' -l ' . $_imageFilepath . " | awk 'NR==2 {print $2}'");
    } else if ($ext == 'xz') {
      $extract = 'xz';
      $uncompressed = round(shell_exec($extract . ' -l ' . $_imageFilepath . " | awk 'NR==2 {print $5}'") * 1024 * 1024);
    } else {
      throw new Exception(__("Abandon, impossible de décompresser l'image système", __FILE__) . ' : ' . $ext);
    }
    $total = cmd::autoValueArray($uncompressed, 2, 'o');

    if (cache::exist('atlasRecoveryCancellation')) {
      throw new Exception(__("Annulation de la gravure à la demande de l'utilisateur", __FILE__));
    }

    self::setRecoveryProgress(['details' => __("Démarrage de la gravure", __FILE__)], 2);

    $cmd = 'sudo ' . $extract . ' -dc ' . $_imageFilepath . ' | sudo dd of=' . $_targetDevice . ' bs=4096 oflag=nocache status=progress 2>&1';
    $pipes = array();
    $error = false;
    $process = proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'a']], $pipes);

    if (is_resource($process)) {
      do {
        if (cache::exist('atlasRecoveryCancellation')) {
          proc_terminate($process);
          $error = __("Annulation de la gravure à la demande de l'utilisateur", __FILE__);
          break;
        }

        $line = stream_get_line($pipes[1], 256, '/s');

        if (stripos($line, 'error') !== false) {
          $error = $line;
          break;
        }

        if (substr($line, -1) == 'B') {
          $lineInArray = explode(' ', $line);
          if ($lineInArray[0] > 0) {
            $lineInArraySize = count($lineInArray);
            $percent = self::calculPercentProgress($lineInArray[0], $uncompressed);
            $done = cmd::autoValueArray($lineInArray[0], 2, 'o');
            $speed = $lineInArray[$lineInArraySize - 2] . str_replace('B', 'o', $lineInArray[$lineInArraySize - 1]);
            self::setRecoveryProgress(['details' => $done[0] . $done[1] . '/' . $total[0] . $total[1] . ' (' . $speed . '/s)', 'progress' => $percent]);
          }
        }

        $processStatus = proc_get_status($process);
      } while ($processStatus['running']);
    } else {
      $error = __("Erreur lors du démarrage de la gravure", __FILE__);
    }

    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    if ($error) {
      throw new Exception($error);
    }

    self::setRecoveryProgress(['details' => __("Gravure terminée avec succès", __FILE__), 'progress' => 100], 2);
  }

  private static function downloadImage() {
    self::setRecoveryProgress(['step' => __("Téléchargement de l'image système...", __FILE__), 'details' => __("Collecte des informations", __FILE__), 'progress' => 0], 2);

    jeedom::cleanFileSystemRight();
    $imgInfos = self::getImgInfosFromMarket();
    // Manually set $imgInfos for testings
    $imgInfos['url'] = 'https://images.jeedom.com/atlas/jeedomAtlas.img.xz';
    $imgInfos['SHA256'] = '811ce8f26cc6d0bc7fbede6e7cb469456d502ec8f17ad6183c7d87a4053f2f66';

    $downloadPath = realpath(__DIR__ . '/../../../../data') . '/imgOs';
    if (!file_exists($downloadPath)) {
      mkdir($downloadPath, 0644);
    }

    $downloadFilepath = $downloadPath . '/' . basename($imgInfos['url']);
    if (file_exists($downloadFilepath)) {
      self::setRecoveryProgress(['details' => __("Validation de l'image système", __FILE__), 'progress' => 99], 1);
      self::validateImage($downloadFilepath, $imgInfos['SHA256']);
      self::setRecoveryProgress(['details' => __("Image système validée avec succès", __FILE__), 'progress' => 100], 2);
      return $downloadFilepath;

      self::setRecoveryProgress(['details' => __('Image système invalide, reprise du téléchargement', __FILE__), 'progress' => 0], 1);
    }

    self::setRecoveryProgress(['details' => __("Début du téléchargement", __FILE__), 'progress' => 0], 1);
    $error = false;
    $ch = curl_init();
    $fp = fopen($downloadFilepath, 'wb');

    curl_setopt_array($ch, [
      CURLOPT_URL => $imgInfos['url'],
      CURLOPT_HEADER => false,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_FILE => $fp,
      CURLOPT_PROGRESSFUNCTION => ['self', 'downloadImageProgress'],
      CURLOPT_NOPROGRESS => false,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_FAILONERROR => true
    ]);

    curl_exec($ch);

    if (curl_errno($ch)) {
      $error = __("Erreur lors du téléchargement", __FILE__) . ' : ' . curl_error($ch);
    }

    curl_close($ch);
    fclose($fp);

    if ($error) {
      unlink($downloadFilepath);
      throw new Exception($error);
    }

    self::setRecoveryProgress(['details' => __("Validation de l'image système téléchargée", __FILE__), 'progress' => 99], 1);
    self::validateImage($downloadFilepath, $imgInfos['SHA256']);
    self::setRecoveryProgress(['details' => __("Image système téléchargée avec succès", __FILE__), 'progress' => 100], 2);
    return $downloadFilepath;
  }

  private static function getImgInfosFromMarket() {
    $jsonrpc = repo_market::getJsonRpc();
    if (!$jsonrpc->sendRequest('box::atlas_image_url')) {
      throw new Exception(__("Abandon, impossible de récupérer les informations sur l'image système", __FILE__) . ' : ' . $jsonrpc->getErrorMessage());
    }

    $imgInfos = $jsonrpc->getResult();
    if ($imgInfos['url'] && $imgInfos['SHA256']) {
      return $imgInfos;
    }

    throw new Exception(__("Abandon, informations sur l'image système manquantes", __FILE__) . ' : ' . print_r($imgInfos, true));
  }

  private static function downloadImageProgress($_resource, $_downloadSize, $_downloaded) {
    if (cache::exist('atlasRecoveryCancellation')) {
      return 1;
    }

    if ($_downloaded > 0 && $_downloadSize > 0) {
      $percent = self::calculPercentProgress($_downloaded, $_downloadSize, 99);
      $downloaded = cmd::autoValueArray($_downloaded, 2, 'o');
      $downloadSize = cmd::autoValueArray($_downloadSize, 2, 'o');
      $downloadSpeed = cmd::autoValueArray(curl_getinfo($_resource, CURLINFO_SPEED_DOWNLOAD), 2, 'o');
      self::setRecoveryProgress(['details' => $downloaded[0] . $downloaded[1] . '/' . $downloadSize[0] . $downloadSize[1] . ' (' . $downloadSpeed[0] . $downloadSpeed[1] . '/s)', 'progress' => $percent]);
    }
  }

  private static function validateImage(string $_filepath, string $_sha256) {
    if (cache::exist('atlasRecoveryCancellation')) {
      unlink($_filepath);
      throw new Exception(__("Annulation du téléchargement à la demande de l'utilisateur", __FILE__));
    }

    $sha256 = hash_file('sha256', $_filepath);
    if ($sha256 != $_sha256) {
      unlink($_filepath);
      throw new Exception(__("Erreur lors de la vérification de l'image système", __FILE__) . ' : ' . $sha256 . ' != ' . $_sha256);
    }
  }

  private static function setRecoveryProgress(array $_progress, int $_pause = null) {
    cache::byKey('atlasRecovery')->setValue(json_encode($_progress))->setLifetime(60)->save();
    if ($_pause) {
      $level = 'debug';
      if (isset($_progress['progress']) && $_progress['progress'] < 0) {
        if (cache::exist('atlasRecoveryCancellation')) {
          $level = 'warning';
        } else {
          $level = 'error';
        }
      }
      log::add(__CLASS__, $level, '[ATLAS RECOVERY] ' . print_r($_progress, true));
      sleep($_pause);
    }
  }

  public static function getRecoveryProgress() {
    return cache::byKey('atlasRecovery')->getValue();
  }

  public static function usbConnected() {
    foreach (['/dev/sda', '/dev/sdb', '/dev/sdc'] as $device) {
      if (file_exists($device)) {
        $deviceInfos = shell_exec('udevadm info -q path -n ' . $device);
        if (stripos($deviceInfos, '/usb1/1-1/') !== false) {
          return $device;
        }
      }
    }
    return false;
  }

  private static function getTargetDevice($_mode) {
    if ($_mode == 'usb' && $usb = self::usbConnected()) {
      return $usb;
    }

    if ($_mode == 'emmc') {
      foreach (['/dev/mmcblk2', '/dev/mmcblk1', '/dev/mmcblk0'] as $device) {
        if (file_exists($device)) {
          return $device;
        }
      }
    }

    throw new Exception(__('Abandon, support de destination introuvable', __FILE__) . ' : ' . $_mode);
  }

  private static function calculPercentProgress($_done, $_total, int $_max = 100) {
    $percent = round(($_done / $_total) * 100, 1);
    if ($percent < 0) {
      return 0;
    }

    if ($percent > $_max) {
      return $_max;
    }

    return $percent;
  }

  private static function put_ini_file($_file, $_array, $_i = 0) {
    $str = "[core]\n";
    foreach ($_array as $k => $v) {
      if (is_array($v)) {
        $str .= str_repeat(" ", $_i * 2) . "[$k]" . PHP_EOL;
        $str .= self::put_ini_file("", $v, $_i + 1);
      } else
        $str .= str_repeat(" ", $_i * 2) . "$k = $v" . PHP_EOL;
    }
    if ($_file)
      return file_put_contents($_file, $str);
    else
      return $str;
  }

  /* ----- RECOVERY END  ----- */

  public static function cron5($_eqlogic_id = null) {
    if ($_eqlogic_id !== null) {
      $eqLogics = array(eqLogic::byId($_eqlogic_id));
    } else {
      $eqLogics = eqLogic::byType('atlas');
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
    self::securityIp();
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
      shell_exec("nmcli --pretty --fields UUID,TYPE con show | grep wifi | awk '{print $1}'" . ' | while read line; do nmcli con delete uuid $line; done');
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
      self::activeHotSpot();
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
        self::activeHotSpot();
      }
    }
  }

  public static function activeHotSpot() {
    log::add(__CLASS__, 'debug', __('Activation du Hotspot.', __FILE__));
    $linkForHotspot = __DIR__ . '/../../resources/lnxrouter';
    $wlanLink = 'wlan0';
    $atlas = eqLogic::byLogicalId('wifi', __CLASS__);
    $interfaceInfo = self::getMac();
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
}
