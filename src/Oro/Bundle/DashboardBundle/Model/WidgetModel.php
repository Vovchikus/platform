<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;

class WidgetModel
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Widget
     */
    protected $entity;

    /**
     * @var WidgetState
     */
    protected $state;

    /**
     * @param Widget      $widget
     * @param array       $config
     * @param WidgetState $widgetState
     */
    public function __construct(Widget $widget, array $config, WidgetState $widgetState)
    {
        $this->entity = $widget;
        $this->config = $config;
        $this->state  = $widgetState;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Widget
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return WidgetState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getEntity()->getId();
    }

    /**
     * @param string $name
     * @return Widget
     */
    public function setName($name)
    {
        $this->getEntity()->setName($name);
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getEntity()->getName();
    }

    /**
     * @param array $layoutPosition
     * @return Widget
     */
    public function setLayoutPosition(array $layoutPosition)
    {
        $this->getState()->setLayoutPosition($layoutPosition);

        return $this;
    }

    /**
     * @return array
     */
    public function getLayoutPosition()
    {
        return $this->getState()->getLayoutPosition();
    }

    /**
     * @param boolean $expanded
     * @return Widget
     */
    public function setExpanded($expanded)
    {
        $this->getState()->setExpanded($expanded);

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpanded()
    {
        return $this->getState()->isExpanded();
    }
}
