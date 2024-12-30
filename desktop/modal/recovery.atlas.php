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
include_file('core', 'atlas', 'class.js', 'atlas');
$_mode = init('mode', atlas::getRecoveryMode());
sendVarToJS('_mode', $_mode);
if ($_mode == 'emmc') {
?>
  <h3>{{Restauration du système}}</h3>
<?php
} else {
?>
  <h3>{{Clé USB de restauration}}</h3>
<?php
}
?>
<img src="<?php echo config::byKey('product_connection_image'); ?>" alt="Product Image">
<div class=" bold" id="recovery-step"></div>
<div class="text-center" id="atlas-recovery">
  <div class="progress hidden">
    <div id="recovery-progress" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
  </div>
  <div id="recovery-details"></div>
  <?php
  if ($_mode == 'emmc') {
  ?>
    <div class="alert alert-warning text-center" id="recovery-warning">{{Une sauvegarde récente doit être téléchargée avant de démarrer la restauration du système}}</div>
  <?php
  } else {
  ?>
    <div class="alert alert-warning text-center" id="recovery-warning">{{La clé USB sera formatée durant le processus}}</div>
  <?php
  }
  ?>

  <div>
    <button type="button" class="btn btn-danger" id="bt_cancel"><i class="fas fa-times"></i> {{Annuler}}</button>
    <button type="button" class="btn btn-success hidden" id="bt_start"><i class="fab fa-usb"></i> {{Démarrer}}</button>
    <button type="button" class="btn btn-primary hidden" id="bt_restart"><i class="fas fa-redo"></i> {{Redémarrer}}</button>
    <button type="button" class="btn btn-primary hidden" id="bt_stop"><i class="fas fa-stop"></i> {{Arrêter}}</button>
  </div>
</div>

<style>
  #md_atlasRecovery>.jeeDialogContent {
    display: grid;
    justify-items: center;
    align-items: center;
    grid-template-columns: 100%;
    grid-template-rows: 50px 250px 25px auto;
  }

  #md_atlasRecovery>.jeeDialogContent>img {
    max-width: 350px;
    max-height: 225px;
  }

  #atlas-recovery {
    width: 100%;
    height: 100%;
    min-height: 250px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center
  }

  #atlas-recovery>.progress {
    width: 100%;
    max-width: 600px;
  }

  #atlas-recovery>#recovery-details {
    height: 150px;
    overflow: hidden;
  }

  .bold {
    font-weight: bold;
  }
</style>

