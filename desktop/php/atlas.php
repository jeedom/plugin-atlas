<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('atlas');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor logoPrimary" data-mode="<?= atlas::getRecoveryMode() ?>" id="bt_recovery">
				<i class="fas fa-upload"></i>
				<br>
				<span>{{Restauration système}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes Modules Atlas}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Atlas n\'a été trouvé}}</div>';
		} else {
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div>
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
					<!-- </a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span> -->
				</a><a class="btn btn-sm btn-success eqLogicAction roundedRight" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
					<!-- </a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}} -->
				</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>
							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Wifi}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr ipfixwifienabled" data-l1key="configuration" data-l2key="wifiEnabled" id="wifiEnabledCheck" unchecked />{{Activer}}</label>
								</div>
							</div>
							<div class="form-group wifi" style="display:none">
								<label class="col-sm-4 control-label">{{Réseau wifi}}</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control nohotspot" data-l1key="configuration" data-l2key="wifiSsid"></select>
								</div>
								<div class="col-lg-2">
									<a class="btn btn-info" id="bt_refreshWifiList"><i class="fas fa-sync-alt"></i></a>
								</div>
							</div>
							<div class="form-group wifi" style="display:none">
								<label class="col-sm-4 control-label">{{Mot de passe wifi}}</label>
								<div class="col-sm-6">
									<input type="password" class="eqLogicAttr form-control nohotspot" data-l1key="configuration" data-l2key="wifiPassword" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Hotspot}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr ipfixwifienabled" data-l1key="configuration" data-l2key="hotspotEnabled" id="hotspotEnabledCheck" unchecked />{{Activer}}</label>
								</div>
							</div>
							<div class="form-group wifihotspot" style="display:none">
								<label class="col-sm-4 control-label">{{SSID du hotspot}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ssidHotspot" />
								</div>
							</div>
							<div class="form-group wifihotspot" style="display:none">
								<label class="col-sm-4 control-label">{{Clef du hotspot}}</label>
								<div class="col-sm-6">
									<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="mdpHotspot" />
								</div>
							</div>
							<div class="form-group" style="display:none">
								<label class="col-sm-4 control-label">{{DHCP Hotspot}} :</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" id='dnsSelect' data-l1key="configuration" data-l2key="dns">
										<option id="dnsDesactivated" value="desactivated">{{Désactivé}}</option>
										<option id="dnsWlan0" value="wlan0">{{Wifi Hotspot}}</option>
										<option id="dnsEth0" value="eth0">{{Ethernet Hotspot}}</option>
									</select>
								</div>
							</div>
						</div>

						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-lg-4 control-label">{{Adresse MAC ethernet}}</label>
								<div class="col-lg-6">
									<span class="label label-info macLan" style="font-size:1em;cursor:default;"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-4 control-label">{{Adresse IP ethernet}}</label>
								<div class="col-lg-6">
									<span class="label label-info ipLan" style="font-size:1em;cursor:default;"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-4 control-label">{{Adresse MAC Wifi}}</label>
								<div class="col-lg-6">
									<span class="label label-info macWifi" style="font-size:1em;cursor:default;"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-4 control-label">{{Adresse IP Wifi}}</label>
								<div class="col-lg-6">
									<span class="label label-info ipWifi" style="font-size:1em;cursor:default;"></span>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>

			<div role="tabpanel" class="tab-pane" id="commandtab">
				<!-- <a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a> -->
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:500px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th>{{Valeur}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>

						</tbody>
					</table>
				</div>

			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'atlas', 'js', 'atlas'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
