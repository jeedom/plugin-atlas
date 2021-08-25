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
    
    /*     * ***********************Methode static*************************** */

  public static function dependancy_info() {
    $return = array();
    $return['progress_file'] = jeedom::getTmpFolder('atlas') . '/dependance';
    $return['state'] = 'ok';
    if (exec(system::getCmdSudo() . system::get('cmd_check') . '-E "python3\-requests|python3\-pyudev" | wc -l') < 2) {
      $return['state'] = 'nok';
    }
    if (exec(system::getCmdSudo() . 'pip3 list | grep -E "nmcli" | wc -l') < 8) {
      $return['state'] = 'nok';
    }
    return $return;
  }
	
  public static function dependancy_install() {
    log::remove(__CLASS__ . '_update');
    return array('script' => __DIR__ . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('atlas') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
  }



  public static function crea_log_avancement(){
	shell_exec('(sudo unzip -p /var/www/html/data/JeedomAtlas-V1-ARMBIAN-AOUT-2021.zip | sudo dd of=/dev/mmcblk2 obs=512 status=progress) > '.log::getPathToLog('migrate').' 2>&1');
  }


  public static function percentageAdvancmt(){
      
      $logMigrate = log::get('migrate', 0, 1);
      $logMigrateAll = log::get('migrate', 0, 10);
      $GO = 32; 
      $MO = $GO*1024;
      $KO = $MO*1024;
      $BytesGlobal = $KO*1024;
      
      $pos = self::posOut($logMigrateAll);
      $firstln = $logMigrate[0];
      log::add('atlas', 'debug', 'AVANCEMENT: '.$firstln);

      if($pos == false){
         log::add('atlas', 'debug', 'MAJ % ACTIVE');
         $valueByte = stristr($firstln, 'bytes', true);
         log::add('atlas', 'debug', $valueByte);
         $pourcentage = round((100*$valueByte)/$BytesGlobal, 2);
         log::add('atlas', 'debug', 'ETAT: ' .$pourcentage. '%');
         log::clear('migrate');
         if($valueByte == '' || $valueByte == null){
            log::add('atlas', 'debug', 'NULL');
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
       log::add('atlas', 'debug', ' Fonction posOut : ');
       foreach($needles as $needle){
          $rep = strpos($needle, 'records');
          log::add('atlas', 'debug', $needle.' >>> '.$res);
          if($rep != false){
            log::add('atlas', 'debug', ' RESULTAT VRAI ');
            return true;
          }else{
            log::add('atlas', 'debug', ' RESULTAT FAUX ');
          }
       }
       return false;

  }


public function startPercentage(){
    self::crea_log_avancement();
    $var_test = self::percentageAdvancmt();
    if ($var_test != 100){
       sleep(1);
       self::percentageAdvancmt();
    }

}

  
    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
      public static function cron5() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
      public static function cron10() {
      }
     */
    
    /*
     * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
      public static function cron15() {
      }
     */
    
    /*
     * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
      public static function cron30() {
      }
     */
    
    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {
      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {
      }
     */



    /*     * *********************Méthodes d'instance************************* */
    
 // Fonction exécutée automatiquement avant la création de l'équipement 
    public function preInsert() {
        
    }

 // Fonction exécutée automatiquement après la création de l'équipement 
    public function postInsert() {
        
    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement 
    public function preUpdate() {
        
    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement 
    public function postUpdate() {
        
    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement 
    public function preSave() {
        
    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement 
    public function postSave() {
        
    }

 // Fonction exécutée automatiquement avant la suppression de l'équipement 
    public function preRemove() {
        
    }

 // Fonction exécutée automatiquement après la suppression de l'équipement 
    public function postRemove() {
        
    }

    /*
     * Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire : permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
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
        
     }

    /*     * **********************Getteur Setteur*************************** */
}


