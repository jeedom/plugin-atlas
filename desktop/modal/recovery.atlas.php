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

if (!isConnect()) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('_type', init('type'));
?>
<!-- <script>
   function Good() {
    $('.img-atlas').attr('src', '<?php echo config::byKey('product_connection_image'); ?>');
  }

  function ping(ip, callback) {
    if (!this.inUse) {
      this.status = 'unchecked';
      this.inUse = true;
      this.callback = callback;
      this.ip = ip;
      var _that = this;
      this.img = new Image();
      this.img.onload = function() {
        _that.inUse = false;
        _that.callback('responded');

      };
      this.img.onerror = function(e) {
        if (_that.inUse) {
          _that.inUse = false;
          _that.callback('responded', e);
        }

      };
      this.start = new Date().getTime();
      this.img.src = "http://" + ip;
      this.timer = setTimeout(function() {
        if (_that.inUse) {
          _that.inUse = false;
          _that.callback('timeout');
        }
      }, 1500);
    }
  }

  $('#bt_go').off('click').on('click', function() {
    loopMigration = 0;
    progress(0);
    $('#bt_go').hide();
    $('#bt_relancer').hide();
    $('#div_progressbar').show();
    $('.progress').show();
    lancement();
    setTimeout(function() {
      migratepourcentage();
    }, 3000);
  });
  $('#bt_relancer').off('click').on('click', function() {
    loopMigration = 1;
    progress(0);
    $('#bt_go').hide();
    $('#bt_relancer').hide();
    $('#div_progressbar').show();
    $('.progress').show();
    migratepourcentage();
  });
  $('#bt_redemarrer').off('click').on('click', function() {
    $('#bt_redemarrer').hide();
    $('.textAtlas').text('{{Redémarrage en cours, vous serez automatiquement redirigé vers la page de connexion quand la box Atlas sera de nouveau opérationnelle.}}');
    redirectIP('jeedomatlasrecovery.local')
    jeedom.rebootSystem();
  });
  $('#bt_arreter').off('click').on('click', function() {
    $('#bt_arreter').hide();
    $('.textAtlas').text('{{Arrêt effectué, veuillez retirer la clé USB puis débrancher et rebrancher électriquement la box Jeedom Atlas. Vous serez automatiquement redirigé vers la page de connexion quand la box Atlas sera de nouveau opérationnelle.}}');
    redirectIP('jeedomatlas.local')
    jeedom.haltSystem();
  });

  function redirectIP(ip) {
    $('#div_progressbar').show();
    $('.progress').show();
    redirect++;
    new ping(ip, function(status, e) {
      console.log(status);
      if (redirect == 100) {
        $('.textAtlas').text('{{Impossible de trouver la box Atlas sur le réseau suite au redémarrage...}}');
        progress(-1);
      } else {
        progress(redirect);
        if (status == 'timeout') {
          setTimeout(function() {
            redirectIP(ip);
          }, 10000);
        } else if (status == 'responded') {
          $('.textAtlas').text('{{Redirection en cours vers}} ' + ip + '...');
          progress(100);
          top.location.href = 'http://' + ip;
        }
      }
    });
  }
</script> -->

<div class="col-md-12 text-center" id="recovery-modal">
  <h2>{{Restauration système}}</h2>
  <div class="col-md-6 col-md-offset-3 text-center">
    <img class="img-responsive center-block img-atlas" src="<?php echo config::byKey('product_connection_image'); ?>" />
  </div>
  <div class="col-md-12 text-center">
    <h3 class="text-center" id="recovery-step"></h3>
    <div class="label label-warning hidden" id="recovery-warn">{{Ne pas fermer la fenêtre durant la procédure}}</div>
    <br>
    <br>
    <div class="col-md-offset-1 col-md-10">
      <div class="progress hidden">
        <div id="recovery-progress" role="progressbar" style="width:0; height:20px;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
        </div>
      </div>
    </div>
    <div class="col-md-12 text-center">
      <h4 class="text-center" id="recovery-details"></h4>
      <br>
      <button type="button" class="btn btn-success hidden" id="bt_start"><i class="fab fa-usb"></i> {{Démarrer}}</button>
      <button type="button" class="btn btn-danger" id="bt_cancel"><i class="fas fa-times"></i> {{Annuler}}</button>
      <button type="button" class="btn btn-warning hidden" id="bt_restart"><i class="fas fa-redo"></i> {{Redémarrer}}</button>
      <button type="button" class="btn btn-danger hidden" id="bt_stop"><i class="fas fa-stop"></i> {{Arrêter}}</button>
    </div>
  </div>
</div>

