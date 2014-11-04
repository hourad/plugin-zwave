<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
include_file('3rdparty', 'jquery.fileupload/jquery.ui.widget', 'js');
include_file('3rdparty', 'jquery.fileupload/jquery.iframe-transport', 'js');
include_file('3rdparty', 'jquery.fileupload/jquery.fileupload', 'js');
sendVarToJS('eqType', 'zwave');
sendVarToJS('marketAddr', config::byKey('market::address'));

$controlerState = zwave::getZwaveInfo('controller::data::controllerState::value');
if ($controlerState === 0) {
    echo '<div id="div_inclusionAlert"></div>';
}
if ($controlerState === 1) {
    echo '<div class="alert jqAlert alert-warning" id="div_inclusionAlert" style="margin : 0px 5px 15px 15px; padding : 7px 35px 7px 15px;">{{Vous etes en mode inclusion. Recliquez sur le bouton d\'inclusion pour sortir de ce mode}}</div>';
}
if ($controlerState === 5) {
    echo '<div class="alert jqAlert alert-warning" id="div_inclusionAlert" style="margin : 0px 5px 15px 15px; padding : 7px 35px 7px 15px;">{{Vous etes en mode exclusion. Recliquez sur le bouton d\'exclusion pour sortir de ce mode}}</div>';
}
if ($controlerState === '') {
    echo '<div class="alert jqAlert alert-danger" style="margin : 0px 5px 15px 15px; padding : 7px 35px 7px 15px;">{{Impossible de contacter le serveur zway. Avez vous bien renseigné l\'IP ?}}</div>';
}
?>

