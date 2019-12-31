<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\View\Helper;

use Laminas\View\Exception\InvalidArgumentException;

/**
 * Helper for passing data between otherwise segregated Views. It's called
 * Placeholder to make its typical usage obvious, but can be used just as easily
 * for non-Placeholder things. That said, the support for this is only
 * guaranteed to effect subsequently rendered templates, and of course Layouts.
 *
 * @package    Laminas_View
 * @subpackage Helper
 */
class Placeholder extends AbstractHelper
{
    /**
     * Placeholder items
     * @var array
     */
    protected $items = array();

    /**
     * @var \Laminas\View\Helper\Placeholder\Registry
     */
    protected $registry;

    /**
     * Constructor
     *
     * Retrieve container registry from Placeholder\Registry, or create new one and register it.
     *
     */
    public function __construct()
    {
        $this->registry = Placeholder\Registry::getRegistry();
    }

    /**
     * Placeholder helper
     *
     * @param  string $name
     * @return \Laminas\View\Helper\Placeholder\Container\AbstractContainer
     * @throws InvalidArgumentException
     */
    public function __invoke($name = null)
    {
        if ($name == null) {
            throw new InvalidArgumentException('Placeholder: missing argument.  $name is required by placeholder($name)');
        }

        $name = (string) $name;
        return $this->registry->getContainer($name);
    }

    /**
     * Retrieve the registry
     *
     * @return \Laminas\View\Helper\Placeholder\Registry
     */
    public function getRegistry()
    {
        return $this->registry;
    }
}
