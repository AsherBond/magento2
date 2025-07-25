<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
namespace Magento\Backend\Ui\Component\Control;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Ui\Component\Control\Container;

/**
 * Represents split-button with pre-configured options
 * Provide an ability to show drop-down list with options clicking on the "Save" button
 *
 * @api
 * @since 101.0.0
 */
class SaveSplitButton implements ButtonProviderInterface
{
    /**
     * @var string
     */
    private $targetName;

    /**
     * @param string $targetName
     */
    public function __construct(string $targetName)
    {
        $this->targetName = $targetName;
    }

    /**
     * {@inheritdoc}
     * @since 101.0.0
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save &amp; Continue'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => $this->targetName,
                                'actionName' => 'save',
                                'params' => [
                                    // first param is redirect flag
                                    false,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'class_name' => Container::SPLIT_BUTTON,
            'options' => $this->getOptions(),
            'sort_order' => 40,
        ];
    }

    /**
     * @return array
     */
    private function getOptions(): array
    {
        $options = [
            [
                'label' => __('Save &amp; Close'),
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => $this->targetName,
                                    'actionName' => 'save',
                                    'params' => [
                                        // first param is redirect flag
                                        true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sort_order' => 10,
            ],
            [
                'label' => __('Save &amp; New'),
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => $this->targetName,
                                    'actionName' => 'save',
                                    'params' => [
                                        // first param is redirect flag, second is data that will be added to post
                                        // request
                                        true,
                                        [
                                            'redirect_to_new' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sort_order' => 20,
            ],
        ];
        return $options;
    }
}
