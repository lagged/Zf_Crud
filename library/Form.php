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

namespace Lagged\Zf\Crud;

/**
 * @category Management
 * @package  Lagged\Zf\Crud
 * @author   Yvan Volochine <yvan.volochine@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_release@
 * @link     http://lagged.biz
 */

class Form extends \Zend_Form
{

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        $this->setMethod('post');
    }

    public function generate($cols)
    {
        foreach ($cols as $col) {
            if (! $col['PRIMARY']) {
                $this->_createElement($col);
            }
        }

        $this->addElement(
            'submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'submit',
            )
        );

    }

    /**
     * _createElement
     */
    private function _createElement($col)
    {
        switch ($col['DATA_TYPE']) {
            case 'int':
                $element = new \Zend_Form_Element_Text($col['COLUMN_NAME']);
                break;
            case 'varchar':
                $element = new \Zend_Form_Element_Text($col['COLUMN_NAME']);
                if ($col['LENGTH']) {
                    $element->setAttrib('size', $col['LENGTH']);
                    $element->setAttrib('maxlength', $col['LENGTH']);
                }
                break;
            case 'date':
                $element = new \Zend_Form_Element_Text($col['COLUMN_NAME']);
                $element->setAttrib('size', 10);
                $element->setAttrib('maxlength', 10);
                break;
            case 'datetime':
                $element = new \Zend_Form_Element_Text($col['COLUMN_NAME']);
                $element->setAttrib('size', 19);
                $element->setAttrib('maxlength', 19);
                break;
            case 'text':
                $element = new \Zend_Form_Element_Textarea($col['COLUMN_NAME']);
                $element->setAttrib('cols', 40)->setAttrib('rows', 10);
                break;
            default:
                throw new \Zend_Exception($col['DATA_TYPE'] . ' is not implemented');
        }
        $element->setLabel($col['COLUMN_NAME']);
        $this->addElement($element);
    }
}
