<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\View\Helper;

use Laminas\View\Exception;
use Laminas\View\Model\ModelInterface as Model;

/**
 * View helper for retrieving layout object
 *
 * @package    Laminas_View
 * @subpackage Helper
 */
class Layout extends AbstractHelper
{
    /**
     * @var ViewModel
     */
    protected $viewModelHelper;

    /**
     * Get layout template
     *
     * @return string
     */
    public function getLayout()
    {
        $model = $this->getRoot();
        return $model->getTemplate();
    }

    /**
     * Set layout template
     *
     * @param  string $template
     * @return Layout
     */
    public function setTemplate($template)
    {
        $model = $this->getRoot();
        $model->setTemplate((string) $template);
        return $this;
    }

    /**
     * Set layout template or retrieve "layout" view model
     *
     * If no arguments are given, grabs the "root" or "layout" view model.
     * Otherwise, attempts to set the template for that view model.
     *
     * @param  null|string $template
     * @return Layout
     */
    public function __invoke($template = null)
    {
        if (null === $template) {
            return $this->getRoot();
        }
        return $this->setTemplate($template);
    }

    /**
     * Get the root view model
     *
     * @throws Exception\RuntimeException
     * @return null|Model
     */
    protected function getRoot()
    {
        $helper = $this->getViewModelHelper();
        if (!$helper->hasRoot()) {
            throw new Exception\RuntimeException(sprintf(
                '%s: no view model currently registered as root in renderer',
                __METHOD__
            ));
        }
        return $helper->getRoot();
    }

    /**
     * Retrieve the view model helper
     *
     * @return ViewModel
     */
    protected function getViewModelHelper()
    {
        if ($this->viewModelHelper) {
            return $this->viewModelHelper;
        }
        $view = $this->getView();
        $this->viewModelHelper = $view->plugin('view_model');
        return $this->viewModelHelper;
    }
}
