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
 * A basic form for the {@link \Lagged\Zf\Crud\Controller}
 *
 * @category Management
 * @package  Lagged\Zf\Crud\Form
 * @author   Yvan Volochine <yvan.volochine@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_release@
 * @link     http://lagged.biz
 */
class Form_Edit extends \Zend_Form
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

    /**
     * Generate the form from columns from \Zend_Db_Table
     *
     * @param array $cols
     *
     * @return void
     * @uses   self::_createElement()
     * @uses   EasyBib_Form_Decorator::setFormDecorator()
     */
    public function generate(array $cols)
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

        /**
         * @desc Apply Twitter Bootstrap to all elements.
         */
        \EasyBib_Form_Decorator::setFormDecorator(
            $this,
            \EasyBib_Form_Decorator::BOOTSTRAP,
            'submit',
            'cancel'
        );
    }

    /**
     * _createElement
     */
    private function _createElement($col)
    {
        if (preg_match('/^enum\((.+)\)/', $col['DATA_TYPE'], $matches)) {
            $col['DATA_TYPE'] = 'enum';
            $col['DATA_LIST'] = $this->_getEnumList($matches[1]);
        };

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
            $element->setAttrib('class', 'xxlarge')->setAttrib('cols', 100)->setAttrib('rows', 20);
            break;
        case 'enum':
            $element = new \Zend_Form_Element_Select($col['COLUMN_NAME']);
            $element->addMultiOptions($col['DATA_LIST']);
            break;
        default:
            var_dump($col); die();

            throw new \Zend_Exception($col['DATA_TYPE'] . ' is not implemented');
        }
        $element->setLabel($col['COLUMN_NAME']);
        $this->addElement($element);
    }

    /**
     * _getEnumList
     * Parse the enum string and reformat array to get a valid data list for ENUM.
     *
     * @param string $str ''
     * @return array
     * @throws Zend_Exception if enum string is not valid
     */
    private function _getEnumList($str)
    {
        try {
            $str  = str_replace('\'', '', $str);
            $data = explode(',', $str);
            foreach ($data as $key => $value) {
                $data[$value] = $value;
                unset($data[$key]);
            }
            return $data;
        } catch (\Zend_Exception $e) {
            throw $e;
        }
    }

}
