<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use Cake\Core\App;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\View\View;
use Cake\View\Exception\MissingViewException;


/**
 * Provides an API for iteratively building a view up.
 *
 * Once you have configured the view and established all the context
 * you can create a view instance with `build()`.
 */
class ViewBuilder
{
    /**
     * The subdirectory to the template.
     *
     * @var string
     */
    protected $_templatePath;

    /**
     * The template file to render.
     *
     * @var string
     */
    protected $_template;

    /**
     * The plugin name to use.
     *
     * @var string
     */
    protected $_plugin;

    /**
     * The theme name to use.
     *
     * @var string
     */
    protected $_theme;

    /**
     * The layout name to render.
     *
     * @var string
     */
    protected $_layout;

    /**
     * Whether or not autoLayout should be enabled.
     *
     * @var bool
     */
    protected $_autoLayout;

    /**
     * The layout path to build the view with.
     *
     * @var string
     */
    protected $_layoutPath;

    /**
     * The view variables to use
     *
     * @var string
     */
    protected $_name;

    /**
     * The view variables to use
     *
     * @var string
     */
    protected $_className;

    /**
     * The view variables to use
     *
     * @var array
     */
    protected $_options = [];

    /**
     * The helpers to use
     *
     * @var array
     */
    protected $_helpers = [];

    /**
     * Get/set path for template files.
     *
     * @param string|null $path Path for view files. If null returns current path.
     * @return string|$this
     */
    public function templatePath($path = null)
    {
        if ($path === null) {
            return $this->_templatePath;
        }

        $this->_templatePath = $path;
        return $this;
    }

    /**
     * Get/set path for layout files.
     *
     * @param string|null $path Path for layout files. If null returns current path.
     * @return string|$this
     */
    public function layoutPath($path = null)
    {
        if ($path === null) {
            return $this->_layoutPath;
        }

        $this->_layoutPath = $path;
        return $this;
    }

    /**
     * Turns on or off CakePHP's conventional mode of applying layout files.
     * On by default. Setting to off means that layouts will not be
     * automatically applied to rendered views.
     *
     * @param bool|null $autoLayout Boolean to turn on/off. If null returns current value.
     * @return bool|$this
     */
    public function autoLayout($autoLayout = null)
    {
        if ($autoLayout === null) {
            return $this->_autoLayout;
        }

        $this->_autoLayout = (bool)$autoLayout;
        return $this;
    }

    /**
     * The plugin name to use
     *
     * @param string|null $name Plugin name. If null returns current plugin.
     * @return string|$this
     */
    public function plugin($name = null)
    {
        if ($name === null) {
            return $this->_plugin;
        }

        $this->_plugin = $name;
        return $this;
    }

    /**
     * The helpers to use
     *
     * @param array|null $helpers Helpers to use.
     * @param bool $merge Whether or not to merge existing data with the new data.
     * @return array|$this
     */
    public function helpers(array $helpers = null, $merge = true)
    {
        if ($helpers === null) {
            return $this->_helpers;
        }
        if ($merge) {
            $helpers = array_merge($this->_helpers, $helpers);
        }
        $this->_helpers = $helpers;
        return $this;
    }

    /**
     * The view theme to use.
     *
     * @param string|null $theme Theme name. If null returns current theme.
     * @return string|$this
     */
    public function theme($theme = null)
    {
        if ($theme === null) {
            return $this->_theme;
        }

        $this->_theme = $theme;
        return $this;
    }

    /**
     * Get/set the name of the view file to render. The name specified is the
     * filename in /app/Template/<SubFolder> without the .ctp extension.
     *
     * @param string|null $name View file name to set. If null returns current name.
     * @return string|$this
     */
    public function template($name = null)
    {
        if ($name === null) {
            return $this->_template;
        }

        $this->_template = $name;
        return $this;
    }

    /**
     * Get/set the name of the layout file to render the view inside of.
     * The name specified is the filename of the layout in /app/Template/Layout
     * without the .ctp extension.
     *
     * @param string|null $name Layout file name to set. If null returns current name.
     * @return string|$this
     */
    public function layout($name = null)
    {
        if ($name === null) {
            return $this->_layout;
        }

        $this->_layout = $name;
        return $this;
    }

    /**
     * Set additional options for the view.
     *
     * @param array|null $options Either an array of options or null to get current options.
     * @param bool $merge Whether or not to merge existing data with the new data.
     * @return array|$this
     */
    public function options(array $options = null, $merge = true)
    {
        if ($options === null) {
            return $this->_options;
        }
        if ($merge) {
            $options = array_merge($this->_options, $options);
        }
        $this->_options = $options;
        return $this;
    }

    /**
     * Get/set the view name
     *
     * @param string|null $name The name of the view
     * @return array|$this
     */
    public function name($name = null)
    {
        if ($name === null) {
            return $this->_name;
        }
        $this->_name = $name;
        return $this;
    }

    /**
     * Get/set the view classname
     *
     * @param string|null $name The class name for the view. Can
     *   be a plugin.class name reference, a short alias, or a fully
     *   namespaced name.
     * @return array|$this
     */
    public function className($name = null)
    {
        if ($name === null) {
            return $this->_className;
        }
        $this->_className = $name;
        return $this;
    }

    /**
     * Using the data in the builder, create a view instance.
     *
     * @param array $vars The view variables/context to use.
     * @param \Cake\Network\Request $request The request to use.
     * @param \Cake\Network\Response $response The response to use.
     * @param \Cake\Event\EventManager $events The event manager to use.
     * @return \Cake\View\View
     * @throws \Cake\View\Exception\MissingViewException
     */
    public function build($vars = [], Request $request = null, Response $response = null, EventManager $events = null)
    {
        if ($this->_className === 'View') {
            $className = App::className($this->_className, 'View');
        } else {
            $className = App::className($this->_className, 'View', 'View');
        }
        if (!$className) {
            throw new MissingViewException([$this->_className]);
        }
        $data = [
            'name' => $this->_name,
            'viewPath' => $this->_templatePath,
            'view' => $this->_template,
            'plugin' => $this->_plugin,
            'theme' => $this->_theme,
            'layout' => $this->_layout,
            'autoLayout' => $this->_autoLayout,
            'layoutPath' => $this->_layoutPath,
            'helpers' => $this->_helpers,
            'viewVars' => $vars,
        ];
        $data += $this->_options;
        return new $className($request, $response, $events, $data);
    }
}
