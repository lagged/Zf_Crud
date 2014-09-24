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
     * @var bool $bulkDelete Show delete checkboxes in list view
     */
    protected $bulkDelete = false;

    /**
     * @var array $columns to show
     */
    protected $availableColumns = array();
    
    /**
     * @var Zend_Db::select $customSelect
     */
    protected $customSelect;
    
    /**
     * @var array $enumList
     */
    protected $enumList;
    
    /**
     * @var array $defaultValues
     */
    protected $defaultValue;
    
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

        $this->view->assign('ui_title', $this->title);

        $this->view->addScriptPath(dirname(__DIR__) . '/views/scripts/');
        $this->view->addHelperPath(dirname(__DIR__) . '/views/helpers/', 'Crud_View_Helper');
        
        if (!empty($this->availableColumns)) {
            $this->cols = $this->availableColumns;
        } else {
            $this->cols = array_diff(
                array_keys($this->obj->info(\Zend_Db_Table_Abstract::METADATA)),
                $this->hidden
            );
        }
        
        if (empty($this->primaryKey)) {
            $this->primaryKey = array_values($this->obj->info('primary')); // composite?
        }
        
        $this->view->assign('cols', $this->cols);
        $this->view->assign('primary', $this->primaryKey);

        $this->view->assign('requestAction', $this->getRequest()->getActionName());
        $this->view->assign('requestModule', $this->getRequest()->getModuleName());
    }

    /**
     * Fire bulk actions (bulk delete)
     *
     * @return void
     */
    public function bulkAction()
    {
        $bulkPost = $this->_request->getPost('bulk');
        if (true === $this->bulkDelete && $this->_request->isPost() && is_array($bulkPost)) {
            $this->bulkDelete($bulkPost);
        }
        return $this->_helper->redirector('list');
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
        return $this->render('crud/create', null, true);
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
        if (null === ($id = $this->_getParam('primary-key'))) {
            throw new \InvalidArgumentException("ID is not set.");
        }

        $form = new Confirm();

        $this->view->assign('pkValue', $id);

        if ($this->_request->isPost() !== true) {
            $this->view->assign('form', $form);
            return $this->render('crud/delete', null, true); // confirm
        }
        if ($this->_request->isPost() === true &&
            $this->_request->getPost('confirm') == 'yes')
        {
            try {
                $stmt = $this->_getWhereStatement($id);
                $this->obj->delete($stmt);
                return $this->_helper->redirector('list');
            } catch (\Zend_Exception $e) {
                throw $e;
            }
        }
        $this->view->assign('form', $form);
        return $this->render('crud/delete', null, true);
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
        if (null === ($id = $this->_getParam('primary-key'))) {
            return $this->_helper->redirector('list');
        }
        $record = $this->getRecord($id);
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
        $time1 = microtime(true);
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
        if (!empty($this->availableColumns)) {
            $searchForm->columns->addMultiOptions(array_flip($this->cols));
        } else {
            $searchForm->columns->addMultiOptions($this->cols);
        }
        $time2 = microtime(true);
        error_log('LINE : ' . __LINE__  . ' TIME => ' . ($time2 - $time1));
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

        $this->view->searchForm = $searchForm->setAction($this->view->url());
        if ($this->session->searchFormState) {
            $this->view->searchForm->populate($this->session->searchFormState);
        }
        $this->view->bulkDelete = $this->bulkDelete;
        
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
        if (null === ($id = $this->_getParam('primary-key'))) {
            throw new \Runtime_Exception('invalid id');
        }

        $record = $this->getRecord($id);
        $form = $this->_getForm();

        if ($this->_request->isPost()) {
            if ($form->isValid($this->_request->getPost())) {
                $this->_update($id, $form->getValues());
                $this->_helper->redirector('list');
                return;
            }
        }
        $record = $this->getRecord($id);
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
        if (!empty($this->availableColumns)) {
            $infoDb = array_map('strtolower', $this->availableColumns);
            foreach ($infoDb as $description => $field) {
                $columns [$field] = $this->obj->info(\Zend_Db_Table_Abstract::METADATA)[$field];
                $columns [$field]['DESCRIPTION'] = $description;
                
                if (!empty($this->enumList[strtoupper($field)])) {
                    $columns [$field]['DATA_TYPE'] = 'ENUM';
                    $columns [$field]['DATA_LIST'] = $this->enumList[strtoupper($field)];
                }
                if (!empty($this->defaultValue[strtoupper($field)])) {
                    $columns [$field]['DEFAULT_VALUE'] = $this->defaultValue[strtoupper($field)];
                }
            }
        } else {
            $columns = $this->obj->info(\Zend_Db_Table_Abstract::METADATA);
        }
 
        $form = new Edit($this->primaryKey);
        $form->generate(
            $columns
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
        if (empty($this->customSelect)) {
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
        } else {
            $select = $this->customSelect;
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
     * @param  mixed $id
     * @return mixed (string/array)
     */
    private function _getWhereStatement($id)
    {
        if (is_numeric($id)) {
            $where = $this->obj->getAdapter()
                ->quoteInto($this->primaryKey[0] . ' = ?', $id);
        } else {
            $id = unserialize($id);
            foreach ($id as $key => $value) {
                $where[] = $this->obj->getAdapter()
                    ->quoteInto($key . ' = ?', $value);
            }
        }
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
        $this->session->searchFormState = $data;
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
            $this->session->searchFormState = null;
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

    /**
     * Get Record out of primary-key
     * (serialized array)
     *
     * @param  string $id Serialized id string
     * @return Zend_Db_Table_Rowset_Abstract
     */
    protected function getRecord($id)
    {
       // if (empty($this->customSelect)) {
            $primaryKey = unserialize($id);
            $record = call_user_func_array(
                array($this->obj, 'find'),
                array_values($primaryKey)
            )->toArray();
        /*} else {
            $primaryKey = unserialize($id);
            $db = \Zend_Registry::get($this->dbAdapter);
            $select = $this->customSelect;
            foreach ($primaryKey as $key => $value) {
                $select->where($key . ' = ?', trim($value));
            }
            var_dump($db->fetchRow($select));
            exit;
        }*/
        
        return $record;
    }

    /**
     * Bulk Delete entries
     *
     * @param  array With serialized ids
     * @return bool
     */
    protected function bulkDelete(array $bulkIds)
    {
        if (empty($bulkIds)) {
            return false;
        }
        foreach ($bulkIds as $id) {
            $primaryKey = urldecode($id);
            $where      = $this->_getWhereStatement($primaryKey);
            $this->obj->delete($where);
        }
        return true;
    }

}
