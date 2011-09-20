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
    protected $dbAdapter = 'dbAdapter';

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
     * @var array $hidden Hidden columns
     * @see self::init()
     */
    protected $hidden = array();

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
        $this->view->addHelperPath(dirname(__DIR__) . '/views/helpers/', 'Crud_View_Helper');

        $this->cols = array_diff(
            array_keys($this->obj->info(\Zend_Db_Table_Abstract::METADATA)),
            $this->hidden
        );

        $this->primaryKey = array_values($this->obj->info('primary')); // composite?

        $this->view->assign('cols', $this->cols);
        $this->view->assign('primary', $this->primaryKey);
    }

    /**
     * Create!
     *
     * GET: form
     * POST: create
     *
     * @return void
     */
    public function createAction()
    {
        $form = $this->_getForm();

        if ($this->_request->isPost() === true) {
            // validate
            // save
        }

        $this->view->form = $form;
    }

    /**
     * Delete
     *
     * GET: confirm
     * POST: delete
     *
     * @return void
     */
    public function deleteAction()
    {
        if (null === ($id = $this->_getParam('id'))) {
            throw new \InvalidArgumentException("ID is not set.");
        }

        $form = new Form_Confirm();

        $this->view->assign('pkValue', $id);

        if ($this->_request->isPost() !== true) {
            $this->view->assign('form', $form);
            return $this->render('crud/delete', null, true); // confirm
        }
        try {
            $stmt = $this->_getWhereStatement($id);
            $this->obj->delete($stmt);
            $this->_helper->redirector('list');
        } catch (\Zend_Exception $e) {
            throw $e;
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
            return $this->_helper->redirector('list');
        }

        $record = $this->obj->find($id)->toArray();
        $this->view->assign('record', $record[0]);
        $this->view->assign('pkValue', $id);
        return $this->render('crud/detail', null, true);
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
        return $this->render('crud/list', null, true);
    }

    /**
     * edit
     *
     * GET: form
     * POST: update
     *
     * @return void
     */
    public function editAction()
    {
        if (null === ($id = $this->_getParam('id'))) {
            throw new \Runtime_Exception('bouh');
        }

        $form = $this->_getForm();

        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $this->_update($id, $form->getValues());
            }
        }
        $record = $this->obj->find($id)->toArray();
        $form->populate($record[0]);
        $this->view->assign('form', $form);
        $this->view->assign('pkValue', $id);

        return $this->render('crud/edit', null, true);
    }

    private function _update($id, $data)
    {
        $id = ((int) $id == $id) ? (int) $id : $id;
        try {
            $stmt = $this->_getWhereStatement($id);
            $this->obj->update($data, $stmt);
            $this->_helper->redirector('list');
        } catch (\Zend_Exception $e) {
            throw $e;
        }
    }

    /**
     * Create the form
     *
     * @return \Lagged\Zf\Crud\Form
     * @uses   \Zend_Db_Table_Abstract::info()
     */
    private function _getForm()
    {
        $form = new Form_Edit();
        $form->generate(
            $this->obj->info(\Zend_Db_Table_Abstract::METADATA)
        );
        return $form;
    }

    /**
     * Create the paginator for {@link self::listAction()}.
     *
     * @return \Zend_Paginator
     * @uses   self::$dbAdapter
     * @uses   self::$obj
     */
    private function _getPaginator()
    {
        $db        = \Zend_Registry::get($this->dbAdapter);
        $table     = $this->obj->info('name');
        $select    = $db->select()->from($table);
        $paginator = \Zend_Paginator::factory($select);
        $paginator->setItemCountPerPage($this->count);

        return $paginator;
    }

    /**
     * @return string
     */
    private function _getTable()
    {
        return $this->obj->info('name');
    }

    private function _getWhereStatement($id)
    {
        $id = ((int) $id == $id) ? (int) $id : $id;
        $where = $this->obj->getAdapter()
            ->quoteInto($this->primaryKey[0] . ' = ?', $id);
        return $where;
    }
}
