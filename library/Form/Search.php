<?php
/**
 * EasyBib Copyright 2008-2011
 * Modifying, copying, of code contained herein that is not specifically
 * authorized by Imagine Easy Solutions LLC ("Company") is strictly prohibited.
 * Violators will be prosecuted.
 *
 * This restriction applies to proprietary code developed by EasyBib. Code from
 * third-parties or open source projects may be subject to other licensing
 * restrictions by their respective owners.
 *
 * Additional terms can be found at http://www.easybib.com/company/terms
 *
 * PHP Version 5
 *
 * @category Management
 * @package  Lagged\Zf\Crud
 * @author   Yvan Volochine <yvan.volochine@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  GIT: $Id$
 * @link     http://lagged.biz
 */

namespace Lagged\Zf\Crud\Form;

/**
 * A basic jump-to-page form for the {@link \Lagged\Zf\Crud\Controller}
 *
 * @category Management
 * @package  Lagged\Zf\Crud\Form
 * @author   Yvan Volochine <yvan.volochine@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_release@
 * @link     http://lagged.biz
 */
class Search extends \Zend_Form
{
    /**
     * var Zend_Form_Element_Select $columns ''
     */
    public $columns;

    /**
     * init
     *
     * @return void
     * @TODO Validators
     */
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('id', 'searchForm');
        $this->setAttrib('class', 'form-inline');
        $this->setAttrib('style', 'margin: 0');

        $this->addElement(
            'text', 'search', array(
                'label'      => 'Term',
                'required'   => true,
                /*
                'validators' => array(
                    array(
                        'validator' => 'Alnum',
                        'options'   => array(0, 20)
                    )
                )*/
            )
        );

        $this->addElement(
            'checkbox', 'exact', array(
                'label'      => 'exact'
            )
        );

        $this->columns = new \Zend_Form_Element_Select('columns');
        $this->columns->setLabel('into')->setRequired(true)
            ->setRegisterInArrayValidator(false);

        $this->addElement($this->columns);

        $this->addElement(
            'submit', 'submit', array(
                'ignore'     => true,
                'label'      => 'Search'
            )
        );

        $this->addDisplayGroup(
            array('search', 'exact', 'columns', 'submit'),
            'searchForm',
            array('legend' => '')
        );

        \EasyBib_Form_Decorator::setFormDecorator(
            $this,
            \EasyBib_Form_Decorator::BOOTSTRAP_MINIMAL,
            'submit',
            'cancel'
        );

        $this->getElement('submit')->setAttrib('class', 'btn btn-primary btn-mini');
        $this->getElement('search')->setAttrib('style', 'margin-right: 10px; width: auto;');
        $this->getElement('search')->setAttrib('class', 'input-medium');
        $this->getElement('exact')->setAttrib('style', 'margin: -2px 10px 0 0;');
        $this->getElement('columns')->setAttrib('class', 'input-medium');
        $this->getElement('columns')->setAttrib('style', 'margin-right: 10px;');

    }

}
