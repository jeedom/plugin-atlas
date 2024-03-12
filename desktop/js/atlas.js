
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


printWifiList()
printMacLan()
printMacWifi()
function printWifiList($forced = false) {
  $.ajax({// fonction permettant de faire de l'ajax
    type: "POST", // methode de transmission des données au fichier php
    url: "plugins/atlas/core/ajax/atlas.ajax.php", // url du fichier php
    data: {
      action: "listWifi",
      mode: $forced,
    },
    dataType: 'json',
    async: true,
    error: function(request, status, error) {
      handleAjaxError(request, status, error)
    },
    success: function(data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({ message: data.result, level: 'danger' })
        return
      }
      var options = ''
      for (i in data.result) {
        options += '<option value="' + i + '">'
        options += data.result[i]['ssid'] + ' - Signal : ' + data.result[i]['signal'] + ' Canal : ' + data.result[i]['channel'] + ' Sécurité - ' + data.result[i]['security']
        options += '</option>'
      }
      $('.eqLogicAttr[data-l1key=configuration][data-l2key=wifiSsid]').empty().html(options)
    }
  })
}

function printMacLan() {
  $.ajax({// fonction permettant de faire de l'ajax
    type: "POST", // methode de transmission des données au fichier php
    url: "plugins/atlas/core/ajax/atlas.ajax.php", // url du fichier php
    data: {
      action: "macfinder",
      interfa: "eth0",
    },
    dataType: 'json',
    async: true,
    global: false,
    error: function(request, status, error) {
      handleAjaxError(request, status, error)
    },
    success: function(data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({ message: data.result, level: 'danger' })
        return
      }
      $('.macLan').empty().append(data.result[0])
      $('.ipLan').empty().append(data.result[1])
    }
  })
}

function printMacWifi() {
  $.ajax({// fonction permettant de faire de l'ajax
    type: "POST", // methode de transmission des données au fichier php
    url: "plugins/atlas/core/ajax/atlas.ajax.php", // url du fichier php
    data: {
      action: "macfinder",
      interfa: "wlan0",
    },
    dataType: 'json',
    async: true,
    global: false,
    error: function(request, status, error) {
      handleAjaxError(request, status, error)
    },
    success: function(data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({ message: data.result, level: 'danger' })
        return
      }
      $('.macWifi').empty().append(data.result[0])
      $('.ipWifi').empty().append(data.result[1])
    }
  })
}

$('#bt_refreshWifiList').on('click', function() {
  printWifiList(true)
})

window.setInterval(function() {
  printMacLan()
  printMacWifi()
}, 5000)

$("#table_cmd").sortable({ axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true })
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} }
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="type disable" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType disable" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
  tr += '<div style="margin-top:7px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
  tr += '</td>';
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
  }
  tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>'
  tr += '</tr>'
  $('#table_cmd tbody').append(tr)
  $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr')
  jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType))
}

function ajax_loop_percentage() {
  $.ajax({
    type: "POST",
    url: "plugins/atlas/core/ajax/atlas.ajax.php",
    data: {
      action: "loop_percentage"
    },
    dataType: 'json',
    error: function(request, status, error) {
      handleAjaxError(request, status, error)
    },
    success: function(data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({ message: data.result, level: 'danger' })
        return
      }
    }
  })
}


function ajax_start_percentage() {
  $.ajax({
    type: "POST",
    url: "plugins/atlas/core/ajax/atlas.ajax.php",
    data: {
      action: "start_percentage"
    },
    dataType: 'json',
    error: function(request, status, error) {
      handleAjaxError(request, status, error)
    },
    success: function(data) {
      if (data.state != 'ok') {
        $('#div_alert').showAlert({ message: data.result, level: 'danger' })
        return
      }
    }
  })
}


$('#bt_USBrecovery').off('click').on('click', function() {
  $('#md_modal').dialog({ title: "{{Création de la clé USB de restauration}}" }).load('index.php?v=d&plugin=atlas&modal=recovery.atlas&typeDemande=usb').dialog('open')
})

$('#bt_recovery').off('click').on('click', function() {
  $('#md_modal').dialog({ title: "{{Démarrage de la restauration}}" }).load('index.php?v=d&plugin=atlas&modal=recovery.atlas&typeDemande=emmc').dialog('open')
})

$('#wifiEnabledCheck').change(function() {
  if (this.checked == true) {
    $('.wifi').css('display', 'block')
    $('.wifihot').css('display', 'block')
  } else {
    $('.wifi').css('display', 'none')
    $('.wifihot').css('display', 'none')
    $('#hotspotEnabledCheck').prop('checked', false)
    $('.wifihotspot').css('display', 'none')
    $('.nohotspot').prop('disabled', false)
    $('#dnsDesactivated').prop('selected', true)
  }
})

$('#hotspotEnabledCheck').change(function() {
  if (this.checked == true) {
    $('.wifihotspot').css('display', 'block')
    $('.nohotspot').prop('disabled', true)
    $('#dnsWlan0').prop('disabled', false)
    $('#dnsEth0').prop('disabled', true)
    $("#dnsSelect option:selected").each(function() {
      if ($(this).val() == 'eth0' || $(this).val() == 'desactivated') {
        $('#dnsWlan0').prop('selected', true)
      }
    })
  } else {
    $('.wifihotspot').css('display', 'none')
    $('.nohotspot').prop('disabled', false)
    $('#dnsWlan0').prop('disabled', true)
    $('#dnsEth0').prop('disabled', false)
    $("#dnsSelect option:selected").each(function() {
      if ($(this).val() == 'wlan0') {
        $('#dnsDesactivated').prop('selected', true)
      }
    })
  }
})
