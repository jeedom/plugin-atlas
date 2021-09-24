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
      $return['progress_file'] = jeedom::getTmpFolder('atlas') . '/dependance';
      $return['state'] = 'ok';
      if (exec(system::getCmdSudo() . system::get('cmd_check') . '-E "rsync" | wc -l') < 2) {
        $return['state'] = 'nok';
      }
      return $return;
    }

    public static function dependancy_install() {
      log::remove(__CLASS__ . '_update');
      return array('script' => __DIR__ . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('atlas') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

    /*     * ***********************Methode static*************************** */

/* ----- RECOVERY et MIGRATION ----- */
public static function put_ini_file($file, $array, $i = 0){
    $str="[core]\n";
    foreach ($array as $k => $v){
      if (is_array($v)){
        $str.=str_repeat(" ",$i*2)."[$k]".PHP_EOL;
        $str.=put_ini_file("",$v, $i+1);
      }else
        $str.=str_repeat(" ",$i*2)."$k = $v".PHP_EOL;
    }
  if($file)
      return file_put_contents($file,$str);
    else
      return $str;
}

  public static function startMigration($target = 'emmc'){
      log::clear('migrate');
      config::save('migrationText', 'prelancement');
      config::save('migration', 0);
      $path_target='';
      log::add('atlas', 'debug', 'PATH TARGET');
      if(file_exists('/dev/mmcblk2') && $target == 'emmc'){
        $path_target = '/dev/mmcblk2';
        config::save('migrationText', 'emmc');
        if(atlas::ddImg($path_target)){
          atlas::recoveryemmcMount($path_target);
          return 'ok';
        }
        return 'nok';
      }elseif(file_exists('/dev/mmcblk1') && $target == 'emmc'){
        $path_target = '/dev/mmcblk1';
        config::save('migrationText', 'emmc');
        if(atlas::ddImg($path_target)){
          atlas::recoveryemmcMount($path_target);
          return 'ok';
        }
        return 'nok';
      }elseif(file_exists('/dev/sda') && $target == 'usb'){
        $path_target = '/dev/sda';
        config::save('migrationText', 'usb');
        if(atlas::ddImg($path_target)){
          atlas::recoveryUsbMount($path_target);
          return 'ok';
        };
        return 'nok';
      }else{
        log::add('atlas', 'debug', 'ERREUR TARGET DEVICE');
        config::save('migrationText', 'errorTarget');
        return 'nok';
      }
  }

  public static function recoveryUsbMount($devusb = '/dev/sda'){
    config::save('migrationText', 'finalUSB');
    config::save('migration', 101);
    if(!file_exists('/mnt/usb')){
      log::add('atlas', 'debug', 'creation /mnt/usb');
      shell_exec('sudo mkdir /mnt/usb');
    }
    shell_exec('sudo umount /mnt/usb');
    log::add('atlas', 'debug', 'FSDISK -d');
    shell_exec('sudo sfdisk -d '.$devusb.' > sda_partition_bak.dmp');
    config::save('migration', 110);
    log::add('atlas', 'debug', 'growpart');
    shell_exec('sudo growpart -N '.$devusb.' 1');
    shell_exec('sudo growpart '.$devusb.' 1');
    config::save('migration', 120);
    log::add('atlas', 'debug', 'verification de la partition de boot');
    shell_exec('sudo e2fsck -fy '.$devusb.'1');
    log::add('atlas', 'debug', 'resize de la partition de boot');
    shell_exec('sudo resize2fs '.$devusb.'1 12G');
    config::save('migration', 130);
    log::add('atlas', 'debug', 'mount de la partition');
    shell_exec('sudo mount '.$devusb.'1 /mnt/usb');
    if(!file_exists('/mnt/usb/var/www/html/data/imgOs')){
      shell_exec('sudo mkdir /mnt/usb/var/www/html/data/imgOs');
    }else{
      if(file_exists('/mnt/usb/var/www/html/data/imgOs/jeedomAtlas.img.gz')){
        shell_exec('sudo rm /mnt/usb/var/www/html/data/imgOs/jeedomAtlas.img.gz');
      }
    }
    log::add('atlas', 'debug', 'montage clé usb');
    $ini_array = parse_ini_file('/mnt/usb/var/www/html/data/custom/custom.config.ini');
    log::add('atlas', 'debug', '--------------');
    $ini_array['product_name'] = 'Jeedom Atlas Recovery';
    $ini_array['path_wizard'] = 'data/custom/atlasRecoveryWizard.json';
    $ini_array['product_connection_image'] = 'core/img/logo-jeedom-atlas-recovery-grand-nom-couleur.svg';
    atlas::put_ini_file('/mnt/usb/var/www/html/data/custom/custom.config.ini', $ini_array);
    config::save('migration', 140);
    log::add('atlas', 'debug', 'changement ini fait');
    shell_exec('sudo bash -c \'echo "JeedomAtlasRecovery" > /mnt/usb/etc/hostname\'');
    log::add('atlas', 'debug', 'changement hostname fait');
    shell_exec('sudo cp /var/www/html/plugins/atlas/data/recovery/atlasRecoveryWizard.json /mnt/usb/var/www/html/'.$ini_array['path_wizard']);
    //shell_exec('sudo cp /var/www/html/plugins/atlas/data/recovery/logo-jeedom-atlas-recovery-grand-nom-couleur.svg /mnt/usb/var/www/html/'.$ini_array['product_connection_image']);
    config::save('migration', 150);
    log::add('atlas', 'debug', '--------------');
    log::add('atlas', 'debug', 'cp de l\'image');
    shell_exec('sudo cp /var/www/html/data/imgOs/jeedomAtlas.img.gz /mnt/usb/var/www/html/data/imgOs/jeedomAtlas.img.gz');
    log::add('atlas', 'debug', 'Fin');
    config::save('migrationText', 'endUSB');
    config::save('migration', 200);
  }

  public static function recoveryemmcMount($devemmc = '/dev/mmcblk1'){
    config::save('migrationText', 'finalUSB');
    config::save('migration', 101);
    if(!file_exists('/mnt/usb')){
      log::add('atlas', 'debug', 'creation /mnt/usb');
      shell_exec('sudo mkdir /mnt/usb');
    }
    config::save('migration', 120);
    shell_exec('sudo umount /mnt/usb');
    log::add('atlas', 'debug', 'FSDISK -d');
    shell_exec('sudo sfdisk -d '.$devemmc.' > mmcblk1_partition_bak.dmp');
    log::add('atlas', 'debug', 'growpart');
    config::save('migration', 140);
    shell_exec('sudo growpart -N '.$devemmc.' 1');
    shell_exec('sudo growpart '.$devemmc.' 1');
    config::save('migration', 150);
    log::add('atlas', 'debug', 'verification de la partition de boot');
    shell_exec('sudo e2fsck -fy '.$devemmc.'1');
    config::save('migration', 180);
    log::add('atlas', 'debug', 'resize de la partition de boot');
    shell_exec('sudo resize2fs '.$devemmc.'p1');
    log::add('atlas', 'debug', 'mount de la partition');
    log::add('atlas', 'debug', 'Fin');
    config::save('migrationText', 'endEMMC');
    config::save('migration', 200);
  }

  public static function ddImg($target){
    log::add('atlas', 'debug', 'IN CREATE LOG');
      config::save('migrationText', 'verifdd');
     if(atlas::downloadImage()){
       config::save('migrationText', 'dd');
       log::add('atlas', 'debug', '(sudo cat /var/www/html/data/imgOs/jeedomAtlas.img.gz | sudo gunzip | sudo dd of='.$target.' bs=512 status=progress) > '.log::getPathToLog('migrate').' 2>&1');
       shell_exec('(sudo cat /var/www/html/data/imgOs/jeedomAtlas.img.gz | sudo gunzip | sudo dd of='.$target.' bs=512 status=progress) > '.log::getPathToLog('migrate').' 2>&1');
       return true;
     }else{
       log::add('atlas', 'debug', 'ERREUR IMAGE MIGRATE');
       config::save('migrationText', 'errorDd');
       return false;
     }
  }

public static function marketImg(){
  $jsonrpc = repo_market::getJsonRpc();
  if (!$jsonrpc->sendRequest('box::atlas_image_url')) {
			throw new Exception($jsonrpc->getErrorMessage());
		}
	$urlArray = $jsonrpc->getResult();
	if($urlArray['url'] && $urlArray['SHA256'] && $urlArray['size']){
    return $urlArray;
  }
  return false;
}

public static function downloadImage(){
    $urlArray = atlas::marketImg();
    if(!$urlArray){
      log::add('atlas', 'debug', 'probleme avec le market.');
      return false;
    }
		$url = $urlArray['url'];
		$size = $urlArray['SHA256'];
    log::add('atlas', 'debug', 'IN DOWNALOAD > '.$size);
		exec('sudo pkill -9 wget');
    $path_imgOs = '/var/www/html/data/imgOs';
    if(!file_exists($path_imgOs)){
       mkdir($path_imgOs, 0644);
    }
    $find = false;
    $fichier = $path_imgOs.'/jeedomAtlas.img.gz';
    log::add('atlas', 'debug', 'fichier > '.$fichier);
    if(file_exists($fichier)){
      log::add('atlas', 'debug', 'existe');
      $sha_256 = hash_file('sha256', $fichier);
      log::add('atlas', 'debug', 'size > '.$size);
      log::add('atlas', 'debug', 'size > '.$sha_256);
      if($size == $sha_256){
          log::add('atlas', 'debug', 'SHA pareil');
          $find = true;
      }else{
          log::add('atlas', 'debug', 'SHA pas pareil');
          //RM fichier
          //unlink($fichier);
      }
    }
     if($find == false){
       config::save('migrationText', 'upload');
        log::add('atlas', 'debug', 'find a False');
        shell_exec('sudo wget --progress=dot --dot=mega '.$url.' -a '.log::getPathToLog('downloadImage').' -O '.$path_imgOs.'/jeedomAtlas.img.gz >> ' . log::getPathToLog('downloadImage').' 2&>1');
        $sha_256 = hash_file('sha256', $fichier);
        if($size == $sha_256){
          return true;
        }else{
          return false;
        }
     }
     return true;

}

public static function loopPercentage(){
    $urlArray = atlas::marketImg();
    $size = $urlArray['size'];
    $GO = $size;
    $MO = $GO*1024;
    $KO = $MO*1024;
    $BytesGlobal = $KO*1024;
    $level_percentage = 0;
    config::save('migration', $level_percentage);
    while(config::byKey('migration') < 100){
       log::add('atlas', 'debug', $level_percentage);
       sleep(1);
       $level_percentage = atlas::percentageProgress($BytesGlobal);
       if(config::byKey('migration') < 101){
         config::save('migration', $level_percentage);
       }else{
         log::add('atlas', 'debug', 'NON save pour le 100%');
       }
    }
}

  public static function percentageProgress($BytesGlobal){
      $logMigrate = log::get('migrate', 0, 1);
      $logMigrateAll = log::get('migrate', 0, 10);

      $pos = self::posOut($logMigrateAll);
      $firstln = $logMigrate[0];
      log::add('atlas', 'debug', 'AVANCEMENT : '.$firstln);

      if($pos == false){
         $valueByte = stristr($firstln, 'bytes', true);
         $pourcentage = round((100*$valueByte)/$BytesGlobal, 2);
         log::add('atlas', 'debug', 'ETAT: ' .$pourcentage. '%');
         log::clear('migrate');
         if($valueByte == '' || $valueByte == null){
         }else{
            return $pourcentage;
         }
      }else{
         log::add('atlas', 'debug', 'FIN');
         log::add('atlas', 'debug', '100%');
         return 100;
      }

  }


  public static function posOut($needles){
       foreach($needles as $needle){
            $rep = strpos($needle, 'records');
            if($rep != false){
              log::add('atlas', 'debug', ' FIN de la Migration ');
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
			log::add('atlas', 'debug', 'Pull Cron pour atlas');
			$atlas->wifiConnect();
			if ($atlas->getIsEnable() != 1) {continue;};
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
			$wifiIp= shell_exec("sudo ifconfig wlan0 | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1'");
			$lanIp= shell_exec("sudo ifconfig eth0 | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1'");
			log::add('atlas','debug','Lan Ip is :' . $lanIp);
			log::add('atlas','debug','Wifi Ip is :' . $wifiIp);
			$atlas->checkAndUpdateCmd('isconnect', $wifiup);
			$atlas->checkAndUpdateCmd('isconnecteth', $ethup);
			$atlas->checkAndUpdateCmd('signal', $wifisignal);
			$atlas->checkAndUpdateCmd('lanip', $lanIp);
			$atlas->checkAndUpdateCmd('wifiip', $wifiIp);
			if ($atlas->getConfiguration('wifiEnabled',0) == 1){
				$atlas->checkAndUpdateCmd('ssid', $atlas->getConfiguration('wifiSsid',''));
			} else {
				$atlas->checkAndUpdateCmd('ssid', 'Aucun');
			}
		}
	}

/* ----- WIFI ----- */
	public static function start() {
		log::add('atlas','debug','Jeedom started checking all connections');
		foreach (eqLogic::byType('atlas') as $atlas) {
			$atlas->wifiConnect();
		}
	}

	public static function isWificonnected ($ssid) {
		$result = shell_exec("sudo nmcli d | grep '" . $ssid . "'");
		log::add('atlas','debug',$result);
		if (strpos($result,'connected') === false && strpos($result,'connecté') === false){
			return false;
		}
		return true;
	}

  	public static function isWifiProfileexist($ssid) {
		$result = shell_exec("nmcli --fields NAME con show");
		$countProfile = substr_count($result, $ssid);
      	if ($countProfile > 1){
        	log::add('atlas','debug','suppression des profils');
        	shell_exec("nmcli --pretty --fields UUID,TYPE con show | grep wifi | awk '{print $1}' | while read line; do nmcli con delete uuid  $line; done");
        	return true;
        }else if ($countProfile == 1){
        	return true;
        }else{
        	return false;
        }
	}

	public static function listWifi($forced = false) {
		$eqLogic = eqLogic::byType('atlas');
		log::add('atlas','debug','Wifi enabled : ' . $eqLogic[0]->getConfiguration('wifiEnabled'));
		$return =[];
		if ($eqLogic[0]->getConfiguration('wifiEnabled') == true || $forced == true){
			$scanresult = shell_exec('sudo nmcli -f SSID,SIGNAL,SECURITY,CHAN -t -m tabular dev wifi list');
			$results = explode("\n", $scanresult);
			$return = array();
			foreach ($results as $result) {
				log::add('atlas','debug',$result);
				$result = str_replace('\:','$%$%',$result);
				$wifiDetail = explode(':',$result);
				$chan = $wifiDetail[3];
				$security = $wifiDetail[2];
				if ($security == ''){
					$security = 'Aucune';
				}
				$signal =  $wifiDetail[1];
				$ssid = str_replace('$%$%','\:',$wifiDetail[0]);
				if ($ssid != '') {
					log::add('atlas','debug',$ssid . ' with signal ' . $signal . ' and security ' . $security . ' on channel ' . $chan);
					if (isset($return[$ssid]) && $return[$ssid]['signal']> $signal){
						continue;
					}
					$return[$ssid] = array('ssid' => $ssid,'signal'=>$signal,'security'=>$security,'channel'=>$chan);
				}
			}
		}
		return $return;
	}

	public static function getMac($_interface = 'eth0') {
		$interfaceIp= shell_exec("sudo ifconfig $_interface | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1'");
		$interfaceMac = shell_exec("sudo ip addr show $_interface | grep -i 'link/ether' | grep -o -E '([[:xdigit:]]{1,2}:){5}[[:xdigit:]]{1,2}' | sed -n 1p");
		return [$interfaceMac,$interfaceIp];
	}

	public function wifiConnect() {
		if ($this->getConfiguration('wifiEnabled') == true){
			$ssid = $this->getConfiguration('wifiSsid','');
			if (self::isWificonnected($ssid) === false) {
				log::add('atlas','debug','Not Connected to ' . $ssid . '. Connecting ...');
				shell_exec("sudo ip link set wlan0");
              	if(self::isWifiProfileexist($ssid) === true) {
                	$exec = "sudo nmcli con up '".$ssid."'";
                }else{
                	$password = $this->getConfiguration('wifiPassword','');
                    if ($password != ''){
                        $exec = "sudo nmcli dev wifi connect '" . $ssid . "' password '" . $password . "'";
                    } else {
                    $exec ="sudo nmcli dev wifi connect '" . $ssid . "'";
                    }
                }
				log::add('atlas','debug','Executing ' . $exec);
				shell_exec($exec);
			}
		} else {
			log::add('atlas','debug','Executing sudo nmcli dev disconnect wlan0');
			shell_exec('sudo nmcli dev disconnect wlan0');
		}
	}

/* ----- FIN WIFI ----- */

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
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

  // Exécution d'une commande
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
            			if($ssidConf == ""){
					$eqLogic->setConfiguration('wifiSsid', shell_exec('iwgetid -r'));
					$eqLogic->save();
					message::add('wifip', 'sauvegarde ssid');
				}
				$connFile = shell_exec('nmcli --fields TYPE,FILENAME con show --active | grep -i wifi | cut -c46-600');
				message::add('atlas', 'suppression des profils pour'.$connFile);
				shell_exec('sudo find /etc/NetworkManager/system-connections -type f ! -name "'.$connFile.'" -delete');
				message::add('atlas', 'suppression OK merci de redémarrer');
				break;
		}
		$eqLogic->cron5($eqLogic->getId());
     }

    /*     * **********************Getteur Setteur*************************** */
}