<script>
  var _atlasRecoveryInProgress = null

  if (_mode == 'usb') {
    usbDetect().then(() => {
      updateRecovery({
        step: '{{Cliquez sur le bouton "Démarrer" pour initier la création de la clé USB de restauration}}',
        details: ''
      })
      document.querySelector('.progress').addClass('hidden')
      document.getElementById('bt_start').removeClass('hidden')
    })
  } else if (_mode == 'emmc') {
    updateRecovery({
      step: '{{Cliquez sur le bouton "Démarrer" pour initier la restauration du système}}'
    })
    document.getElementById('bt_start').removeClass('hidden')
  }

  document.getElementById('atlas-recovery').addEventListener('click', function(event) {
    var _target = null

    if (_target = event.target.closest('#bt_start')) {
      document.getElementById('recovery-warning').addClass('hidden')
      _target.addClass('hidden')
      updateRecovery({
        step: '{{Initialisation...}}',
        details: "{{Démarrage}}",
        progress: 0
      })

      monitorRecovery()

      jeedom.atlas.startRecovery({
        global: false,
        type: _mode,
        success: function(_result) {
          stopRecoveryProgress()

          if (_result) {
            if (_mode == 'usb') {
              document.getElementById('bt_restart').removeClass('hidden')
              updateRecovery({
                step: '{{La clé USB de restauration du système est prête}}',
                details: '{{Cliquez sur le bouton "Redémarrer" sans débrancher la clé USB}}',
                progress: 100
              })
            } else if (_mode == 'emmc') {
              document.getElementById('bt_stop').removeClass('hidden')
              updateRecovery({
                step: '{{La restauration système est terminée}}',
                details: '{{Cliquez sur le bouton "Arrêter" puis débrancher la clé USB avant de redémarrer la box électriquement}}',
                progress: 100
              })
            }
          }
          document.getElementById('recovery-progress').removeClass('active')
        }
      })
      return
    }

    if (_target = event.target.closest('#bt_cancel')) {
      if (_atlasRecoveryInProgress) {
        bootbox.confirm('<div class="text-center alert alert-danger"><i class="fas fa-exclamation-triangle"></i> {{Annuler la procédure de restauration du système ?}}</div>', function(ok) {
          if (ok) {
            _target.addClass('hidden')
            stopRecoveryProgress()
            updateRecovery({
              details: '',
              progress: -1
            })
            jeedom.atlas.cancelRecovery({
              async: false
            })
            setTimeout(() => {
              jeeDialog.get('#md_atlasRecovery')?.destroy()
            }, 5000)
          }
        })
      } else {
        jeeDialog.get('#md_atlasRecovery')?.destroy()
      }
      return
    }

    if (_target = event.target.closest('#bt_restart')) {
      _target.addClass('hidden')
      redirect('http://jeedomatlasrecovery.local')
      jeedom.rebootSystem()
      return
    }

    if (_target = event.target.closest('#bt_stop')) {
      _target.addClass('hidden')
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
      let i = .5
      updateRecovery({
        step: '{{Détection de la clé USB...}}',
        details: "{{Veuillez insérer une clé USB dans le port noir en bas à droite (8Go minimum)}}",
        progress: i
      })
      _atlasRecoveryInProgress = setInterval(function() {
        if (usbConnected()) {
          stopRecoveryProgress()
          return resolve(true)
        }

        if (i == 200) {
          updateRecovery({
            details: '{{Abandon, clé USB non détectée}}',
            progress: -1
          })
          return stopRecoveryProgress()
        }

        i += .5
        updateRecovery({
          progress: i
        })
      }, 5000)
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
    _atlasRecoveryInProgress = setInterval(function() {
      jeedom.atlas.getRecoveryProgress({
        async: false,
        success: function(_data) {
          if (_data) {
            data = JSON.parse(_data)
            if (_atlasRecoveryInProgress || isset(data.progress) && data.progress < 0) {
              updateRecovery(data)
            }
          }
        }
      })
    }, 500)
  }

  function updateRecovery(_data) {
    if (isset(_data.step)) {
      document.getElementById('recovery-step').innerText = _data.step
    }
    if (isset(_data.details)) {
      document.getElementById('recovery-details').innerText = _data.details
    }
    if (isset(_data.progress)) {
      document.querySelector('.progress.hidden')?.removeClass('hidden')
      let progressbar = document.getElementById('recovery-progress')

      if (_data.progress < 0) {
        progressbar.classList = 'progress-bar ' + ((!_atlasRecoveryInProgress) ? 'progress-bar-warning' : 'progress-bar-danger')
        progressbar.style.width = '100%'
        progressbar.setAttribute('aria-valuenow', 100)
        progressbar.innerText = (!_atlasRecoveryInProgress) ? "{{Annulation demandée}}" : '{{Une erreur est survenue}}'
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
    let i = .5
    updateRecovery({
      step: '{{Redémarrage...}}',
      details: "{{Veuillez patienter}}",
      progress: i
    })

    _atlasRecoveryInProgress = setInterval(function() {
      ping(_url).then(_ping => {
        if (_ping) {
          stopRecoveryProgress()
          updateRecovery({
            details: '{{Redirection vers la page de connexion}}',
            progress: 100
          })
          return setTimeout(function() {
            top.location.href = _url
          }, 2000)
        }

        if (i == 200) {
          updateRecovery({
            details: "{{Abandon, pas de réponse de l'adresse}} " + _url,
            progress: -1
          })
          return stopRecoveryProgress()
        }

        i += .5
        updateRecovery({
          progress: i
        })
      })
    }, 5000)
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
      image.src = _url + '/favicon.ico?t=' + new Date().getTime()
    })
  }

  function stopRecoveryProgress() {
    clearInterval(_atlasRecoveryInProgress)
    _atlasRecoveryInProgress = null
  }
</script>
