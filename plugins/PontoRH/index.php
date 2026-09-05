<?php

defined('PLUGINPATH') or exit('No direct script access allowed');
/*
 Plugin Name: Ponto RH
 Description: Monitor de ponto integrado à Sólides para DP e funcionários.
 Version: 0.3.0
 Requires at least: 3.9.0
 Author: Internal
*/
require_once __DIR__.'/Helpers/pontorh_helper.php';
require_once __DIR__.'/Plugin.php';
$pontorh_language=get_setting('language')?:'english';
$pontorh_language_file=__DIR__.'/Language/'.$pontorh_language.'/default_lang.php';
if(file_exists($pontorh_language_file))require_once $pontorh_language_file;elseif(file_exists(__DIR__.'/Language/english/default_lang.php'))require_once __DIR__.'/Language/english/default_lang.php';
\PontoRH\Plugin::register();

// Substitui o menu antigo de registro/tratamento/fechamento por uma camada de monitoramento.
app_hooks()->add_filter('app_filter_staff_left_menu',function($menu){
    if(isset($menu['pontorh'])){
        $menu['pontorh']['name']='Ponto · Sólides';
        $menu['pontorh']['url']='pontorh';
        $menu['pontorh']['class']='clock';
        $menu['pontorh']['submenu']=array('pontorh_solides'=>array('name'=>'Monitor Sólides','url'=>'pontorh','class'=>'activity'));
        $menu['pontorh']['sub_pages']=array('pontorh/index','pontorh/solides');
    }
    return $menu;
});

if(file_exists(__DIR__.'/Config/Routes.php'))require_once __DIR__.'/Config/Routes.php';
// WorkflowRoutes legado não é carregado: ajustes, marcações e fechamento permanecem exclusivamente na Sólides.
register_installation_hook('PontoRH',function(){require_once __DIR__.'/install.php';\PontoRH\install\pontorh_install();});
register_activation_hook('PontoRH',function(){\PontoRH\Plugin::runMigrations();});
register_update_hook('PontoRH',function(){require_once __DIR__.'/install.php';\PontoRH\install\pontorh_install();\PontoRH\Plugin::runMigrations();});
register_deactivation_hook('PontoRH',function(){return true;});
register_uninstallation_hook('PontoRH',function(){require_once __DIR__.'/uninstall.php';\PontoRH\install\pontorh_uninstall();});
