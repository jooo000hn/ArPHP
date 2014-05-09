<?php
/**
 * Ar for PHP .
 *
 * @author ycassnr<ycassnr@gmail.com>
 */

/**
 * class Ar.
 */
class Ar {

    static private $_a = array();

    static private $_c = array();

    static private $_config = array();

    static public $autoLoadPath;

    static public function init()
    {
        self::$autoLoadPath = array(
            CORE_PATH,
            FRAME_PATH,
            COMP_PATH,
            COMP_PATH . 'Db' . DS,
            COMP_PATH . 'Url' . DS,
            COMP_PATH . 'Format' . DS,
            COMP_PATH . 'Validator' . DS,
            COMP_PATH . 'Hash' . DS,
            COMP_PATH . 'Rpc' . DS,
            COMP_PATH . 'List' . DS,
            COMP_PATH . 'Cache' . DS
        );

        Ar::c('url.skeleton')->generate(DEFAULT_APP_NAME);

        self::setConfig('', Ar::import(ROOT_PATH . 'Conf' . DS . 'public.config.php'));

        Ar::c('url.route')->parse();

        Ar::import(CORE_PATH . 'alias.func.php');

        self::$_config = array_merge(
                self::$_config,
                Ar::import(CONFIG_PATH . 'default.config.php', true)
            );

        ArApp::run();

    }

    static public function setA($key, $val)
    {
        self::$_a[$key] = $val;

    }

    static public function getConfig($ckey = '', $rt = array())
    {
        if (empty($ckey)) :
            $rt = self::$_config;
        else :
            if (strpos($ckey, '.') === false) :
                if (isset(self::$_config[$ckey])) :
                    $rt = self::$_config[$ckey];
                endif;
            else :
                $cE = explode('.', $ckey);
                $rt = self::$_config;

                while ($k = array_shift($cE)) :
                    if (!isset($rt[$k])) :
                        $rt = null;
                        break;
                    else :
                        $rt = $rt[$k];
                    endif;
                endwhile;
            endif;

        endif;

        return $rt;

    }

    static public function setConfig($ckey = '', $value = array())
    {
        if (!empty($ckey))
            self::$_config[$ckey] = $value;
        else
            self::$_config = $value;

    }

    static public function a($akey)
    {
        return isset(self::$_a[$akey]) ? self::$_a[$akey] : null;

    }

    static public function c($cname)
    {
        $cKey = strtolower($cname);

        if (!isset(self::$_c[$cKey])) :

            $config = self::getConfig('components.' . $cKey . '.config');
            self::setC($cKey, $config);

        endif;

        return self::$_c[$cKey];

    }

    static public function setC($component, $config = array())
    {
        $cKey = strtolower($component);

        if (isset(self::$_c[$cKey]))
            return false;

        $cArr = explode('.', $component);

        array_unshift($cArr, 'components');

        $cArr = array_map('ucfirst', $cArr);

        $className = 'Ar' . array_pop($cArr);

        $cArr[] = $className;

        $classFile = implode($cArr, '\\');

        self::$_c[$cKey] = call_user_func_array("$className::init", array($config, $className));

    }

    static public function autoLoader($class)
    {
        $class = str_replace('\\', DS, $class);

        $m = self::getConfig('requestRoute');

        if (!empty($m['m'])) :
            $appMoudle = ROOT_PATH . $m['m'] . DS;
            array_push(self::$autoLoadPath, $appMoudle);

            $appConfigFile = $appMoudle . 'Conf' . DS . 'app.config.php';
            $appConfig = self::import($appConfigFile, true);

            if (is_array($appConfig))
                self::setConfig('', array_merge(self::getConfig(), $appConfig));

            if (preg_match("#[A-Z]{1}[a-z0-9]+$#", $class, $match)) :
                $appEnginePath = $appMoudle . $match[0] . DS;

                $extPath = $appMoudle . 'Ext' . DS;

                array_push(self::$autoLoadPath, $appEnginePath, $extPath);
            endif;

        endif;

        foreach (self::$autoLoadPath as $path) :
            $classFile = $path . $class . '.class.php';
            if (is_file($classFile)) :
                require_once $classFile;
                $rt = true;
                break;
            endif;
        endforeach;

        if (empty($rt))
            throw new ArException('class : ' . $class . ' does not exist !');

    }
    static public function importPath($path)
    {
        array_push(self::$autoLoadPath, rtrim($path, DS) . DS);

    }

    static public function import($path, $allowTry = false)
    {
        if (strpos($path, DS) === false)
            $fileName = str_replace(array('c.', 'ext.', 'app.', '.'), array('Controller.', 'Extensions.', rtrim(ROOT_PATH, DS) . '.', DS), $path) . '.class.php';
        else
            $fileName = $path;

        if (is_file($fileName)) :
            $file = require_once($fileName);
            if ($file === true) :
                return array();
            else :
                return $file;
            endif;
        else :
            if ($allowTry)
                return array();
            else
                throw new ArException('import not found file :' . $fileName);
        endif;

    }

    static public function createUrl($url = '', $params = array())
    {
        $prefix = rtrim(SERVER_PATH . (arCfg('requestRoute.m') == DEFAULT_APP_NAME ? '' : arCfg('requestRoute.m')), '/');

        $url = ltrim($url, '/');

        if (empty($url)) :
            $url = $prefix;

            $url .= '/' . arCfg('requestRoute.c') . '/' . arCfg('requestRoute.a');

        else :
            if (strpos($url, '/') === false) :
                $url = $prefix . '/' . arCfg('requestRoute.c') . '/' . $url;
            else :
                $url = $prefix . '/' . $url;
            endif;

        endif;

        foreach ($params as $pkey => $pvalue) :
            $url .= '/' . $pkey . '/' . $pvalue;
        endforeach;

        return $url;

    }

    static public function exceptionHandler($e)
    {
        echo get_class($e) . ' : ' . $e->getMessage();

    }

    static public function errorHandler($errno, $errstr)
    {
        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";

    }

}
