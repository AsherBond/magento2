<?php
/**
 * Copyright 2011 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Sales\Model\Order\Invoice\Comment;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order\Invoice\Comment;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Helper\SalesEntityCommentValidator;

/**
 * Sales invoice comment validator
 */
class Validator
{
    /**
     * Sales entity comment validator
     * @var SalesEntityCommentValidator
     */
    private SalesEntityCommentValidator $helperValidator;

    /**
     * Required field
     *
     * @var array
     */
    protected $required = [
        'parent_id' => 'Parent Invoice Id',
        'comment' => 'Comment',
    ];

    /**
     * @param SalesEntityCommentValidator|null $helperValidator
     */
    public function __construct(
        ?SalesEntityCommentValidator $helperValidator = null
    ) {
        $this->helperValidator = $helperValidator ??
            ObjectManager::getInstance()->get(SalesEntityCommentValidator::class);
    }

    /**
     * Validate data
     *
     * @param Comment $comment
     * @return array
     * @throws CouldNotSaveException
     */
    public function validate(Comment $comment)
    {
        $errors = [];
        $commentData = $comment->getData();

        if (!$this->helperValidator->isEditCommentAllowed($comment)) {
            $errors['comment'] = sprintf('User is not authorized to edit comment.');
        }

        foreach ($this->required as $code => $label) {
            if (!$comment->hasData($code)) {
                $errors[$code] = sprintf('"%s" is required. Enter and try again.', $label);
            } elseif (empty($commentData[$code])) {
                $errors[$code] = sprintf('%s can not be empty', $label);
            }
        }
        return $errors;
    }
}
