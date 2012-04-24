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
use Lagged\Zf\Crud\Form\Edit    as Edit;
use Lagged\Zf\Crud\Form\Confirm as Confirm;
use Lagged\Zf\Crud\Form\Search  as Search;
use Lagged\Zf\Crud\Form\JumpTo  as JumpTo;

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
     * @var string $dbAdapter Name of \Zend_Registry key for DbAdapter
     */
    protected $dbAdapter = 'dbAdapter';

    /**
     * @var string $model
     */
    protected $model;

    /**
     * @var \Zend_Db_Table_Abstract
     * @see self::init()
     */
    protected $obj;

    /**
     * @var string
     * @see self::init()
     */
    protected $tableName;

    /**
     * @var \Zend_Session_Namespace $session
     */
    protected $session;

    /**
     * @var array $hidden Hidden columns
     * @see self::init()
     */
    protected $hidden = array();

    /**
     * @var string $where Mysql WHERE query for search form
     * @see listAction
     */
    protected $where;

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
     * @var string $order ORDER BY column name
     */
    protected $order;

    /**
     * @var string $orderType ORDER BY type
     */
    protected $orderType = 'ASC';

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

        $this->_initModel();

        $this->_initSession();

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
        $this->view->action = $this->getRequest()->getActionName();
    }

    /**
     * Create!
     *
     * POST: create
     *
     * @return void
     */
    public function createAction()
    {
        $form = $this->_getForm();

        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $this->_insert($form->getValues());
                $this->_helper->redirector('list');
                return;
            }
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

        $form = new Confirm();

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
     * The POST form here is always SearchForm
     *
     * @return void
     * @todo: refactor
     */
    public function listAction()
    {
        $this->_checkSession($this->_request);
        if (null !== ($table = $this->_getParam('table'))) {
            $this->tableName = $table;
            $this->_initModel();
        }
        $offset    = null;
        $page      = abs($this->_getParam('p', 1));
        $page      = ((int) $page == 0) ? 1 : $page;
        $offset    = ((int) $page - 1) * $this->count;
        $order     = $this->_getParam('o');
        $orderType = $this->_getParam('ot');

        $searchForm = new Search();
        $searchForm->columns->addMultiOptions($this->cols);

        if ($this->_request->isPost()) {
            if ($searchForm->isValid($this->_request->getPost())) {
                $data = $searchForm->getValues();
                $this->_assignSearchWhereQuery($data);
            }
        }

        if (isset($order) && isset($orderType)) {
            $this->_assignOrderBy($order, $orderType);
        }

        $paginator = $this->_getPaginator();
        $paginator->setCurrentPageNumber($page);

        $this->view->paginator = $paginator;

        if ($this->order) {
            $this->view->order = $this->order;
        }
        $this->view->otNew = $this->_getNextOrderType($this->orderType);

        $query = $this->_request->getQuery();
        $this->view->assign('urlParams', array('params' => $query));

        $url = array('action' => 'jump');
        if ($this->order) {
            $url['o']  = $this->order;
            $url['ot'] = $this->orderType;
        }
        $url = $this->view->BetterUrl($url);

        $jumpForm = new JumpTo();
        $this->view->jumpForm = $jumpForm->setAction($url);

        $searchForm->columns->addMultiOptions($this->cols);
        $this->view->searchForm = $searchForm->setAction($this->view->url());

        return $this->render('crud/list', null, true);
    }

    /**
     * edit
     *
     * POST: update
     *
     * @return void
     */
    public function editAction()
    {
        if (null === ($id = $this->_getParam('id'))) {
            throw new \Runtime_Exception('invalid id');
        }

        $form = $this->_getForm();

        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $this->_update($id, $form->getValues());
                $this->_helper->redirector('list');
                return;
            }
        }
        $record = $this->obj->find($id)->toArray();
        $form->populate($record[0]);
        $this->view->assign('form', $form);
        $this->view->assign('pkValue', $id);

        return $this->render('crud/edit', null, true);
    }

    /**
     * jumpAction
     *
     * @return void
     */
    public function jumpAction()
    {
        $form = new JumpTo();
        $o    = $this->_getParam('o');
        $ot   = $this->_getParam('ot');
        $params = $o
            ? array('action' => 'list', 'o' => $o, 'ot' => $ot)
            : array('action' => 'list');

        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $data = $form->getValues();
                $params['p'] = $data['p'];
                $url = $this->view->BetterUrl($params);
                $this->_helper->redirector->gotoUrl($url);
            } else {
                $this->_helper->redirector('list');
            }
        }
    }

    /**
     * insert row into DB
     *
     * @param array $data ''
     * @return void
     * @throws Zend_Exception if row cannot be inserted
     */
    private function _insert($data)
    {
        try {
            $this->obj->insert($data);
        } catch (\Zend_Exception $e) {
            throw $e;
        }
    }

    /**
     * Update DB row with data
     *
     * @param mixed $id   ''
     * @param array $data ''
     * @return void
     * @throws Zend_Exception if row cannot be updated
     */
    private function _update($id, $data)
    {
        $id = ((int) $id == $id) ? (int) $id : $id;
        try {
            $stmt = $this->_getWhereStatement($id);
            $this->obj->update($data, $stmt);
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
        $form = new Edit();
        $form->generate(
            $this->obj->info(\Zend_Db_Table_Abstract::METADATA)
        );
        return $form;
    }

    /**
     * Create the paginator for {@link self::listAction()}.
     * Try to get a WHERE statement from the form ($this->where), otherwise
     * see if an older one is still in SESSION.
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

        if ($this->where) {
            $select->where($this->where);
        } else if (isset($this->session->query)) {
            $select->where($this->session->query);
        }
        if ($this->order && $this->orderType) {
            $select->order($this->order . ' ' . $this->orderType);
        }

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

    /**
     * _getWhereStatement
     *
     * @param mixed $id
     * @return string
     */
    private function _getWhereStatement($id)
    {
        $id = ((int) $id == $id) ? (int) $id : $id;
        $where = $this->obj->getAdapter()
            ->quoteInto($this->primaryKey[0] . ' = ?', $id);

        return $where;
    }

    /**
     * _assignOrderBy
     *
     * @param string $order     order column
     * @param string $type ''
     * @return void
     * @throws Zend_Exception if the string is invalid
     */
    private function _assignOrderBy($order, $type)
    {
        $validTypes = array('ASC', 'DESC');
        if (! in_array($order, $this->cols)
            || ! in_array($type, $validTypes)
        ) {
            throw new \Zend_Exception('Invalid order');
        };
        $this->order = $order;
        $this->orderType = $type;
    }

    /**
     * _getNextOrderType
     * Returns the opposite of current order
     *
     * @return string
     */
    private function _getNextOrderType()
    {
        return ($this->orderType == 'ASC')
            ? 'DESC'
            : 'ASC';
    }

    /**
     * _assignSearchWhereQuery
     *
     * @param array $data ''
     * @return void
     */
    private function _assignSearchWhereQuery($data)
    {
        $search = $data['search'];
        $column = $this->cols[$data['columns']];
        $query  = ($data['exact'])
            ? sprintf("%s = '%s'", $column, $search)
            : sprintf("%s LIKE '%%%s%%'", $column, $search);
        $this->where = $query;
        $this->session->query = $query;
    }

    /**
     * _initSession
     *
     * @return void
     */
    private function _initSession()
    {
        \Zend_Session::start();
        if (! $this->session) {
            $this->session = new \Zend_Session_Namespace('crud');
        }
    }

    /**
     * _checkSession If $_GET['reset'] is set, reset the SESSION_NAMESPACE
     *
     * @param Zend_Controller_Request_Http $req
     * @return void
     */
    private function _checkSession($req)
    {
        if ($req->isGet()
            && (null !== ($reset = $this->_getParam('reset')))
        ) {
            $this->session->query = null;
        }
    }

    /**
     * _initModel
     *
     * @return void
     * @throws LogicException if the model type is not Zend_Db_Table_Abstract
     */
    private function _initModel()
    {
        $options = array('db' => $this->dbAdapter);
        if ($this->tableName) {
            $options['name'] = $this->tableName;
        }
        $this->obj = new $this->model($options);
        if (!($this->obj instanceof \Zend_Db_Table_Abstract)) {
            throw new \LogicException(
                'The model must extend Zend_Db_Table_Abstract'
            );
        }
    }

}
