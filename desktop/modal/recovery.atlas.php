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
sendVarToJS('_mode', init('mode'));
include_file('core', 'atlas', 'class.js', 'atlas');
?>

<div class="col-md-12 text-center" id="atlas-recovery">
  <h2>{{Restauration système}}</h2>
  <div class="col-md-6 col-md-offset-3 text-center">
    <img class="img-responsive center-block img-atlas" src="<?php echo config::byKey('product_connection_image'); ?>" />
  </div>
  <div class="col-md-12 text-center">
    <h3 class="text-center" id="recovery-step"></h3>
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
      <button type="button" class="btn btn-warning hidden" id="bt_stop"><i class="fas fa-stop"></i> {{Arrêter}}</button>
    </div>
  </div>
</div>

<script>
  var _cancelRecovery = _inProgress = false

  if (_mode == 'usb') {
    usbDetect().then(() => {
      document.getElementById('recovery-step').innerText = '{{Clé USB détectée, cliquez sur le bouton "Démarrer" pour initier la procédure de restauration système}}'
      document.querySelector('.progress').classList.add('hidden')
      document.getElementById('recovery-details').innerText = ''
      document.getElementById('bt_start').classList.remove('hidden')
    })
  } else if (_mode == 'emmc') {
    document.getElementById('recovery-step').innerText = '{{Cliquez sur le bouton "Démarrer" pour débuter la restauration du système}}'
    document.getElementById('bt_start').classList.remove('hidden')
  }

  document.getElementById('atlas-recovery').addEventListener('click', function(event) {
    var _target = null

    if (_target = event.target.closest('#bt_start')) {
      _target.classList.add('hidden')
      monitorRecovery()
      jeedom.atlas.startRecovery({
        global: false,
        type: _mode,
        success: function(_result) {
          _inProgress = false
          document.getElementById('recovery-progress').classList.remove('active')
          if (_result) {
            if (_mode == 'usb') {
              document.getElementById('bt_restart').classList.remove('hidden')
            } else if (_mode == 'emmc') {
              document.getElementById('bt_stop').classList.remove('hidden')
            }
          } else {
            document.getElementById('bt_cancel').classList.add('hidden')
            _cancelRecovery = true
          }
        }
      })
      return
    }

    if (_target = event.target.closest('#bt_cancel')) {
      if (!_cancelRecovery) {
        bootbox.confirm("{{Annuler la restauration système ?}}", function(ok) {
          if (ok) {
            _target.classList.add('hidden')
            _cancelRecovery = true
            updateRecovery({
              step: "{{Annulation...}}",
              details: '',
              progress: -1
            })
            if (_inProgress) {
              jeedom.atlas.cancelRecovery({})
            }
          }
        })
      }
      return
    }

    if (_target = event.target.closest('#bt_restart')) {
      _target.classList.add('hidden')
      redirect('http://jeedomatlasrecovery.local')
      jeedom.rebootSystem()
      return
    }

    if (_target = event.target.closest('#bt_stop')) {
      _target.classList.add('hidden')
      redirect('http://jeedomatlas.local')
      jeedom.haltSystem()
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
        details: "{{Veuillez insérer une clé USB dans le port situé en bas à droite (8Go minimum)}}",
        progress: i
      })
      let usbDetection = setInterval(function() {
        if (_cancelRecovery) {
          return $('#md_modal').dialog('close')
        }

        if (usbConnected()) {
          clearInterval(usbDetection)
          return resolve(true)
        }

        if (i == 100) {
          updateRecovery({
            details: '{{Abandon, clé USB non détectée.}}',
            progress: -1
          })
          _cancelRecovery = true
          return clearInterval(usbDetection)
        }

        i++
        updateRecovery({
          progress: i
        })
      }, 10000)

      $('#md_modal').bind('dialogbeforeclose', function() {
        if (!_cancelRecovery) {
          document.getElementById('bt_cancel').triggerEvent('click')
          return false
        }

        clearInterval(usbDetection)
        return true
      })
    })
  }

  function usbConnected() {
    var response
    jeedom.atlas.usbConnected({
      async: false,
      success: function(_data) {
        response = _data
      }
    })
    return response
  }

  function monitorRecovery() {
    _inProgress = true

    let recoveryProgress = setInterval(function() {
      if (!_inProgress) {
        clearInterval(recoveryProgress)
        if (_cancelRecovery) {
          return setTimeout(() => {
            $('#md_modal').dialog('close')
          }, 2000)
        }
      }

      jeedom.atlas.getRecoveryProgress({
        global: false,
        success: function(_data) {
          if (_data) {
            data = JSON.parse(_data)
            if (!_cancelRecovery || isset(data.progress) && data.progress < 0) {
              updateRecovery(data)
            }
          }
        }
      })
    }, 950)

    $('#md_modal').bind('dialogbeforeclose', function() {
      if (!_cancelRecovery) {
        document.getElementById('bt_cancel').triggerEvent('click')
        return false
      }

      clearInterval(recoveryProgress)
      return true
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
        progressbar.innerText = (_cancelRecovery) ? "{{Annulation demandée, veuillez patienter}}" : '{{Une erreur est survenue, veuillez réessayer}}'
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

  function redirect(_url) {
    let i = 1
    updateRecovery({
      step: '{{Redémarrage...}}',
      details: "{{Détection automatique de la box sur le réseau (veuillez patienter)}}",
      progress: i
    })

    let atlasDetection = setInterval(function() {
      if (_cancelRecovery) {
        return $('#md_modal').dialog('close')
      }

      ping(_url).then((_ping) => {
        if (_ping) {
          clearInterval(atlasDetection)
          updateRecovery({
            details: '{{Box opérationnelle suite au redémarrage, redirection vers la page de connexion.}}',
            progress: 100
          })
          return setTimeout(function() {
            top.location.href = _url
          }, 2000)
        }

        if (i == 100) {
          updateRecovery({
            details: '{{Abandon, impossible de trouver la box sur le réseau suite au redémarrage.}}',
            progress: -1
          })
          _cancelRecovery = true
          return clearInterval(atlasDetection)
        }

        i++
        updateRecovery({
          progress: i
        })
      })
    }, 5000)

    $('#md_modal').bind('dialogbeforeclose', function() {
      if (!_cancelRecovery) {
        document.getElementById('bt_cancel').triggerEvent('click')
        return false
      }

      clearInterval(atlasDetection)
      return true
    })
  }

  function ping(_url) {
    return new Promise((resolve) => {
      let image = new Image();
      image.onload = function() {
        resolve(true)
      }
      image.onerror = function() {
        resolve(false)
      }
      image.src = _url + '/favicon.ico'
    })
  }

  // function Good() {
  //   $('.img-atlas').attr('src', '<?php echo config::byKey('product_connection_image'); ?>');
  // }
</script>
