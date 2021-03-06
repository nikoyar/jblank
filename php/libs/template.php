<?php
/**
 * J!Blank Template for Joomla by JBlank.pro (JBZoo.com)
 *
 * @package    JBlank
 * @author     SmetDenis <admin@jbzoo.com>
 * @copyright  Copyright (c) JBlank.pro
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL
 * @link       http://jblank.pro/ JBlank project page
 */

defined('_JEXEC') or die;


// load joomla libs
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

// load own libs
$tmplPath = dirname(__FILE__);
require_once $tmplPath . '/css.php';
require_once $tmplPath . '/css.less.leafo.php';
require_once $tmplPath . '/css.less.gpeasy.php';
require_once $tmplPath . '/css.scss.leafo.php';
require_once $tmplPath . '/class.mobiledetect.php';

/**
 * Class JBlankTemplate
 */
class JBlankTemplate
{
    /**
     * @var JDocumentHTML
     */
    public $doc = null;

    /**
     * @var Joomla\Registry\Registry
     */
    public $config = null;

    /**
     * @var JUri
     */
    public $url;

    /**
     * @var JApplicationSite
     */
    public $app;

    /**
     * @var JMenuSite
     */
    public $menu;

    /**
     * @var Joomla\Registry\Registry
     */
    public $params;

    /**
     * @var Joomla\Registry\Registry
     */
    public $request;

    /**
     * @var JUser
     */
    public $user;

    /**
     * @var JBlankMobileDetect
     */
    public $mobile;

    /**
     * @var string
     */
    public $dir;
    public $baseurl;
    public $path;
    public $pathFull;
    public $img;
    public $imgFull;
    public $less;
    public $lessFull;
    public $scss;
    public $scssFull;
    public $css;
    public $cssFull;
    public $js;
    public $jsFull;
    public $lang;
    public $langDef;

