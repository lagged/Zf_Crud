<?php
/**
 * @category
 * @package
 * @author
 * @license
 * @version
 * @link
 */
class Crud_View_Helper_BetterUrl extends \Zend_View_Helper_Abstract
{
    /**
     * @var string $action, $controller, $module
     */
    protected $action;
    protected $controller;
    protected $module;

    /**
     * @var array $params
     * @see self::parseUri()
     */
    protected $params = array();

    /**
     * @var string $query
     * @see self::parseUri()
     */
    protected $query;

    /**
     * BetterUrl: assumes /module/controller/action
     *
     * @param $options Similar to Zend_View_Helper_Url
     *
     * @return string
     */
    public function BetterUrl(array $options)
    {
        $this->parseUri();

        $link = array();

        if (!isset($options['module'])) {
            $link['module'] = $this->module;
        } else {
            $link['module'] = $options['module'];
            unset($options['module']);
        }

        if (!isset($options['controller'])) {
            $link['controller'] = $this->controller;
        } else {
            $link['controller'] = $options['controller'];
            unset($options['controller']);
        }

        if (!isset($options['action'])) {
            $link['action'] = $this->action;
        } else {
            $link['action'] = $options['action'];
            unset($options['action']);
        }

        $url = sprintf('/%s/%s/%s',
            $link['module'], $link['controller'], $link['action']
        );
        if (is_array($options) && count($options) > 0) {
            foreach ($options as $k=>$v) {

                /**
                 * @desc Overwrite current params from the url with value from $options
                 */
                if (is_array($this->params) && isset($this->params[$k])) {
                    unset($this->params[$k]);
                }

                $url .= '/' . $k . '/' . urlencode($v);
            }
        }

        if ($this->query !== null) {
            $url .= '?' . $this->query;
        }
        return $url;
    }

    /**
     * Parse {@global $_SERVER['REQUEST_URI']}
     *
     * @return void
     */
    protected function parseUri()
    {
        $parts = explode('/', $_SERVER['REQUEST_URI']);
        if (!is_array($parts)) {
            throw new \DomainException("Something is wrong.");
        }
        if (count($parts) < 3) {
            throw new \DomainException("You're using a route, we don't support that.");
        }
        $this->module     = $parts[0];
        $this->controller = $parts[1];
        $this->action     = $parts[2];

        array_splice($parts, 0, 3);

        if (!is_array($parts) || count($parts) == 0) {
            return;
        }
    }

    /**
     * For leftovers after module/controller/action are detected.
     *
     * @param array $parts
     *
     * @return void
     * @uses   self::$query
     * @uses   self::$params
     * @todo   Figure out parsing of the rest.
     */
    protected function parseRest(array $parts)
    {
        $uri = implode('/', $parts);
        if (strstr($uri, '?')) {
            list($uri, $this->query) = explode('?', $uri);
        }

        // this is a bad idea
/*
        $parts = explode('/', $uri);
        foreach ($parts as $part) {

        }
        $this->params = $parts;
*/
    }
}
