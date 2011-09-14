<?php
/**
 * @category Management
 * @package  Lagged\Zf\Crud
 * @author   Till Klampaeckel <till@lagged.biz>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  GIT: $Id$
 * @link     http://lagged.biz
 */

namespace Lagged\Zf\Crud;

/**
 * @category Management
 * @package  Lagged\Zf\Crud
 * @author   Till Klampaeckel <till@lagged.biz>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_release@
 * @link     http://lagged.biz
 */
abstract class Controller extends \Zend_Controller_Action
{
    /**
     * @var array $cols The columns in the table.
     * @see self::init()
     */
    protected $cols;

    /**
     * @var Zend_Config $db
     */
    protected $dbConfig = null;

    /**
     * @var string $model
     */
    protected $model;

    /**
     * @var Zend_Db_Table_Abstract
     * @see self::init()
     */
    protected $obj;

    /**
     * @var string $title For the view.
     */
    protected $title = 'CRUD INTERFACE';

    /**
     * Init
     *
     * @return void
     * @uses   self::$model
     * @uses   self::$dbConfig
     * @uses   Zend_View
     */
    public function init()
    {
        if (empty($this->model)) {
            throw new \RuntimeException("You need to define self::$model");
        }

        $this->obj = new $this->model(array('db' => $this->dbConfig));
        if (!($this->obj instanceof \Zend_Db_Table_Abstract)) {
            throw new \LogicException("The model must extend Zend_Db_Table_Abstract");
        }
        $this->view->headLink()->appendStylesheet(
            'http://twitter.github.com/bootstrap/assets/css/bootstrap-1.2.0.min.css'
        );
        $this->view->assign('ui_title', $this->title);
        $this->view->addScriptPath(dirname(__DIR__) . '/views/scripts/crud');

        $this->cols = $this->obj->info(\Zend_Db_Table_Abstract::METADATA);
        $this->view->assign('cols', $this->cols);
    }

    public function createAction()
    {
        // create form from table
    }

    public function deleteAction()
    {
        $id = $this->_getParam('id');
        if ($id === null) {
            throw new \InvalidArgumentException("ID is not set.");
        }
        if ($this->_request->isGet() === true) {
            $this->view->assign('record', $this->obj->find($id));
            return $this->render('delete');
        }
    }

    public function indexAction()
    {
        return $this->_helper->redirector('read');
    }

    public function readAction()
    {
        //$this->_helper->viewRenderer->setScriptController('crud');

        $id = $this->_getParam('id');
        if ($id === null) {
            $this->view->assign('data', $this->obj->fetchAll()->toArray());
            return $this->render('list');
        }
        $this->view->assign('record', $this->obj->find($id));
        return $this->render('detail');
    }

    public function updateAction()
    {
        if ($this->_request->isPost() !== true) {
            throw new \RuntimeException("Method must be post.");
        }
    }
}
