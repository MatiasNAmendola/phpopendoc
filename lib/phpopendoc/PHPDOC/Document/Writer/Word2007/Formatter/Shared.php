<?php
/**
 * This file is part of the PHP Open Doc library.
 *
 * @author Jason Morriss <lifo101@gmail.com>
 * @since  1.0
 *
 */
namespace PHPDOC\Document\Writer\Word2007\Formatter;

use PHPDOC\Element\ElementInterface;

/**
 * Shared property formats
 */
class Shared
{
    protected $map;
    private $aliases;

    public function __construct() {
        $this->initMap();
    }

    /**
     * Initialize the property map
     *
     * Subclasses should override this to provide a property map.
     */
    protected function initMap($aliases = null)
    {
        $this->map = array();
        $this->aliases = array();
        if (is_array($aliases) and count($aliases)) {
            $this->aliases = $aliases;
        }
    }

    /**
     * Create a new properties DOM object for the element.
     *
     * @param mixed     $element The element to extract properties from
     * @param \DOMNode  $root    The DOMNode to interface with
     * @return boolean  Return true if any properties were processed.
     */
    public function format(ElementInterface $element, \DOMNode $root)
    {
        $modified = false;
        foreach ($element->getProperties() as $name => $val) {
            $type = $this->lookup($name);
            if ($type) {
                if ($this->process($type, $name, $val, $element, $root)) {
                    $modified = true;
                }
            }
        }
        return $modified;
    }

    /**
     * Process a property
     *
     * @param string           $type    The translation type.
     * @param string           $name    The original property name.
     * @param mixed            $val     The property value or array.
     * @param ElementInterface $element The element being processed.
     * @param \DOMNode         $root    The DOM node to update.
     * @return boolean Return true if the property was processed
     */
    public function process($type, $name, $val, $element, $root)
    {
        $ret = false;
        $method = 'process_' . $type;
        if (method_exists($this, $method)) {
            $ret = $this->$method($this->lookupAlias($name), $val, $element, $root);
            if (!is_bool($ret)) {
                // failsafe for when I forget to return true from one of my
                // process methods and i spend several minutes wondering why
                // the property is not being saved to the document...
                throw new \UnexpectedValueException(get_class($this) . "::$method did not return a boolean. Got " . gettype($ret) . " instead");
            }
        } else {
            // @todo Should an exception be thrown for unknown properties?
        }
        return $ret;
    }

    /**
     * Process simple boolean property
     *
     * @param string           $name    The original property name.
     * @param mixed            $val     The property value or array.
     * @param ElementInterface $element The element being processed.
     * @param \DOMNode         $root    The DOM node to update.
     * @return boolean Return true if the property was processed
     */
    protected function process_bool($name, $val, ElementInterface $element, \DOMNode $root)
    {
        return $this->appendSimpleValue($root, $name, $this->getOnOff($val));
    }

    protected function process_decimal($name, $val, ElementInterface $element, \DOMNode $root)
    {
        return $this->appendSimpleValue($root, $name, intval($val));
    }

    protected function process_text($name, $val, ElementInterface $element, \DOMNode $root)
    {
        return $this->appendSimpleValue($root, $name, $val);
    }

    /**
     * Lookup a property name and return the translation type for it
     *
     * @return string The translation type
     * @param string $name The property name/alias to lookup
     */
    public function lookup($name)
    {
        $name = $this->lookupAlias($name);
        if (isset($this->map[$name])) {
            return $this->map[$name];
        }
        return $name;
    }

    /**
     * Lookup a property alias
     *
     * @return Returns the original name if no alias is defined.
     * @param string $name The original property name.
     */
    protected function lookupAlias($name, $aliases = null)
    {
        if (!$aliases) {
            $aliases =& $this->aliases;
        }
        if (isset($aliases[$name])) {
            return $aliases[$name];
        }
        return $name;
    }

    /**
     * Assign a simple value to the root, <w:$name $key="$val"/>
     */
    public function appendSimpleValue(\DOMNode $root, $name, $val, $key='w:val')
    {
        if (is_bool($val)) {
            $val = self::getOnOff($val);
        }
        if ($val !== null and $val !== '') {
            $node = $root->ownerDocument->createElement('w:' . $name);
            $node->appendChild(new \DOMAttr($key, $val));
            $root->appendChild($node);
        }
        return true;
    }

    /**
     * Return 'on', 'off' or null based on the value given.
     */
    public function getOnOff($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'on' : 'off';
        }
        $value = strtolower($value);
        if (in_array($value, array('on', 'true', 'yes', '1', 1))) {
            return 'on';
        }
        if (in_array($value, array('off', 'false', 'no', '0', 0))) {
            return 'off';
        }
        return null;
    }
}
