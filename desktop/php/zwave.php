<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
include_file('3rdparty', 'jquery.fileupload/jquery.ui.widget', 'js');
include_file('3rdparty', 'jquery.fileupload/jquery.iframe-transport', 'js');
include_file('3rdparty', 'jquery.fileupload/jquery.fileupload', 'js');
sendVarToJS('eqType', 'zwave');
sendVarToJS('marketAddr', config::byKey('market::address'));

try{
    $controlerState = zwave::getZwaveInfo('controller::data::controllerState::value');
}catch(Exception $e){
   $controlerState = 0;
}
if ($controlerState === 0) {
    echo '<div id="div_inclusionAlert"></div>';
}
if ($controlerState === 1) {
    echo '<div class="alert jqAlert alert-warning" id="div_inclusionAlert" style="margin : 0px 5px 15px 15px; padding : 7px 35px 7px 15px;">{{Vous êtes en mode inclusion. Cliquez à nouveau sur le bouton d\'inclusion pour sortir de ce mode}}</div>';
}
if ($controlerState === 5) {
    echo '<div class="alert jqAlert alert-warning" id="div_inclusionAlert" style="margin : 0px 5px 15px 15px; padding : 7px 35px 7px 15px;">{{Vous êtes en mode exclusion. Cliquez à nouveau sur le bouton d\'exclusion pour sortir de ce mode}}</div>';
}
if ($controlerState === '') {
    echo '<div class="alert jqAlert alert-danger" style="margin : 0px 5px 15px 15px; padding : 7px 35px 7px 15px;">{{Impossible de contacter le serveur zway. Vérifiez que vous avez bien renseigné l\'adresse IP.}}</div>';
}
$eqLogics = eqLogic::byType('zwave');
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <center style="margin-bottom: 5px;">
                    <a class="btn btn-default btn-sm tooltips" id="bt_syncEqLogic" title="{{Synchroniser équipement avec le Razberry}}" style="display: inline-block;"><i class="fa fa-refresh"></i> <span class="expertModeHidden">{{Synchroniser}}</span></a>
                    <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_inspectQueue" title="{{Inspecter la queue Z-Wave}}" style="display: inline-block;"><i class="fa fa-exchange fa-rotate-90"></i></a>
                    <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_routingTable" title="{{Afficher la table de routage}}" style="display: inline-block;"><i class="fa fa-sitemap"></i></a>
                    <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_adminRazberry" title="{{Administration avancée du Z-Wave}}" style="display: inline-block;"><i class="fa fa-cogs"></i></a>
                    <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_healthRazberry" title="{{Santé du réseau Z-Wave}}" style="display: inline-block;"><i class="fa fa-medkit"></i></a>
                    <?php if (config::byKey('isOpenZwave', 'zwave', 0) == 0) { ?>
                        <a class="btn btn-default btn-sm tooltips expertModeVisible" id="bt_showZwayLog" title="{{Log du serveur z-way (valable uniquement si le serveur z-way est local)}}" style="display: inline-block;"><i class="fa fa-file-o"></i></a>
                        <?php } ?>
                    </center>
                    <?php
                    if ($controlerState == 1) {
                        echo ' <a class="btn btn-success tooltips changeIncludeState" title="{{Inclure périphérique Z-Wave}}" data-mode="1" data-state="0" style="width : 100%;margin-bottom : 5px;"><i class="fa fa-sign-in fa-rotate-90"></i> Arrêter inclusion</a>';
                    } else {
                        echo ' <a class="btn btn-default tooltips changeIncludeState" title="{{Inclure périphérique Z-Wave}}" data-mode="1" data-state="1" style="width : 100%;margin-bottom : 5px;"><i class="fa fa-sign-in fa-rotate-90"></i> Mode inclusion</a>';
                    }
                    if ($controlerState == 5) {
                        echo ' <a class="btn btn-danger tooltips changeIncludeState" title="{{Exclure périphérique Z-Wave}}" data-mode="0" data-state="0" style="width : 100%;margin-bottom : 5px;"><i class="fa fa-sign-out fa-rotate-90"></i> Arrêter exclusion</a>';
                    } else {
                        echo ' <a class="btn btn-default tooltips changeIncludeState" title="{{Exclure périphérique Z-Wave}}" data-mode="0" data-state="1" style="width : 100%;margin-bottom : 5px;"><i class="fa fa-sign-out fa-rotate-90"></i> Mode exclusion</a>';
                    }
                    ?>
                    <a class="btn btn-default eqLogicAction expertModeVisible" style="width : 100%;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un équipement}}</a>
                    <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="Rechercher" style="width: 100%"/></li>
                    <?php
                    foreach ($eqLogics as $eqLogic) {
                        echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true);
                       // echo '<span class="badge pull-right tooltips expertModeVisible" title="ID logique de l\'équipement">'.$eqLogic->getLogicalId().'</span>';
                        echo '</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>

        <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
            <legend>{{Mes équipements Z-Wave}}
                <span style="font-size: 0.7em;color:#c5c5c5">
                    Vous devez être connecté à internet pour voir les prévisualisations
                </span>
            </legend>
            <div class="eqLogicThumbnailContainer">
                <?php
                if (count($eqLogics) == 0) {
                    echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>Vous n'avez pas encore de module Z-Wave. Cliquez sur le bouton inclusion  à gauche et suivez la documentation de votre module pour associer celui-ci à Jeedom</span></center>";
                } else {
                    foreach ($eqLogics as $eqLogic) {
                        echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                        echo "<center>";
                        $urlPath = config::byKey('market::address') . '/market/zwave/images/' . $eqLogic->getConfiguration('device') . '.jpg';
                        $urlPath2 = config::byKey('market::address') . '/market/zwave/images/' . $eqLogic->getConfiguration('device') . '_icon.png';
                        $urlPath3 = config::byKey('market::address') . '/market/zwave/images/' . $eqLogic->getConfiguration('device') . '_icon.jpg';

                        echo '<img class="lazy" src="plugins/zwave/doc/images/zwave_icon.png" data-original3="' . $urlPath3 . '" data-original2="' . $urlPath2 . '" data-original="' . $urlPath . '" height="105" width="95" />';
                        echo "</center>";
                        echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>

        <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
            <div class="row">
                <div class="col-sm-6">
                    <form class="form-horizontal">
                        <fieldset>
                            <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}} <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
                                <div class="col-sm-8">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label" >{{Objet parent}}</label>
                                <div class="col-sm-8">
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
                                <label class="col-sm-4 control-label">{{Catégorie}}</label>
                                <div class="col-sm-8">
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
                                <label class="col-sm-4 control-label">{{Activer}}</label>
                                <div class="col-sm-1">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>
                                </div>
                                <label class="col-sm-4 control-label">{{Visible}}</label>
                                <div class="col-sm-1">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                                </div>
                            </div>
                            <div class="form-group expertModeVisible">
                                <label class="col-sm-4 control-label">{{Node ID}}</label>
                                <div class="col-sm-4">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" />
                                </div>
                            </div>
                            <div class="form-group expertModeVisible">
                                <label class="col-sm-4 control-label">{{Délai maximum autorisé entre 2 messages (min)}}</label>
                                <div class="col-sm-4">
                                    <input class="eqLogicAttr form-control" data-l1key="timeout" />
                                </div>
                            </div>
                            <div class="form-group expertModeVisible">
                                <label class="col-sm-4 control-label">{{Fréquence de rafraîchissement des valeurs (cron)}}</label>
                                <div class="col-sm-4">
                                    <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="refreshDelay"/>
                                </div>
                                <div class="col-sm-1">
                                    <i class="fa fa-question-circle cursor floatright" id="bt_cronGenerator"></i>
                                </div>
                            </div>
                            <div class="form-group expertModeVisible">
                                <label class="col-sm-4 control-label">{{Ne jamais mettre en erreur}}</label>
                                <div class="col-sm-1">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="nerverFail"/>
                                </div>
                                <label class="col-sm-4 control-label">{{Ne pas vérifier la batterie}}</label>
                                <div class="col-sm-1">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="noBatterieCheck"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">{{Commentaire}}</label>
                                <div class="col-sm-8">
                                    <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="commentaire" ></textarea>
                                </div>
                            </div>
                        </fieldset> 
                    </form>
                </div>
                <div class="col-sm-6">
                    <form class="form-horizontal">
                        <fieldset>
                            <legend>{{Informations}}
                                <i id="bt_autoDetectModule" class="fa fa-search expertModeVisible pull-right tooltips cursor" title="{{Detecter automatiquement le modele du module}}"></i>
                                <i id="bt_displayZwaveData" title="{{Voir l'arbre Z-Wave}}" class="fa fa-tree expertModeVisible pull-right tooltips cursor"></i>
                                <i  id="bt_showClass" title="{{Voir les classes Z-Wave}}" class="fa fa-eye expertModeVisible pull-right tooltips cursor"></i>
                            </legend>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{Module}}</label>
                                <div class="col-sm-5">
                                    <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="device">
                                        <option value="">{{Aucun}}</option>
                                        <?php
                                        foreach (zwave::devicesParameters() as $id => $info) {
                                            if (isset($info['name'])) {
                                                echo '<option value="' . $id . '">' . $info['name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-sm-5">
                                    <a class="btn btn-success tooltips" id="bt_getFromMarket" title="{{Récupérer du market}}"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs hidden-sm hidden-md">{{Market}}</span></a>
                                    <a class="btn btn-default" id="bt_configureDevice" title='{{Configurer}}'><i class="fa fa-wrench"></i></a>
                                </div>
                            </div>
                            <div class="form-group expertModeVisible">
                                <label class="col-sm-2 control-label">{{Envoyer une configuration}}</label>
                                <div class="col-sm-5">
                                    <input id="bt_uploadConfZwave" type="file" name="file" data-url="plugins/zwave/core/ajax/zwave.ajax.php?action=uploadConfZwave">
                                </div>
                                <div class="col-sm-5">
                                    <a class="btn btn-success eqLogicAction" data-action="export"><i class="fa fa-cloud-download"></i> <span class="hidden-xs hidden-sm hidden-md">{{Exporter}}</span></a>
                                    <a class="btn btn-warning tooltips" id="bt_shareOnMarket" title="{{Partager}}" ><i class="fa fa-cloud-upload"></i> <span class="hidden-xs hidden-sm hidden-md">{{Partager}}</span></a>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{Marque}}</label>
                                <div class="col-sm-10">
                                    <span class="zwaveInfo tooltips label label-default" data-l1key="brand"></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{Nom}}</label>
                                <div class="col-sm-10">
                                    <span class="zwaveInfo tooltips label label-default" data-l1key="name" style="word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"></span>
                                </div>
                            </div>

                            <div class="form-group expertModeVisible">
                                <label class="col-sm-2 control-label">{{Identifiant Fabricant}}</label>
                                <div class="col-sm-4">
                                    <span class="zwaveInfo tooltips label label-default tooltips" title="{{Identifiant Fabricant}}" data-l1key="manufacturerId"></span>
                                    <span class="zwaveInfo tooltips label label-default tooltips" title="{{Type produit}}" data-l1key="manufacturerProductType"></span>
                                    <span class="zwaveInfo tooltips label label-default tooltips" title="{{Identifiant Produit}}" data-l1key="manufacturerProductId"></span>
                                </div>
                                <label class="col-sm-2 control-label">{{Batterie}}</label>
                                <div class="col-sm-2">
                                    <span class="zwaveInfo tooltips label label-default" data-l1key="battery"></span>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">

                                        <label class="col-sm-4 control-label">{{Interview}}</label>
                                        <div class="col-sm-2">
                                            <a class="btn btn-default" id="bt_showInterview" ><i class="fa fa-eye"></i> <span class="zwaveInfo" data-l1key="interviewComplete"></span></a>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">{{Dernière communication}}</label>
                                        <div class="col-sm-4">
                                            <span class="zwaveInfo tooltips label label-default" data-l1key="lastReceived"></span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-4 control-label">{{Etat}}</label>
                                        <div class="col-sm-4">
                                            <span class="zwaveInfo tooltips label label-default" data-l1key="state"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-1"></div>
                                <div class="col-sm-5">
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
                        <th style="width: 100px;" class="expertModeVisible">{{Classe}}</th>
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
    <?php include_file('desktop', 'zwave', 'js', 'zwave'); ?>
    <?php include_file('core', 'plugin.template', 'js'); ?>
