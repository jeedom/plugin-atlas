<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('atlas');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
			<?php
			if(trim(shell_exec('cat /etc/hostname')) == 'JeedomAtlas'){ ?>
			<div class="cursor logoSecondary" id="bt_USBrecovery">
				<i class="fab fa-usb"></i>
				<br>
				<span>{{Création clé Recovery (BETA)}}</span>
			</div>
			<?php }
				if(trim(shell_exec('cat /etc/hostname')) == 'JeedomAtlasRecovery'){ ?>
				<div class="cursor logoSecondary" id="bt_recovery">
					<i class="fas fa-clone"></i>
					<br>
					<span>{{Lancement Recovery (BETA)}}</span>
				</div>
			<?php } ?>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes Modules Atlas}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br/><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template n\'est paramétré, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
<div class="col-xs-12 eqLogic" style="display: none;">
	<!-- barre de gestion de l'équipement -->
	<div class="input-group pull-right" style="display:inline-flex;">
		<span class="input-group-btn">
			<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
			<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
			</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs">  {{Dupliquer}}</span>
			</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
			</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
			</a>
		</span>
	</div>
	<!-- Onglets -->
	<ul class="nav nav-tabs" role="tablist">
		<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
		<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
		<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
	</ul>
  <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    <div role="tabpanel" class="tab-pane active" id="eqlogictab"><br/>
      <div class="row">
	<div class="col-sm-7">
       <form class="form-horizontal">
            <fieldset>
                <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}<i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
                <div class="form-group">
                    <label class="col-lg-3 control-label">{{Nom de l'équipement}}</label>
                    <div class="col-lg-4">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                    </div>

                </div>
                <div class="form-group">
                <label class="col-lg-3 control-label" >{{Objet parent}}</label>
                    <div class="col-lg-4">
                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (jeeObject::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                 <div class="form-group">
                <label class="col-sm-3 control-label"></label>
                <div class="col-sm-9">
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                </div>
              </div>
				<legend><i class="fa fa-wifi"></i>  {{Wifi}}</legend>
                <div class="form-group">
				<div class="col-lg-2">
				</div>
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr ipfixwifienabled" data-l1key="configuration" data-l2key="wifiEnabled" id="wifiEnabledCheck" unchecked/>{{Activer le wifi}}</label>
				</div>
				<br />
				<div class="col-lg-2">
				</div>
					<div class="form-group wifihot" style="display:none">
						<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr ipfixwifienabled" data-l1key="configuration" data-l2key="hotspotEnabled" id="hotspotEnabledCheck" unchecked/>{{Activer le hotspot}}</label>
					</div>
					<div class="form-group wifihotspot" style="display:none">
						<br />
							<label class="col-lg-2 control-label">{{Ssid du hotspot}}</label>
							<div class="col-lg-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ssidHotspot" />
							</div>
					</div>
					<div class="form-group wifihotspot" style="display:none">
							<label class="col-lg-2 control-label">{{Clef du hotspot}}</label>
							<div class="col-lg-5">
									 <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="mdpHotspot" />
							</div>
					</div>
					<div class="form-group wifi" style="display:none">
						<br />
							<label class="col-lg-2 control-label">{{DHCP Hotspot :}}</label>
							<div class="col-lg-5">
									 <select class="eqLogicAttr form-control" id='dnsSelect' data-l1key="configuration" data-l2key="dns" >
										 <option id="dnsDesactivated" value="desactivated">{{Désactivé}}</option>
										 <option id="dnsWlan0" value="wlan0">{{Wifi Hotspot}}</option>
										 <option id="dnsEth0" value="eth0">{{Ethernet Hotspot}}</option>
									 </select>
							</div>
					</div>
                <div class="form-group wifi" style="display:none">
									<br />
                    <label class="col-lg-2 control-label">{{Réseau wifi}}</label>
                    <div class="col-lg-8">
                        <select class="eqLogicAttr form-control nohotspot" data-l1key="configuration" data-l2key="wifiSsid" ></select>
                    </div>
					<div class="col-lg-2">
                <a class="btn btn-info" id="bt_refreshWifiList"><i class="fas fa-sync-alt"></i></a>
            </div>
                </div>
                <div class="form-group wifi" style="display:none">
                    <label class="col-lg-2 control-label">{{Clef}}</label>
                    <div class="col-lg-8">
                         <input type="password" class="eqLogicAttr form-control nohotspot" data-l1key="configuration" data-l2key="wifiPassword" />
                    </div>
                </div>
            </fieldset>
        </form>
		</div>
<div class="col-sm-5">
  <form class="form-horizontal">
    <fieldset>
      <legend><i class="fa fa-info-circle"></i>  {{Informations}}</legend>
				<div class="form-group">
                    <label class="col-lg-4 control-label">{{Adresse MAC ethernet}}</label>
                    <div class="col-lg-4">
                        <span class="label label-info macLan" style="font-size:1em;cursor:default;"></span>
                    </div>
				</div>
				<div class="form-group">
                    <label class="col-lg-4 control-label">{{Adresse Ip ethernet}}</label>
                    <div class="col-lg-4">
                        <span class="label label-info ipLan" style="font-size:1em;cursor:default;"></span>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-lg-4 control-label">{{Adresse MAC wifi}}</label>
                    <div class="col-lg-4">
                        <span class="label label-info macWifi" style="font-size:1em;cursor:default;"></span>
                    </div>
                </div>
				<div class="form-group">
                    <label class="col-lg-4 control-label">{{Adresse Ip wifi}}</label>
                    <div class="col-lg-4">
                        <span class="label label-info ipWifi" style="font-size:1em;cursor:default;"></span>
                    </div>
                </div>
</fieldset>
</form>
</div>
</div>
</div>
<div role="tabpanel" class="tab-pane" id="commandtab">
       <table id="table_cmd" class="table table-bordered table-condensed">
             <thead>
                <tr>
                    <th>{{Nom}}</th><th>{{Options}}</th><th>{{Action}}</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

    </div>
</div>
</div>
</div>

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'atlas', 'js', 'atlas');?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js');?>
