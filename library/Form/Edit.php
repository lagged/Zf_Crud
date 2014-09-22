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
 * A basic form for the {@link \Lagged\Zf\Crud\Controller}
 *
 * @category Management
 * @package  Lagged\Zf\Crud\Form
 * @author   Yvan Volochine <yvan.volochine@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_release@
 * @link     http://lagged.biz
 */
class Edit extends \Zend_Form
{
    /**
     * @var mixed (array|null)
     */
    protected $primaryKeys;

    public function __construct($primaryKeys)
    {
        $this->primaryKeys = $primaryKeys;
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
            if (count($this->primaryKeys) > 1) {
                $this->_createElement($col);
            } else if (count($this->primaryKeys) == 1) {
                $this->_createElement($col);
            }
        }

        $this->addElement(
            'submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'submit',
            )
        );

        $elements = $this->getElements();
        $this->addDisplayGroup($elements, 'edit', array('legend' => 'Edit Entry'));


        $this->setAttrib('class', 'form-horizontal');
        $this->setMethod('post');

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
        if (preg_match('/^(enum|set)\((.+)\)/', $col['DATA_TYPE'], $matches)) {
            $col['DATA_TYPE'] = $matches[1];
            $col['DATA_LIST'] = $this->_getEnumList(
                $matches[2], $col['NULLABLE']
            );
        };

        $datetimeValidator = new \Zend_Validate_Date(
            array('format' => 'yyyy-MM-dd HH:ii:ss')
        );


        switch (strtolower($col['DATA_TYPE'])) {
        case 'int':
            $element = new \Zend_Form_Element_Text($col['COLUMN_NAME']);
            $element->setAttrib('size', $col['LENGTH'])
                ->setAttrib('maxlength', $col['LENGTH'])
                ->addValidator('Int');
            
            break;
        case 'number':
        case 'char':
        case 'varchar2':
        case 'varchar':
            $element = new \Zend_Form_Element_Text($col['COLUMN_NAME']);
            if ($col['LENGTH']) {
                $element->setAttrib('size', $col['LENGTH']);
                $element->setAttrib('maxlength', $col['LENGTH']);
            }
            break;
        case 'date':
            $element = new \Zend_Form_Element_Text($col['COLUMN_NAME']);
            $element->setAttrib('size', 10)->setAttrib('maxlength', 10)
                    ->setAttrib('type', 'date')
                ->addValidator('Date');
            break;
        case 'datetime':
            $element = new \Zend_Form_Element_Text($col['COLUMN_NAME']);
            $element->setAttrib('size', 19)->setAttrib('maxlength', 19)
                ->addValidator($datetimeValidator);
            break;
        case 'text':
            $element = new \Zend_Form_Element_Textarea($col['COLUMN_NAME']);
            $element->setAttrib('class', 'xxlarge')->setAttrib('cols', 100)
                ->setAttrib('rows', 20);
            break;
        case 'tinyint':
            $element = new \Zend_Form_Element_Checkbox($col['COLUMN_NAME']);
            $element->setAttrib('size', 1)->setAttrib('max_length', 1);
            break;
        case 'enum':
            $element = new \Zend_Form_Element_Select($col['COLUMN_NAME']);
            $element->addMultiOptions($col['DATA_LIST']);
            break;
        case 'set':
            $element = new \Zend_Form_Element_Select($col['COLUMN_NAME']);
            $element->addMultiOptions($col['DATA_LIST']);
            break;
        default:
            throw new \Zend_Exception($col['DATA_TYPE'] . ' is not implemented');
        }
        
        if (!empty($col['DESCRIPTION'])) {
            $element->setLabel($col['DESCRIPTION'] . ' :');
        } else {
            $element->setLabel($col['COLUMN_NAME']);
        }
        
        if (!empty($col['DEFAULT_VALUE'])) {
            $element->setValue($col['DEFAULT_VALUE']);
        }
        
        $element->setRequired(! $col['NULLABLE']);

        $this->addElement($element);
    }

    /**
     * _getEnumList
     * Parse the enum|set string and reformat array to get a valid data list.
     *
     * @param string $str      The string to parse
     * @param bool   $nullable If the column can be null
     * @return array
     * @throws Zend_Exception if the string is not valid
     */
    private function _getEnumList($str, $nullable)
    {
        try {
            $str  = str_replace('\'', '', $str);
            $data = explode(',', $str);
            foreach ($data as $key => $value) {
                $data[$value] = $value;
                unset($data[$key]);
            }
            if ($nullable) {
                $data = array_merge(array("" => "None"), $data);
            }
            return $data;
        } catch (\Zend_Exception $e) {
            throw $e;
        }
    }

}
