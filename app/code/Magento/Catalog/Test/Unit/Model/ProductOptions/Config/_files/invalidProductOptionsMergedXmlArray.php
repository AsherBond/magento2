<?php
/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

return [
    'options_node_is_required' => [
        '<?xml version="1.0"?><config><inputType name="name_one" label="Label One"/></config>',
        [
            "Element 'inputType': This element is not expected. Expected is ( option ).\nLine: 1\nThe xml was: \n" .
            "0:<?xml version=\"1.0\"?>\n1:<config><inputType name=\"name_one\" label=\"Label One\"/></config>\n2:\n"
        ],
    ],
    'inputType_node_is_required' => [
        '<?xml version="1.0"?><config><option name="name_one" label="Label One" renderer="one"/></config>',
        [
            "Element 'option': Missing child element(s). Expected is ( inputType ).\nLine: 1\nThe xml was: \n" .
            "0:<?xml version=\"1.0\"?>\n1:<config><option name=\"name_one\" label=\"Label One\" renderer=\"one\"/>" .
            "</config>\n2:\n"
        ],
    ],
    'options_node_without_required_attributes' => [
        '<?xml version="1.0"?><config><option name="name_one" label="label one"><inputType name="name" label="one"/>' .
        '</option><option name="name_two" renderer="renderer"><inputType name="name_two" label="one" /></option>' .
        '<option label="label three" renderer="renderer"><inputType name="name_one" label="one"/></option></config>',
        [
            "Element 'option': The attribute 'renderer' is required but missing.\nLine: 1\nThe xml was: \n" .
            "0:<?xml version=\"1.0\"?>\n1:<config><option name=\"name_one\" label=\"label one\"><inputType " .
            "name=\"name\" label=\"one\"/></option><option name=\"name_two\" renderer=\"renderer\"><inputType " .
            "name=\"name_two\" label=\"one\"/></option><option label=\"label three\" renderer=\"renderer\">" .
            "<inputType name=\"name_one\" label=\"one\"/></option></config>\n2:\n",
            "Element 'option': The attribute 'label' is required but missing.\nLine: 1\nThe xml was: \n" .
            "0:<?xml version=\"1.0\"?>\n1:<config><option name=\"name_one\" label=\"label one\"><inputType " .
            "name=\"name\" label=\"one\"/></option><option name=\"name_two\" renderer=\"renderer\"><inputType " .
            "name=\"name_two\" label=\"one\"/></option><option label=\"label three\" renderer=\"renderer\">" .
            "<inputType name=\"name_one\" label=\"one\"/></option></config>\n2:\n",
            "Element 'option': The attribute 'name' is required but missing.\nLine: 1\nThe xml was: \n" .
            "0:<?xml version=\"1.0\"?>\n1:<config><option name=\"name_one\" label=\"label one\"><inputType " .
            "name=\"name\" label=\"one\"/></option><option name=\"name_two\" renderer=\"renderer\"><inputType " .
            "name=\"name_two\" label=\"one\"/></option><option label=\"label three\" renderer=\"renderer\">" .
            "<inputType name=\"name_one\" label=\"one\"/></option></config>\n2:\n"
        ],
    ],
    'inputType_node_without_required_attributes' => [
        '<?xml version="1.0"?><config><option name="name_one" label="label one" renderer="renderer">' .
        '<inputType name="name_one"/></option><option name="name_two" renderer="renderer" label="label">' .
        '<inputType label="name_two"/></option></config>',
        [
            "Element 'inputType': The attribute 'label' is required but missing.\nLine: 1\nThe xml was: \n" .
            "0:<?xml version=\"1.0\"?>\n1:<config><option name=\"name_one\" label=\"label one\" " .
            "renderer=\"renderer\"><inputType name=\"name_one\"/></option><option name=\"name_two\" " .
            "renderer=\"renderer\" label=\"label\"><inputType label=\"name_two\"/></option></config>\n2:\n",
            "Element 'inputType': The attribute 'name' is required but missing.\nLine: 1\nThe xml was: \n" .
            "0:<?xml version=\"1.0\"?>\n1:<config><option name=\"name_one\" label=\"label one\" " .
            "renderer=\"renderer\"><inputType name=\"name_one\"/></option><option name=\"name_two\" " .
            "renderer=\"renderer\" label=\"label\"><inputType label=\"name_two\"/></option></config>\n2:\n"
        ],
    ]
];