<div class="row row-overflow">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <center style="margin-bottom: 5px;">
                    <a class="btn btn-default btn-sm tooltips" id="bt_syncEqLogic" title="{{Synchroniser équipement avec le Razberry}}" style="display: inline-block;"><i class="fa fa-refresh"></i> <span class="expertModeHidden">{{Synchroniser}}</span></a>
                    <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_inspectQueue" title="{{Inspecter la queue Z-wave}}" style="display: inline-block;"><i class="fa fa-exchange fa-rotate-90"></i></a>
                    <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_routingTable" title="{{Afficher la table de routage}}" style="display: inline-block;"><i class="fa fa-sitemap"></i></a>
                    <a class="btn btn-default btn-sm tooltips" id="bt_getFromMarket" title="{{Récuperer du market}}" style="display: inline-block;"><i class="fa fa-shopping-cart"></i> <span class="expertModeHidden">{{Market}}</span></a>
                    <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_adminRazberry" title="{{Administration avancée du zwave}}" style="display: inline-block;"><i class="fa fa-cogs"></i></a>
                    <?php if (config::byKey('isOpenZwave', 'zwave', 0) == 0) { ?>
                        <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_showZwayLog" title="{{Log du serveur z-way (valable uniquement si le serveur z-way est local)}}" style="display: inline-block;"><i class="fa fa-file-o"></i></a>
                    <?php } ?>
                </center>
                <?php
                if ($controlerState == 1) {
                    echo ' <a class="btn btn-success tooltips changeIncludeState" title="{{Inclure prériphérique Z-wave}}" data-mode="1" data-state="0" style="width : 100%;margin-bottom : 5px;"><i class="fa fa-sign-in fa-rotate-90"></i> Arreter inclusion</a>';
                } else {
                    echo ' <a class="btn btn-default tooltips changeIncludeState" title="{{Inclure prériphérique Z-wave}}" data-mode="1" data-state="1" style="width : 100%;margin-bottom : 5px;"><i class="fa fa-sign-in fa-rotate-90"></i> Mode inclusion</a>';
                }
                if ($controlerState == 5) {
                    echo ' <a class="btn btn-danger tooltips changeIncludeState" title="{{Exclure périphérique Z-wave}}" data-mode="0" data-state="0" style="width : 100%;margin-bottom : 5px;"><i class="fa fa-sign-out fa-rotate-90"></i> Arreter exclusion</a>';
                } else {
                    echo ' <a class="btn btn-default tooltips changeIncludeState" title="{{Exclure périphérique Z-wave}}" data-mode="0" data-state="1" style="width : 100%;margin-bottom : 5px;"><i class="fa fa-sign-out fa-rotate-90"></i> Mode exclusion</a>';
                }
                ?>
                <a class="btn btn-default eqLogicAction expertModeVisible" style="width : 100%;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un équipement}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="Rechercher" style="width: 100%"/></li>
                <?php
                foreach (eqLogic::byType('zwave') as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName() . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <div class="col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <div class="row">
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>{{Général}}</legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Nom de l'équipement}}</label>
                            <div class="col-lg-8">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label" >{{Objet parent}}</label>
                            <div class="col-lg-8">
                                <select class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (object::all() as $object) {
                                        echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Catégorie}}</label>
                            <div class="col-lg-8">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{Activer}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
                            </div>
                            <label class="col-lg-4 control-label">{{Visible}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                            </div>
                        </div>
                        <div class="form-group expertModeVisible">
                            <label class="col-lg-4 control-label">{{Node ID}}</label>
                            <div class="col-lg-4">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" />
                            </div>
                        </div>
                        <div class="form-group expertModeVisible">
                            <label class="col-lg-4 control-label">{{Délai maximum autorisé entre 2 messages (min)}}</label>
                            <div class="col-lg-4">
                                <input class="eqLogicAttr form-control" data-l1key="timeout" />
                            </div>
                        </div>
                        <div class="form-group expertModeVisible">
                            <label class="col-lg-4 control-label">{{Fréquence de rafraichissement des valeurs (cron)}}</label>
                            <div class="col-lg-4">
                                <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="refreshDelay"/>
                            </div>
                            <div class="col-lg-1">
                                <i class="fa fa-question-circle cursor bt_pageHelp floatright" data-name="cronSyntaxe"></i>
                            </div>
                        </div>
                        <div class="form-group expertModeVisible">
                            <label class="col-lg-4 control-label">{{Ne jamais mettre en erreur}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="nerverFail"/>
                            </div>
                            <label class="col-lg-4 control-label">{{Ne pas verifier la batterie}}</label>
                            <div class="col-lg-1">
                                <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="noBatterieCheck"/>
                            </div>
                        </div>
                    </fieldset> 
                </form>
            </div>
            <div class="col-lg-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>{{Informations}}</legend>

                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Module}}</label>
                            <div class="col-lg-5">
                                <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="device">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    foreach (zwave::devicesParameters() as $id => $info) {
                                        echo '<option value="' . $id . '">' . $info['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-lg-5">
                                <a class="btn btn-default" id="bt_configureDevice" title='{{Configurer}}'><i class="fa fa-wrench"></i></a>
                                <a class="btn btn-warning" id="bt_shareOnMarket"><i class="fa fa-cloud-upload"></i> {{Partager}}</a>
                                <a class="btn btn-default expertModeVisible" id="bt_displayZwaveData" title="Voir l'arbre Zwave"><i class="fa fa-tree"></i></a>
                                <a class="btn btn-default expertModeVisible" id="bt_showClass" title="Voir les classes zwave"><i class="fa fa-eye"></i></a>
                            </div>
                        </div>
                        <div class="form-group expertModeVisible">
                            <label class="col-lg-2 control-label">{{Envoyer une configuration}}</label>
                            <div class="col-lg-5">
                                <input id="bt_uploadConfZwave" type="file" name="file" data-url="plugins/zwave/core/ajax/zwave.ajax.php?action=uploadConfZwave">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">{{Marque}}</label>
                            <div class="col-lg-2">
                                <span class="zwaveInfo tooltips label label-default" data-l1key="brand"></span>
                            </div>
                            <label class="col-lg-2 control-label">{{Nom}}</label>
                            <div class="col-lg-3">
                                <span class="zwaveInfo tooltips label label-default" data-l1key="name"></span>
                            </div>
                        </div>

                        <div class="form-group expertModeVisible">
                            <label class="col-lg-2 control-label">{{Fabricant ID}}</label>
                            <div class="col-lg-2">
                                <span class="zwaveInfo tooltips label label-default" data-l1key="manufacturerId"></span>
                            </div>
                            <label class="col-lg-2 control-label">{{Type produit}}</label>
                            <div class="col-lg-2">
                                <span class="zwaveInfo tooltips label label-default" data-l1key="manufacturerProductType"></span>
                            </div>
                            <label class="col-lg-2 control-label">{{Produit ID}}</label>
                            <div class="col-lg-2">
                                <span class="zwaveInfo tooltips label label-default" data-l1key="manufacturerProductId"></span>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="col-lg-4 control-label">{{Batterie}}</label>
                                    <div class="col-lg-2">
                                        <span class="zwaveInfo tooltips label label-default" data-l1key="battery"></span>
                                    </div>
                                    <label class="col-lg-4 control-label">{{Interview}}</label>
                                    <div class="col-lg-2">
                                        <span class="zwaveInfo tooltips label label-default" data-l1key="interviewComplete"></span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-4 control-label">{{Dernière communication}}</label>
                                    <div class="col-lg-4">
                                        <span class="zwaveInfo tooltips label label-default" data-l1key="lastReceived"></span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 control-label">{{Etat}}</label>
                                    <div class="col-lg-4">
                                        <span class="zwaveInfo tooltips label label-default" data-l1key="state"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-1"></div>
                            <div class="col-lg-5">
                                <img src="core/img/no_image.gif" data-original=".jpg" id="img_device" class="img-responsive" />
                            </div>
                        </div>

                    </fieldset> 
                </form>
            </div>
        </div>

        <legend>Commandes</legend>
        <a class="btn btn-success btn-sm cmdAction expertModeVisible" data-action="add"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 130px;" class="expertModeVisible">{{Type}}</th>
                    <th style="width: 100px;" class="expertModeVisible">{{Instance ID}}</th>
                    <th style="width: 100px;" class="expertModeVisible">{{Class}}</th>
                    <th style="width: 200px;" class="expertModeVisible">{{Commande}}</th>
                    <th >{{Paramètres}}</th>
                    <th style="width: 100px;">{{Options}}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form>

    </div>
</div>

<?php include_file('3rdparty', 'jquery.lazyload/jquery.lazyload', 'js'); ?>
<?php include_file('desktop', 'zwave', 'js', 'zwave'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>