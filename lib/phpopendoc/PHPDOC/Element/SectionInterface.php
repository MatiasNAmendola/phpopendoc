<?php
/**
 * This file is part of the PHP Open Doc library.
 *
 * @author Jason Morriss <lifo101@gmail.com>
 * @since  1.0
 * 
 */
namespace PHPDOC\Element;

interface SectionInterface extends \ArrayAccess, \Countable
{

    /**
     * Returns all elements within the section.
     * 
     * The consumer will take this array of elements and generate the required
     * output to produce the section within the document.
     */
    public function getElements();

    /**
     * Returns the internal name of the Section.
     */
    public function getName();

    /**
     * Sets the internal name of the Section.
     */
    public function setName($name);

}