<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\CommentBundle\Tests\Behat\Element\CommentItem;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class ActivityListItem extends Element
{
    public function collapse()
    {
        $this->find('css', 'a.accordion-toggle')->click();
        $this->getDriver()->waitForAjax();
    }

    /**
     * @param string $linkTitle
     * @return NodeElement|null
     */
    public function getActionLink($linkTitle)
    {
        $this->find('css', 'div.actions a.dropdown-toggle')->mouseOver();
        $links = $this->findAll('css', 'li.launcher-item a');

        /** @var NodeElement $link */
        foreach ($links as $link) {
            if (preg_match(sprintf('/%s/i', $linkTitle), $link->getText())) {
                return $link;
            }
        }

        return null;
    }

    public function deleteAllContexts()
    {
        foreach ($this->getContexts() as $context) {
            $context->find('css', 'i.icon-remove')->click();
        }
    }

    public function hasContext($text)
    {
        foreach ($this->getContexts() as $context) {
            if (false !== stripos($context->getText(), $text)) {
                return;
            }
        }

        self::fail(sprintf('Context with "%s" name not found', $text));
    }

    /**
     * @param TableNode $table
     */
    public function addComment(TableNode $table)
    {
        $addCommentButton = $this->find('css', '.add-comment-button');
        self::assertNotNull($addCommentButton, 'Can\'t find Add comment button');
        $addCommentButton->press();
        $this->getDriver()->waitForAjax();

        /** @var Form $form */
        $form = $this->elementFactory->createElement('Comment Form');
        $form->fill($table);
        $this->getPage()->find('css', '.ui-dialog-buttonpane .btn-primary')->press();
    }

    /**
     * @param string $comment
     * @param TableNode $table
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     */
    public function editComment($comment, TableNode $table)
    {
        /** @var CommentItem $commentItem */
        $commentItem = $this->elementFactory->findElementContains('CommentItem', $comment);
        self::assertNotNull($commentItem, sprintf('Comment with "%s" text not found', $comment));

        $commentItem->clickActionLink('Update Comment');

        /** @var Form $form */
        $form = $this->elementFactory->createElement('Comment Form');
        $form->fill($table);
        $this->getPage()->find('css', '.ui-dialog-buttonpane .btn-primary')->press();
    }

    /**
     * @param string $comment
     */
    public function deleteComment($comment)
    {
        /** @var CommentItem $commentItem */
        $commentItem = $this->elementFactory->findElementContains('CommentItem', $comment);
        self::assertNotNull($commentItem, sprintf('Comment with "%s" text not found', $comment));

        $commentItem->clickActionLink('Delete Comment');

        $this->getPage()->clickLink('Yes, Delete');
        $this->getDriver()->waitForAjax();
    }

    /**
     * @return NodeElement[]
     */
    protected function getContexts()
    {
        return $this->findAll('css', 'div.activity-context-activity-list div.context-item');
    }
}
