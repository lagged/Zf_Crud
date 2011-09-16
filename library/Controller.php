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
     * @var string $dbAdapter Name of Zend_Registry key for DbAdapter
     */
    protected $dbAdapter;

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
     * @var int $count Maximum count when fetching all rows
     * @see listAction
     */
    protected $count = 30;

    /**
     * @var array $primaryKey
     */
    protected $primaryKey;

    /**
     * @var string $title For the view.
     */
    protected $title = 'CRUD INTERFACE';

    /**
     * Init
     *
     * @return void
     * @uses   self::$model
     * @uses   self::$dbAdapter
     * @uses   Zend_View
     */
    public function init()
    {
        if (empty($this->model)) {
            throw new \RuntimeException("You need to define self::model");
        }

        $this->obj = new $this->model(array('db' => $this->dbAdapter));
        if (!($this->obj instanceof \Zend_Db_Table_Abstract)) {
            throw new \LogicException("The model must extend Zend_Db_Table_Abstract");
        }
        $this->view->headLink()->appendStylesheet(
            'http://twitter.github.com/bootstrap/assets/css/bootstrap-1.2.0.min.css'
        );
        $this->view->assign('ui_title', $this->title);
        $this->view->addScriptPath(dirname(__DIR__) . '/views/scripts/');

        $this->cols = $this->obj->info(\Zend_Db_Table_Abstract::METADATA);

        $this->primaryKey = array_values($this->obj->info('primary')); // composite?

        $this->view->assign('cols', $this->cols);
        $this->view->assign('primary', $this->primaryKey);
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
            return $this->render('crud/delete', null, true);
        }
    }

    /**
     * Placeholder: redirect to list
     *
     * @return void
     */
    public function indexAction()
    {
        return $this->_helper->redirector('list');
    }

    /**
     * Display a single entry.
     *
     * @return void
     */
    public function readAction()
    {
        $pkey = $this->primaryKey[0];

        if (null === ($id = $this->_getParam($pkey))) {
            $this->_helper->redirector('list');
            return;
        }

        $record = $this->obj->find($id)->toArray();
        $this->view->assign('record', $record[0]);
        $this->view->assign('pkValue', $id);
        $this->render('crud/detail', null, true);
    }

    /**
     * Display a list of entries in a table.
     *
     * @return void
     */
    public function listAction()
    {
        $offset = null;
        $count  = $this->_getParam('count', $this->count);
        $page   = abs($this->_getParam('page', 1));
        $page   = ((int) $page == 0) ? 1 : $page;
        $offset = ((int) $page - 1) * $count;

        $paginator = $this->_getPaginator();
        $paginator->setCurrentPageNumber($page);

        $this->view->paginator = $paginator;

        $data = $this->obj->fetchAll(null, null, $count, $offset)->toArray();
        $this->view->assign('data', $data);
        $this->render('crud/list', null, true);
    }

    public function editAction()
    {
        if (null === ($id = $this->_getParam('id'))) {
            throw new \Runtime_Exception('bouh');
        }
        include_once __DIR__ . '/Form.php';
        $form = new Form();
        $form->generate($this->cols);

        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $this->_update($id, $form->getValues());
            }
        }
        $record = $this->obj->find($id)->toArray();
        $form->populate($record[0]);
        $this->view->form = $form;
    }

    private function _update($id, $data)
    {
        $id = ((int) $id == $id) ? (int) $id : $id;
        try {
            $where = $this->obj->getAdapter()
                ->quoteInto($this->primaryKey[0] . ' = ?', $id);
            $this->obj->update($data, $where);
            $this->_helper->redirector('list');
        } catch (\Zend_Exception $e) {
            throw $e;
        }
    }

    private function _getPaginator()
    {
        $db        = \Zend_Registry::get($this->dbAdapter);
        $table     = $this->obj->info('name');
        $select    = $db->select()->from($table);
        $paginator = \Zend_Paginator::factory($select);
        $paginator->setItemCountPerPage($this->count);

        return $paginator;
    }

    private function _getTable()
    {
        return $this->obj->info('name');
    }
}
