<?php

if(!function_exists('akelos_autoload')){

    defined('DS')               || define('DS', DIRECTORY_SEPARATOR);
    defined('SINTAGS_BASE_DIR') || define('SINTAGS_BASE_DIR', dirname(__FILE__));
    defined('AK_CONTRIB_DIR')   || define('AK_CONTRIB_DIR', SINTAGS_BASE_DIR.DS.'vendor');
    defined('AK_CONFIG_DIR')    || define('AK_CONFIG_DIR', AK_CONTRIB_DIR.DS.'akelos'.DS.'active_support'.DS.'config');
    defined('AK_ENVIRONMENT')   || define('AK_ENVIRONMENT', 'development');
    defined('AK_CLI')           || define('AK_CLI', php_sapi_name() == 'cli');
    defined('AK_WEB_REQUEST')   || define('AK_WEB_REQUEST', !empty($_SERVER['REQUEST_URI']));
    defined('AK_CAN_FORK')      || define('AK_CAN_FORK', function_exists('pcntl_fork'));
    defined('AK_FRAMEWORK_LANGUAGE')        || define('AK_FRAMEWORK_LANGUAGE', 'en');
    defined('AK_AVAILABLE_ENVIRONMENTS')    || define('AK_AVAILABLE_ENVIRONMENTS','setup,testing,development,production,staging');
    defined('AK_AUTOMATICALLY_UPDATE_LANGUAGE_FILES') || define('AK_AUTOMATICALLY_UPDATE_LANGUAGE_FILES', false);
    
    function sintags_autoload($name, $path = null) {
        static $paths = array(), $lib_paths = array(), $app_paths = array();
        if (!empty($path)){
            $paths[$name] = $path;
            return ;
        }
        if(isset($paths[$name])){
            include $paths[$name];
        }
    }

    sintags_autoload('AkSintags',       SINTAGS_BASE_DIR.DS.'sintags'.DS.'base.php');
    sintags_autoload('AkSintagsParser', SINTAGS_BASE_DIR.DS.'sintags'.DS.'parser.php');
    sintags_autoload('AkSintagsLexer',  SINTAGS_BASE_DIR.DS.'sintags'.DS.'lexer.php');
    sintags_autoload('Ak',              AK_CONTRIB_DIR.DS.'akelos'.DS.'active_support'.DS.'base.php');
    sintags_autoload('AkInflector',     AK_CONTRIB_DIR.DS.'akelos'.DS.'active_support'.DS.'text'.DS.'inflector.php');
    sintags_autoload('AkConfig',        AK_CONTRIB_DIR.DS.'akelos'.DS.'active_support'.DS.'config'.DS.'base.php');
    sintags_autoload('AkLexer',         AK_CONTRIB_DIR.DS.'akelos'.DS.'active_support'.DS.'text'.DS.'lexer.php');
    sintags_autoload('AkRouterHelper',  AK_CONTRIB_DIR.DS.'akelos'.DS.'action_pack'.DS.'router'.DS.'router_helper.php');
    sintags_autoload('AkRoute',         AK_CONTRIB_DIR.DS.'akelos'.DS.'action_pack'.DS.'router'.DS.'route.php');

    spl_autoload_register('sintags_autoload');
}

