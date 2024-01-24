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
?>

<script>
  $('#bt_go').show();
  $('#div_progressbar').hide();
  $('.progress').hide();
  $('#bt_redemarrer').hide();
  $('#bt_arreter').hide();
  var textMigrationValue = '';
  var pourcentageValue = 0;
  var errorFinal = 0;
  var loopMigration = 1;
  var redirect = 0;
  var typeDemande = '<?php echo init('typeDemande'); ?>';
  var startDemande = 'startMigration';

  if (typeDemande == 'emmc') {
    $('.textAtlas').text('{{Vous pouvez démarrer la restauration de votre box Jeedom Atlas.}}');
  } else if (typeDemande == 'usb') {
    startDemande = 'startUSB'
    $('.textAtlas').text('{{Veuillez brancher une clé USB supérieure à 10Go (port USB noir de droite).}}');
  }

  function logDownload() {
    $.ajax({
      type: 'POST',
      url: 'core/ajax/log.ajax.php',
      data: {
        action: 'get',
        log: 'downloadImage'
      },
      dataType: 'json',
      global: false,
      error: function(request, status, error) {
        setTimeout(logDownload, 1000);
      },
      success: function(data) {
        if (data.state != 'ok') {
          setTimeout(logDownload, 1000);
          return;
        }
        var log = '';
        if ($.isArray(data.result)) {
          for (var i in data.result.reverse()) {
            log += data.result[i] + "\n";
            if (data.result[i].indexOf("%") != -1) {
              var indexOfFirst = data.result[i].indexOf("%");
              var pourcentage = data.result[i].substring((indexOfFirst - 2), indexOfFirst);
              pourcentage = Number(pourcentage);
              progress(pourcentage);
            } else if (data.result[i].indexOf("Downloaded: 1 files") != -1) {
              _autoUpdate = 0;
              $('.textAtlasaddons').text('{{Image valide téléchargée.}}');
            }
          }
        }
      }
    });
  }

  function lancement() {
    $.ajax({
      type: "POST",
      url: "plugins/atlas/core/ajax/atlas.ajax.php",
      data: {
        action: startDemande
      },
      global: false,
      dataType: 'json',
      error: function(request, status, error) {
        handleAjaxError(request, status, error);
        errorFinal = 1;
      }
    });
  }

  function loop_percentage() {
    if (loopMigration == 0) {
      loopMigration = 1;
      $.ajax({
        type: "POST",
        url: "plugins/atlas/core/ajax/atlas.ajax.php",
        data: {
          action: "loop_percentage"
        },
        global: false,
        dataType: 'json',
        error: function(request, status, error) {
          handleAjaxError(request, status, error);
          errorFinal = 1;
        }
      });
    }
  }

  function migratepourcentage() {
    jeedom.config.load({
      configuration: 'migrationText',
      error: function(error) {
        console.log('error');
      },
      success: function(data) {
        textMigrationValue = data;
        if (textMigrationValue == 'dd') {
          loop_percentage();
        }
        jeedom.config.load({
          configuration: 'migrationTextfine',
          error: function(error) {
            console.log('error');
          },
          success: function(data) {
            $('.textAtlasaddons').text(data);
            jeedom.config.load({
              configuration: 'migration',
              error: function(error) {
                console.log('error');
              },
              success: function(data) {
                pourcentageValue = data;
                afficher();
                if (errorFinal == 0) {
                  setTimeout(function() {
                    migratepourcentage();
                  }, 5000);
                }
              }
            });
          }
        });
      }
    });
  }

  function afficher() {
    var tableauText = textMigration(textMigrationValue);
    $('.textAtlas').text(tableauText.text);

    if (tableauText.type == 'error') {
      progress(-1);
      errorFinal = 1;
    }

    if (tableauText.type == 'end') {
      progress(100);
      errorFinal = 1;
      if (typeDemande == 'usb') {
        $('#bt_redemarrer').show();
      } else {
        $('#bt_arreter').show();
      }
      $('.textAtlasaddons').hide();
    }

    if (tableauText.type == 'progress') {
      $('#div_progressbar').show();
      $('.progress').show();
    } else {
      $('#div_progressbar').hide();
      $('.progress').hide();
    }

    if (textMigrationValue == 'dd') {
      if (pourcentageValue != '') {
        progress(pourcentageValue);
      }
    }

    if (textMigrationValue == 'finalUSB' || textMigrationValue == 'finalEMMC') {
      if (pourcentageValue > 100 && pourcentageValue < 200) {
        progress((pourcentageValue - 100));
      }
    }

    if (textMigrationValue == 'upload') {
      logDownload();
    }

  }

  function textMigration(text) {
    switch (text) {
      case 'errorTarget':
        return {
          'text': '{{Erreur : Pas de support détecté (USB ou EMMC).}}', 'type': 'error'
        };
        break;
      case 'emmc':
        return {
          'text': '{{Démarrage de migration vers la mémoire interne.}}', 'type': 'start'
        };
        break;
      case 'usb':
        return {
          'text': '{{Création de la clé USB de restauration.}}', 'type': 'start'
        };
        break;
      case 'verifdd':
        return {
          'text': '{{Vérification de l'image Jeedom.}}', 'type': 'start'
        };
        break;
      case 'dd':
        return {
          'text': '{{Création en cours... (délai moyen : environ 15 minutes).}}', 'type': 'progress'
        };
        break;
      case 'errorDd':
        return {
          'text': '{{Une erreur est survenue lors de la migration.}}', 'type': 'error'
        };
        break;
      case 'upload':
        return {
          'text': '{{Téléchargement de l'image Jeedom.}}', 'type': 'progress'
        };
        break;
      case 'finalUSB':
        return {
          'text': '{{Finalisation de la clé USB.}}', 'type': 'progress'
        };
        break;
      case 'finalEMMC':
        return {
          'text': '{{Finalisation de la restauration Jeedom.}}', 'type': 'progress'
        };
        break;
      case 'endUSB':
        return {
          'text': '{{Restauration terminée, vous pouvez redémarrer Jeedom en cliquant ici}} :', 'type': 'end'
        };
        break;
      case 'endEMMC':
        return {
          'text': '{{Restauration terminée, veuillez retirer la clé USB puis débrancher et rebrancher électriquement la box Jeedom Atlas.}}', 'type': 'end'
        };
        break;
      default:
        return '{{Commande non reconnue}}';
    }
  }

  function progress(ProgressPourcent) {
    if (ProgressPourcent == -1) {
      $('#div_progressbar').removeClass('active progress-bar-success progress-bar-info progress-bar-warning');
      $('#div_progressbar').addClass('progress-bar-danger');
      $('#div_progressbar').width('100%');
      $('#div_progressbar').attr('aria-valuenow', 100);
      $('#div_progressbar').html('{{Erreur : Veuillez fermer puis relancer la demande.}}');
      return;
    }
    if (ProgressPourcent == 100) {
      $('#div_progressbar').removeClass('active progress-bar-info progress-bar-danger progress-bar-warning');
      $('#div_progressbar').addClass('progress-bar-success');
      $('#div_progressbar').width(ProgressPourcent + '%');
      $('#div_progressbar').attr('aria-valuenow', ProgressPourcent);
      $('#div_progressbar').html('{{FIN}}');
      Good();
      return;
    }
    $('#div_progressbar').removeClass('progress-bar-info progress-bar-danger progress-bar-warning');
    $('#div_progressbar').addClass('active progress-bar-success');
    $('#div_progressbar').width(ProgressPourcent + '%');
    $('#div_progressbar').attr('aria-valuenow', ProgressPourcent);
    $('#div_progressbar').html(ProgressPourcent + '%');
  }

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
</script>


<div class="col-md-12 text-center">
  <h2>{{Recovery Mode}}</h2>
</div>
<div class="col-md-6 col-md-offset-3 text-center"><img class="img-responsive center-block img-atlas" src="<?php echo config::byKey('product_connection_image'); ?>" /></div>
<div class="col-md-12 text-center">
  <p class="text-center">
  <h3 class="textAtlas"></h3>
  </p>
  <br /><br />
  <div class="col-md-12 text-center">
    <div id="contenuTextSpan" class="progress">
      <div class="progress-bar progress-bar-striped progress-bar-animated active" id="div_progressbar" role="progressbar" style="width: 0; height:20px;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
    </div>
  </div>
  <p class="text-center">
  <h4 class="textAtlasaddons"></h4>
  </p>
  <button type="button" class="btn btn-primary" id="bt_go">{{LANCER}}</button>
  <button type="button" class="btn btn-primary" id="bt_relancer">{{RELANCER}}</button>
  <button type="button" class="btn btn-primary" id="bt_redemarrer">{{REDEMARRER}}</button>
  <button type="button" class="btn btn-primary" id="bt_arreter">{{ARRETER}}</button>
</div>
</div>