    /**
     * Create and get instance
     * @return JBlankTemplate
     */
    public static function getInstance()
    {
        static $instance;

        if (!isset($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Init internal vars
     */
    private function __construct()
    {
        // get links to global vars
        $this->doc     = JFactory::getDocument();
        $this->config  = JFactory::getConfig();
        $this->url     = JUri::getInstance();
        $this->app     = JFactory::getApplication();
        $this->menu    = $this->app->getMenu();
        $this->params  = $this->app->getTemplate(true)->params;
        $this->user    = JFactory::getUser();
        $this->baseurl = $this->_getBaseUrl();

        // relative paths
        $this->path = $this->_getTemplatePath();
        $this->img  = $this->path . '/images';
        $this->less = $this->path . '/less';
        $this->scss = $this->path . '/scss';
        $this->css  = $this->path . '/css';
        $this->js   = $this->path . '/js';

        // absolute paths
        $this->pathFull = $this->_getTemplatePathFull();
        $this->imgFull  = JPath::clean($this->pathFull . '/images');
        $this->cssFull  = JPath::clean($this->pathFull . '/css');
        $this->lessFull = JPath::clean($this->pathFull . '/less');
        $this->scssFull = JPath::clean($this->pathFull . '/scss');
        $this->jsFull   = JPath::clean($this->pathFull . '/js');
        $this->partial  = JPath::clean($this->pathFull . '/partial');

        // init template vars
        $this->lang    = $this->_getLangCurrent();
        $this->langDef = $this->_getLangDefault();
        $this->request = $this->_getRequest();
        $this->dir     = $this->doc->getDirection();

        // init mobile detect
        $this->mobile = $this->_getMobile();
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return defined('JDEBUG') && JDEBUG;
    }

    /**
     * Get var from request
     * @param $key
     * @param null $default
     * @param string $filter
     * @return mixed
     */
    public function request($key, $default = null, $filter = 'cmd')
    {
        $jInput = JFactory::getApplication()->input;
        return $jInput->get($key, $default, $filter);
    }

    /**
     * @param $filename
     * @param string $prefix
     * @param string $type
     * @return $this
     */
    public function css($filename, $type = 'all', $prefix = '')
    {
        if (is_array($filename)) {
            foreach ($filename as $file) {
                $this->css($file, $type, $prefix);
            }

        } else if ($filename) {

            $ext    = pathinfo($filename, PATHINFO_EXTENSION);
            $prefix = (!empty($prefix) ? $prefix . '_' : '');

            if ($ext == 'css') {

                $path = JPath::clean($this->cssFull . '/' . $prefix . $filename);
                if ($mtime = $this->_checkFile($path)) {
                    $cssPath = $this->css . '/' . $prefix . $filename . '?' . $mtime;
                    $this->doc->addStylesheet($cssPath, 'text/css', $type);
                    return $this;
                }

                $path = JPath::clean(JPATH_ROOT . '/' . $filename);
                if ($mtime = $this->_checkFile($path)) {
                    $cssPath = rtrim($this->baseurl, '/') . '/' . ltrim($filename, '/') . '?' . $mtime;
                    $this->doc->addStylesheet($cssPath, 'text/css', $type);
                    return $this;
                }

            } else if ($ext == 'less') {

                $lessMode = $this->params->get('less_processor', 'leafo');

                $path = JPath::clean($this->lessFull . '/' . $prefix . $filename);
                if ($this->_checkFile($path)) {
                    if ($cssPath = JBlankCss::getProcessor('less.' . $lessMode, $this)->compile($path)) {
                        $this->doc->addStylesheet($cssPath, 'text/css', $type);
                    }
                }

            } else if ($ext == 'scss') {

                $path = JPath::clean($this->scssFull . '/' . $prefix . $filename);
                if ($this->_checkFile($path)) {
                    if ($cssPath = JBlankCss::getProcessor('scss.leafo', $this)->compile($path)) {
                        $this->doc->addStylesheet($cssPath, 'text/css', $type);
                    }
                }
            }

        }

        return $this;
    }

    /**
     * @param $filename
     * @param string $prefix
     * @param bool $defer
     * @param bool $async
     * @return $this
     */
    public function js($filename, $prefix = '', $defer = false, $async = false)
    {
        if (is_array($filename)) {
            foreach ($filename as $file) {
                $this->js($file, $prefix, $defer, $async);
            }

        } else if ($filename) {

            $prefix = (!empty($prefix) ? $prefix . '_' : '');
            $path   = JPath::clean($this->jsFull . '/' . $prefix . $filename);

            if ($mtime = $this->_checkFile($path)) {
                $filePath = $this->js . '/' . $prefix . $filename . '?' . $mtime;
                $this->doc->addScript($filePath, "text/javascript", $defer, $async);
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getBodyClasses()
    {
        return implode(' ', array(
            'itemid-' . $this->request->get('Itemid', 0),
            'lang-' . $this->request->get('lang', $this->langDef),
            'com-' . str_replace('com_', '', $this->request->get('option')),
            'view-' . $this->request->get('view', 'none'),
            'layout-' . $this->request->get('layout', 'none'),
            'task-' . $this->request->get('task', 'none'),
            'zoo-itemid-' . $this->request->get('item_id', 0),
            'zoo-categoryid-' . $this->request->get('category_id', 0),
            'device-ios-' . ($this->isiOS() ? 'yes' : 'no'),
            'device-android-' . ($this->isAndroidOS() ? 'yes' : 'no'),
            'device-mobile-' . ($this->isMobile() ? 'yes' : 'no'),
            'device-table-' . ($this->isTablet() ? 'yes' : 'no'),
        ));
    }

    /**
     * @return string
     */
    public function renderHTML()
    {
        $html = array(
            '<!doctype html>',

            '<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7 ie6" '
            . 'lang="' . $this->lang . '" dir="' . $this->dir . '"> <![endif]-->',

            '<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8 ie7" '
            . 'lang="' . $this->lang . '" dir="' . $this->dir . '"> <![endif]-->',

            '<!--[if IE 8]><html class="no-js lt-ie9 ie8" '
            . 'lang="' . $this->lang . '" dir="' . $this->dir . '"> <![endif]-->',

            '<!--[if gt IE 8]><!--><html class="no-js" xmlns="http://www.w3.org/1999/xhtml" '
            . 'lang="' . $this->lang . '" dir="' . $this->dir . '" '
            . 'prefix="og: http://ogp.me/ns#" '
            // . 'prefix="ya: http://webmaster.yandex.ru/vocabularies/" '
            . '> <!--<![endif]-->',
        );

        return implode(" \n", $html) . "\n";
    }

    /**
     * Manual head render
     * @return string
     */
    public function renderHead()
    {
        $document = $this->doc;
        $docData  = $document->getHeadData();
        $html     = array();

        $isHtml5 = method_exists($this->doc, 'isHtml5') && $this->doc->isHtml5();

        // Generate charset when using HTML5 (should happen first)
        if ($isHtml5) {
            $html[] = '<meta charset="' . $document->getCharset() . '" />';
        }

        // Generate base tag (need to happen early)
        $base = $document->getBase();
        if (!empty($base)) {
            $html[] = '<base href="' . $document->getBase() . '" />';
        }

        // Generate META tags (needs to happen as early as possible in the head)
        foreach ($docData['metaTags'] as $type => $tag) {
            foreach ($tag as $name => $content) {
                if ($type == 'http-equiv' && !($isHtml5 && $name == 'content-type')) {
                    $html[] = '<meta http-equiv="' . $name . '" content="' . htmlspecialchars($content) . '" />';
                } elseif ($type == 'standard' && !empty($content)) {
                    $html[] = '<meta name="' . $name . '" content="' . htmlspecialchars($content) . '" />';
                }
            }
        }

        if ($docData['description']) {
            $html[] = '<meta name="description" content="' . htmlspecialchars($docData['description']) . '" />';
        }

        if ($generator = $document->getGenerator()) {
            $html[] = '<meta name="generator" content="' . htmlspecialchars($generator) . '" />';
        }

        $html[] = '<title>' . htmlspecialchars($docData['title'], ENT_COMPAT, 'UTF-8') . '</title>';

        // Generate stylesheet links
        foreach ($docData['styleSheets'] as $strSrc => $strAttr) {
            $tag = '<link rel="stylesheet" href="' . $strSrc . '"';

            if (!is_null($strAttr['mime']) && (!$isHtml5 || $strAttr['mime'] != 'text/css')) {
                $tag .= ' type="' . $strAttr['mime'] . '"';
            }

            if (!is_null($strAttr['media'])) {
                $tag .= ' media="' . $strAttr['media'] . '"';
            }

            if ($temp = JArrayHelper::toString($strAttr['attribs'])) {
                $tag .= ' ' . $temp;
            }

            $tag .= ' />';

            $html[] = $tag;
        }

        // Generate script file links
        foreach ($docData['scripts'] as $strSrc => $strAttr) {
            $tag = '<script src="' . $strSrc . '"';

            $defaultMimes = array('text/javascript', 'application/javascript', 'text/x-javascript', 'application/x-javascript');

            if (!is_null($strAttr['mime']) && (!$isHtml5 || !in_array($strAttr['mime'], $defaultMimes))) {
                $tag .= ' type="' . $strAttr['mime'] . '"';
            }

            if ($strAttr['defer']) {
                $tag .= ' defer="defer"';
            }

            if ($strAttr['async']) {
                $tag .= ' async="async"';
            }

            $tag .= '></script>';
            $html[] = $tag;
        }

        // add custom
        foreach ($docData['custom'] as $custom) {
            $html[] = $custom;
        }

        return implode("\n  ", $html) . "\n\n";
    }

    /**
     * @param string|array $metaRows
     * @return $this
     */
    public function meta($metaRows)
    {
        if (is_array($metaRows)) {
            foreach ($metaRows as $metaRow) {
                $this->meta($metaRow);
            }
        } else {
            $data = $this->doc->getHeadData();
            if (!in_array($metaRows, $data['custom'])) {
                $this->doc->addCustomTag($metaRows);
            }
        }

        return $this;
    }

    /**
     * Get relative template path (for browser)
     * @return string
     */
    protected function _getTemplatePath()
    {
        $path = pathinfo($this->_getTemplatePathFull(), PATHINFO_BASENAME);
        return rtrim($this->baseurl, '/') . '/templates/' . $path;
    }

    /**
     * Get absolute template path (filesystem)
     * @return string
     */
    protected function _getTemplatePathFull()
    {
        $path = rtrim(realpath(__DIR__ . '/../../'), '/'); // TODO Remove template path hack
        //$path = JPath::clean(JPATH_THEMES . '/jblank'); // hardcode fix
        //$path = JPath::clean(JPATH_THEMES . '/' . $this->doc->template); // bug in Joomla on Error page
        return $path;
    }

    /**
     * Get site language
     * @return string
     */
    protected function _getLangDefault()
    {
        $lang = explode('-', JFactory::getLanguage()->getDefault());
        return $lang[0];
    }

    /**
     * Get current site language
     * @return string
     */
    protected function _getLangCurrent()
    {
        $lang = explode('-', JFactory::getLanguage()->getTag());
        return $lang[0];
    }

    /**
     * Get vars from request
     * @return stdClass
     */
    protected function _getRequest()
    {
        $data = array(
            'option' => $this->request('option'),
            'view'   => $this->request('view'),
            'layout' => $this->request('layout'),
            'tmpl'   => $this->request('tmpl', 'index'),
            'lang'   => $this->request('lang', $this->langDef),
            'Itemid' => $this->request('Itemid', 0, 'int'),
        );

        if (class_exists('Joomla\Registry\Registry')) {
            $request = new Joomla\Registry\Registry();
            $request->loadArray($data);

        } else if (class_exists('JRegistry')) { // is depricated since J!3
            $request = new JRegistry();
            $request->loadArray($data);

        } else {
            $request = (object)$data;
        }

        return $request;
    }

    /**
     * Check file exists and return last modified
     * @param $path
     * @return int|null
     */
    protected function _checkFile($path)
    {
        $path = JPath::clean($path);
        if (JFile::exists($path) && filesize($path) > 5) {
            $mdate = substr(filemtime($path), -3);
            return $mdate;
        }

        return null;
    }

    /**
     * @return string
     */
    protected function _getBaseUrl()
    {
        if (0) { // experimental
            $root = JUri::root();
            $juri = new JUri($root);
            return '//' . $juri->toString(array('host', 'port', 'path'));
        }

        return JUri::root();
    }

    /**
     * @return JBlankMobileDetect
     */
    protected function _getMobile()
    {
        return new JBlankMobileDetect();
    }

    /**
     * Set new generator in meta
     * @param string|null $newGenerator
     * @return $this
     */
    public function generator($newGenerator = null)
    {
        $this->doc->setGenerator($newGenerator);
        return $this;
    }

    /**
     * Set html5 mode
     * @param bool $state
     * @return $this
     */
    public function html5($state)
    {
        if (method_exists($this->doc, 'setHtml5')) {
            $this->doc->setHtml5((int)$state);
        }

        return $this;
    }

    /**
     * @param array $patterns
     * @return $this
     */
    public function excludeCSS(array $patterns)
    {
        $this->_excludeAssets(array('styleSheets' => $patterns));
        return $this;
    }

    /**
     * @param array $patterns
     * @return $this
     */
    public function excludeJS(array $patterns)
    {
        $this->_excludeAssets(array('scripts' => $patterns));
        return $this;
    }

    /**
     * Cleanup system links from Joomla, Zoo, JBZoo
     */
    protected function _excludeAssets(array $allPatterns)
    {
        $data = $this->doc->getHeadData();

        foreach ($allPatterns as $type => $patterns) {
            foreach ($data[$type] as $path => $meta) {

                $found = false;
                foreach ($patterns as $pattern) {
                    if (preg_match('#' . $pattern . '#iu', $path)) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    unset($data[$type][$path]);
                }
            }
        }

        $this->doc->setHeadData($data);
    }

    /**
     * Are there any errors on this page?
     * @return bool
     */
    public function isError()
    {
        $buffer = $this->doc->getBuffer('message');

        if (is_array($buffer)) {
            $bufferWords = JString::trim(strip_tags(current($buffer['message'])));
        } else {
            $bufferWords = JString::trim(strip_tags($buffer));
        }

        return !empty($bufferWords);
    }

    /**
     * Check is current device is mobile
     * @return bool
     */
    public function isMobile()
    {
        return $this->mobile->isMobile() && !$this->mobile->isTablet();
    }

    /**
     * Check is current device is tablet
     * @return bool
     */
    public function isTablet()
    {
        return $this->mobile->isTablet();
    }

    /**
     * Check is current device is iOS
     * @return bool
     */
    public function isiOS()
    {
        return $this->mobile->isiOS();
    }

    /**
     * Check is current device is Android OS
     * @return bool
     */
    public function isAndroidOS()
    {
        return $this->mobile->isAndroidOS();
    }

    /**
     * Attention! Function chanage template contect.
     * It means that $this will be instance of JBlankTemplate
     *
     * @param $name
     * @param array $args
     * @return string
     */
    public function partial($name, array $args = array())
    {
        $file = preg_replace('/[^A-Z0-9_\.-]/i', '', $name);

        $args['tpl']   = $this;
        $args['_this'] = $this->doc; // experimental

        // load the partial
        $__file = $this->partial . '/' . $file;

        // render the partial
        if (JFile::exists($__file)) {

            // import vars and get content
            extract($args);
            ob_start();
            include($__file);
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }

        return null;
    }

    /**
     * Simple checking type of current page
     * @return bool
     */
    public function isFront()
    {
        $defId = $this->menu->getDefault()->id;
        $curId = 0;

        $active = $this->menu->getActive();
        if ($active && $active->id) {
            $curId = $active->id;
        }

        return $defId == $curId;
    }

}