<script>
  if (_type == 'usb') {
    usbDetect().then(() => {
      document.getElementById('recovery-step').innerText = '{{Clé USB détectée, cliquez sur le bouton "Démarrer" pour initier la procédure de restauration système.}}'
      document.querySelector('.progress').classList.add('hidden')
      document.getElementById('recovery-details').innerText = ''
      document.getElementById('bt_start').classList.remove('hidden')
    })
  } else if (_type == 'emmc') {
    document.getElementById('recovery-step').innerText = '{{Cliquez sur le bouton "Démarrer" pour débuter la restauration du système.}}'
    document.getElementById('bt_start').classList.remove('hidden')
  }

  document.getElementById('recovery-modal').addEventListener('click', function(event) {
    var _target = null

    if (_target = event.target.closest('#bt_start')) {
      _target.classList.add('hidden')
      document.getElementById('recovery-warn').classList.remove('hidden')
      monitorRecovery()
      jeedom.atlas.startRecovery({
        global: false,
        type: _type,
        success: function(result) {
          // console.log('Recovery result : ' + data)
          document.getElementById('recovery-warn').classList.add('hidden')
          document.getElementById('recovery-progress').classList.remove('active')
          if (result) {
            document.getElementById('bt_cancel').classList.add('hidden')
            if (_type == 'usb') {
              document.getElementById('bt_restart').classList.remove('hidden')
            } else if (_type == 'emmc') {
              document.getElementById('bt_stop').classList.remove('hidden')
            }
          }

        }
      })
      return
    }

    if (_target = event.target.closest('#bt_cancel')) {
      $('#md_modal').dialog('close')
      return
    }

    if (_target = event.target.closest('#bt_restart')) {
      // $('#md_modal').dialog('close')
      return
    }

    if (_target = event.target.closest('#bt_stop')) {
      // $('#md_modal').dialog('close')
      return
    }
  })

  function usbDetect() {
    return new Promise(function(resolve) {
      if (usbConnected()) {
        return resolve(true)
      }
      let i = 1
      updateRecovery({
        step: '{{Détection de la clé USB...}}',
        details: "{{Veuillez insérer une clé USB dans le port situé en bas à droite (8Go minimum).}}",
        progress: i
      })
      let usbDetection = setInterval(function() {
        if (usbConnected()) {
          clearInterval(usbDetection)
          return resolve(true)
        }
        if (i == 100) {
          clearInterval(usbDetection)
          updateRecovery({
            details: '{{Clé USB non détectée, abandon.}}',
            progress: -1
          })
        } else {
          i++
          updateRecovery({
            progress: i
          })
        }
      }, 10000)

      $('#md_modal').bind('dialogbeforeclose', function() {
        clearInterval(usbDetection)
        return true
      })
    })
  }

  function usbConnected() {
    var response
    jeedom.atlas.usbConnected({
      async: false,
      success: function(data) {
        // console.log('USB Detection : ' + data)
        response = data
      }
    })
    return response
  }

  function monitorRecovery() {
    let recoveryProgress = setInterval(function() {
      jeedom.atlas.getRecoveryProgress({
        global: false,
        success: function(data) {
          if (data) {
            data = JSON.parse(data)
            if (isset(data.progress) && data.progress < 0) {
              clearInterval(recoveryProgress)
            }
            updateRecovery(data)
          }
        }
      })
    }, 1000)

    let canCloseDialog = false
    $('#md_modal').bind('dialogbeforeclose', function() {
      if (canCloseDialog) {
        return true
      }
      jeedom.atlas.cancelRecovery({})
      setTimeout(() => {
        clearInterval(recoveryProgress)
        canCloseDialog = true
        $('#md_modal').dialog('close')
      }, 2500);
      return false
    })
  }

  function updateRecovery(_data) {
    if (isset(_data.step)) {
      document.getElementById('recovery-step').innerText = _data.step
    }
    if (isset(_data.details)) {
      document.getElementById('recovery-details').innerText = _data.details
    }
    if (isset(_data.progress)) {
      document.querySelector('.progress.hidden')?.classList.remove('hidden')
      let progressbar = document.getElementById('recovery-progress')
      if (_data.progress < 0) {
        progressbar.classList = 'progress-bar progress-bar-striped progress-bar-animated progress-bar-danger'
        progressbar.style.width = '100%'
        progressbar.setAttribute('aria-valuenow', 100)
        progressbar.innerText = '{{Une erreur est survenue, veuillez réessayer}}'
      } else if (_data.progress >= 100) {
        progressbar.classList = 'progress-bar progress-bar-striped progress-bar-animated progress-bar-success active'
        progressbar.style.width = '100%'
        progressbar.setAttribute('aria-valuenow', 100)
        progressbar.innerText = '100%'
      } else {
        progressbar.classList = 'progress-bar progress-bar-striped progress-bar-animated progress-bar-info active'
        progressbar.style.width = _data.progress + '%'
        progressbar.setAttribute('aria-valuenow', _data.progress)
        progressbar.innerText = _data.progress + '%'
      }
    }
  }
</script>
