<?php

namespace Jaxon\Zend\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Jaxon extends AbstractPlugin
{
    use \Jaxon\Framework\JaxonTrait;

    /**
     * Create a new Jaxon instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Initialize the properties inherited from JaxonTrait.
        $this->jaxon = jaxon();
        $this->response = new \Jaxon\Zend\Response();
        $this->view = new \Jaxon\Zend\View();
    }

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Setup the Jaxon module.
     *
     * @return void
     */
    public function setup()
    {
        // This function should be called only once
        if(($this->setupCalled))
        {
            return;
        }
        $this->setupCalled = true;

        $debug = (getenv('APP_ENV') != 'production');
        $appPath = rtrim(getcwd(), '/');
        $baseUrl = $_SERVER['SERVER_NAME'];
        $baseDir = $_SERVER['DOCUMENT_ROOT'];

        // Use the Composer autoloader
        $this->jaxon->useComposerAutoloader();
        // Jaxon library default options
        $this->jaxon->setOptions(array(
            'js.app.extern' => !$debug,
            'js.app.minify' => !$debug,
            'js.app.uri' => '//' . $baseUrl . '/jaxon/js',
            'js.app.dir' => $baseDir . '/jaxon/js',
        ));
        // Jaxon library settings
        $config = $this->jaxon->readConfigFile($appPath . '/config/jaxon.config.php', 'lib');

        // Jaxon application settings
        $appConfig = array();
        if(array_key_exists('app', $config) && is_array($config['app']))
        {
            $appConfig = $config['app'];
        }
        $controllerDir = (array_key_exists('dir', $appConfig) ? $appConfig['dir'] : $appPath . '/jaxon');
        $namespace = (array_key_exists('namespace', $appConfig) ? $appConfig['namespace'] : '\\Jaxon\\App');
        $excluded = (array_key_exists('excluded', $appConfig) ? $appConfig['excluded'] : array());
        // The public methods of the Controller base class must not be exported to javascript
        $controllerClass = new \ReflectionClass('\\Jaxon\\Zend\\Controller');
        foreach ($controllerClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $xMethod)
        {
            $excluded[] = $xMethod->getShortName();
        }

        // Set the request URI
        if(!$this->jaxon->getOption('core.request.uri'))
        {
            $this->jaxon->setOption('core.request.uri', 'jaxon');
        }
        // Register the default Jaxon class directory
        $this->jaxon->addClassDir($controllerDir, $namespace, $excluded);
    }
}
