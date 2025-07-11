/**
 * Copyright 2014 Adobe
 * All Rights Reserved.
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Ui/js/lib/view/utils/async',
    'jquery/ui',
    'mage/translate',
    'prototype',
    'Magento_Ui/js/modal/modal'
], function (jQuery) {

    window.ProductConfigure = Class.create();

    ProductConfigure.prototype = {

        listTypes:                  $H({}),
        current:                    $H({}),
        itemsFilter:                $H({}),
        blockWindow:                null,
        blockForm:                  null,
        blockFormFields:            null,
        blockFormAdd:               null,
        blockFormConfirmed:         null,
        blockConfirmed:             null,
        blockIFrame:                null,
        blockCancelBtn:             null,
        blockMask:                  null,
        blockMsg:                   null,
        blockMsgError:              null,
        windowHeight:               null,
        confirmedCurrentId:         null,
        confirmCallback:            {},
        cancelCallback:             {},
        onLoadIFrameCallback:       {},
        showWindowCallback:         {},
        beforeSubmitCallback:       {},
        iFrameJSVarname:            null,
        _listTypeId:                1,

        /**
         * Initialize object
         */
        initialize: function () {
            var self = this,
                popupDialog = jQuery('#product_composite_configure');

            this._initWindowElements();
            jQuery.async('#product_composite_configure', function (el) {
                if (el !== popupDialog[0]) {
                    el = popupDialog[0];
                }
                self.dialog = jQuery(el).modal({
                    title: jQuery.mage.__('Configure Product'),
                    type: 'slide',
                    buttons: [{
                        text: jQuery.mage.__('OK'),
                        'class': 'action-primary',
                        click: function () {
                            self.onConfirmBtn();
                        }
                    }],
                    closed: function () {
                        self.clean('window');
                    },
                });
            });
        },

        /**
         * Initialize window elements
         */
        _initWindowElements: function () {
            this.blockWindow                = $('product_composite_configure');
            this.blockForm                  = $('product_composite_configure_form');
            this.blockFormFields            = $('product_composite_configure_form_fields');
            this.blockFormAdd               = $('product_composite_configure_form_additional');
            this.blockFormConfirmed         = $('product_composite_configure_form_confirmed');
            this.blockConfirmed             = $('product_composite_configure_confirmed');
            this.blockIFrame                = $('product_composite_configure_iframe');
            this.blockCancelBtn             = $('product_composite_configure_form_cancel');
            this.blockMsg                   = $('product_composite_configure_messages');
            this.blockMsgError              = this.blockMsg.select('.message.error div')[0];
            this.iFrameJSVarname            = this.blockForm.select('input[name="as_js_varname"]')[0].value;
        },

        /**
         * Returns next unique list type id
         */
        _generateListTypeId: function () {
            return '_internal_lt_' + this._listTypeId++;
        },

        /**
         * Add product list types as scope and their urls
         * example: addListType('product_to_add', {urlFetch: 'http://magento...'})
         * example: addListType('wishlist', {urlSubmit: 'http://magento...'})
         *
         * @param type types as scope
         * @param urls obj can be
         *             - {urlFetch: 'http://magento...'} for fetching configuration fields through ajax
         *             - {urlConfirm: 'http://magento...'} for submit configured data through iFrame when clicked confirm button
         *             - {urlSubmit: 'http://magento...'} for submit configured data through iFrame
         */
        addListType: function (type, urls) {
            if ('undefined' == typeof this.listTypes[type]) {
                this.listTypes[type] = {};
            }
            Object.extend(this.listTypes[type], urls);

            return this;
        },

        /**
         * Adds complex list type - that is used to submit several list types at once
         * Only urlSubmit is possible for this list type
         * example: addComplexListType(['wishlist', 'product_list'], 'http://magento...')
         *
         * @param type types as scope
         * @param urls obj can be
         *             - {urlSubmit: 'http://magento...'} for submit configured data through iFrame
         * @return type string
         */
        addComplexListType: function (types, urlSubmit) {
            var type = this._generateListTypeId();

            this.listTypes[type] = {};
            this.listTypes[type].complexTypes = types;
            this.listTypes[type].urlSubmit = urlSubmit;

            return type;
        },

        /**
         * Add filter of items
         *
         * @param listType scope name
         * @param itemsFilter
         */
        addItemsFilter: function (listType, itemsFilter) {
            if (!listType || !itemsFilter) {
                return false;
            }

            if ('undefined' == typeof this.itemsFilter[listType]) {
                this.itemsFilter[listType] = [];
            }
            this.itemsFilter[listType] = this.itemsFilter[listType].concat(itemsFilter);

            return this;
        },

        /**
         * Returns id of block where configuration for an item is stored
         *
         * @param listType scope name
         * @param itemId
         * @return string
         */
        _getConfirmedBlockId: function (listType, itemId) {
            return this.blockConfirmed.id + '[' + listType + '][' + itemId + ']';
        },

        /**
         * Checks whether item has some configuration fields
         *
         * @param listType scope name
         * @param itemId
         * @return bool
         */
        itemConfigured: function (listType, itemId) {
            var confirmedBlockId = this._getConfirmedBlockId(listType, itemId);
            var itemBlock = $(confirmedBlockId);

            return !!(itemBlock && itemBlock.innerHTML);
        },

        /**
         * Show configuration fields of item, if it not found then get it through ajax
         *
         * @param listType scope name
         * @param itemId
         */
        showItemConfiguration: function (listType, itemId) {
            if (!listType || !itemId) {
                return false;
            }

            this.initialize();
            this.current.listType = listType;
            this.current.itemId = itemId;
            this.confirmedCurrentId = this._getConfirmedBlockId(listType, itemId);

            if (!this.itemConfigured(listType, itemId)) {
                this._requestItemConfiguration(listType, itemId);
            } else {
                this._processFieldsData('item_restore');
                this._showWindow();
            }
        },

        /**
         * Get configuration fields of product through ajax and show them
         *
         * @param listType scope name
         * @param itemId
         */
        _requestItemConfiguration: function (listType, itemId) {
            if (!this.listTypes[listType].urlFetch) {
                return false;
            }
            var url = this.listTypes[listType].urlFetch;

            if (url) {
                new Ajax.Request(url, {
                    parameters: {
                        id: itemId
                    },
                    onSuccess: function (transport) {
                        var response = transport.responseText;

                        if (response.isJSON()) {
                            response = response.evalJSON();

                            if (response.error) {
                                this.blockMsg.show();
                                this.blockMsgError.innerHTML = response.message;
                                if(this.blockCancelBtn) {
                                    this.blockCancelBtn.hide();
                                }
                                this.setConfirmCallback(listType, null);
                                this._showWindow();
                            }
                        } else if (response) {
                            response += '';
                            this.blockFormFields.update(response);

                            // Add special div to hold mage data, e.g. scripts to execute on every popup show
                            var mageData = {};
                            var scripts = response.extractScripts();

                            mageData.scripts = scripts;

                            var scriptHolder = new Element('div', {
                                'style': 'display:none'
                            });

                            scriptHolder.mageData = mageData;
                            this.blockFormFields.insert(scriptHolder);

                            // Show window
                            this._showWindow();
                        }
                    }.bind(this)
                });
            }
        },

        /**
         * Triggered on confirm button click
         * Do submit configured data through iFrame if needed
         */
        onConfirmBtn: function () {
            if (jQuery(this.blockForm).valid()) {
                if (this.listTypes[this.current.listType].urlConfirm) {
                    this.submit();
                } else {
                    this._processFieldsData('item_confirm');
                    this._closeWindow();

                    if (Object.isFunction(this.confirmCallback[this.current.listType])) {
                        this.confirmCallback[this.current.listType]();
                    }
                }
            }

            return this;
        },

        /**
         * Triggered on cancel button click
         */
        onCancelBtn: function () {
            this._closeWindow();

            if (Object.isFunction(this.cancelCallback[this.current.listType])) {
                this.cancelCallback[this.current.listType]();
            }

            return this;
        },

        /**
         * Submit configured data through iFrame
         *
         * @param listType scope name
         */
        submit: function (listType) {
            // prepare data
            if (listType) {
                this.current.listType = listType;
                this.current.itemId = null;
            }
            var urlConfirm = this.listTypes[this.current.listType].urlConfirm;
            var urlSubmit = this.listTypes[this.current.listType].urlSubmit;

            if (!urlConfirm && !urlSubmit) {
                return false;
            }

            if (urlConfirm) {
                this.blockForm.action = urlConfirm;
                this.addFields([new Element('input', {
                    type: 'hidden', name: 'id', value: this.current.itemId
                })]);
            } else {
                this.blockForm.action = urlSubmit;

                var complexTypes = this.listTypes[this.current.listType].complexTypes;

                if (complexTypes) {
                    this.addFields([new Element('input', {
                        type: 'hidden', name: 'configure_complex_list_types', value: complexTypes.join(',')
                    })]);
                }

                this._processFieldsData('current_confirmed_to_form');

                // Disable item controls that duplicate added fields (e.g. sometimes qty controls can intersect)
                // so they won't be submitted
                var tagNames = ['input', 'select', 'textarea'];

                var names = {}; // Map of added field names

                for (var i = 0, len = tagNames.length; i < len; i++) {
                    var tagName = tagNames[i];
                    var elements = this.blockFormAdd.getElementsByTagName(tagName);

                    for (var index = 0, elLen = elements.length; index < elLen; index++) {
                        names[elements[index].name] = true;
                    }
                }

                for (var i = 0, len = tagNames.length; i < len; i++) {
                    var tagName = tagNames[i];
                    var elements = this.blockFormConfirmed.getElementsByTagName(tagName);

                    for (var index = 0, elLen = elements.length; index < elLen; index++) {
                        var element = elements[index];

                        if (names[element.name]) {
                            element.setAttribute('configure_disabled', 1);
                            element.setAttribute('configure_prev_disabled', element.disabled ? 1 : 0);
                            element.disabled = true;
                        } else {
                            element.setAttribute('configure_disabled', 0);
                        }
                    }
                }
            }
            // do submit
            if (Object.isFunction(this.beforeSubmitCallback[this.current.listType])) {
                this.beforeSubmitCallback[this.current.listType]();
            }
            this.blockForm.submit();

            // Show loader
            jQuery(this.blockForm).trigger('processStart');

            return this;
        },

        /**
         * Add dynamically additional fields for form
         *
         * @param fields
         */
        addFields: function (fields) {
            fields.each(function (elm) {
                this.blockFormAdd.insert(elm);
            }.bind(this));

            return this;
        },

        /**
         * Triggered when form was submitted and iFrame was loaded. Get response from iFrame and handle it
         */
        onLoadIFrame: function () {
            this.blockFormConfirmed.select('[configure_disabled=1]').each(function (element) {
                element.disabled = element.getAttribute('configure_prev_disabled') == '1';
            });

            this._processFieldsData('form_confirmed_to_confirmed');

            var response = this.blockIFrame.contentWindow[this.iFrameJSVarname];

            if (response && 'object' == typeof response) {
                if (this.listTypes[this.current.listType].urlConfirm) {
                    if (response.ok) {
                        this._closeWindow();
                        this.clean('current');
                    } else if (response.error) {
                        this.showItemConfiguration(this.current.listType, this.current.itemId);
                        this.blockMsg.show();
                        this.blockMsgError.innerHTML = response.message;
                        this._showWindow();

                        jQuery(this.blockForm).trigger('processStop');
                        return false;
                    }
                }

                if (Object.isFunction(this.onLoadIFrameCallback[this.current.listType])) {
                    this.onLoadIFrameCallback[this.current.listType](response);
                }
                document.fire(this.current.listType + ':afterIFrameLoaded');
            }
            // Hide loader
            jQuery(this.blockForm).trigger('processStop');

            this.clean('current');
            this.initialize();
        },

        /**
         * Helper for fetching content from iFrame
         */
        _getIFrameContent: function () {
            var content = this.blockIFrame.contentWindow || this.blockIFrame.contentDocument;

            if (content.document) {
                content = content.document;
            }

            return content;
        },

        /**
         * Helper to find qty of currently confirmed item
         */
        getCurrentConfirmedQtyElement: function () {
            var elms = $(this.confirmedCurrentId).getElementsByTagName('input');

            for (var i = 0; i < elms.length; i++) {
                if (elms[i].name == 'qty') {
                    return elms[i];
                }
            }
        },

        /**
         * Helper to find select element of currently confirmed item
         */
        getCurrentConfirmedSelectElement: function () {
            return $(this.confirmedCurrentId).getElementsByTagName('select');
        },

        /**
         * Helper to find qty of active form
         */
        getCurrentFormQtyElement: function () {
            var elms = this.blockFormFields.getElementsByTagName('input');

            for (var i = 0; i < elms.length; i++) {
                if (elms[i].name == 'qty') {
                    return elms[i];
                }
            }
        },

        /**
         * Show configuration window
         */
        _showWindow: function () {
            this.dialog.modal('openModal');
            //this._toggleSelectsExceptBlock(false);

            if (Object.isFunction(this.showWindowCallback[this.current.listType])) {
                this.showWindowCallback[this.current.listType]();
            }
        },

        /**
         * Close configuration window
         */
        _closeWindow: function () {
            this.dialog.modal('closeModal');
            //this.blockWindow.style.display = 'none';
            //this.clean('window');
        },

        /**
         * Attach callback function triggered when confirm button was clicked
         *
         * @param confirmCallback
         */
        setConfirmCallback: function (listType, confirmCallback) {
            this.confirmCallback[listType] = confirmCallback;

            return this;
        },

        /**
         * Attach callback function triggered when cancel button was clicked
         *
         * @param cancelCallback
         */
        setCancelCallback: function (listType, cancelCallback) {
            this.cancelCallback[listType] = cancelCallback;

            return this;
        },

        /**
         * Attach callback function triggered when iFrame was loaded
         *
         * @param onLoadIFrameCallback
         */
        setOnLoadIFrameCallback: function (listType, onLoadIFrameCallback) {
            this.onLoadIFrameCallback[listType] = onLoadIFrameCallback;

            return this;
        },

        /**
         * Attach callback function triggered when iFrame was loaded
         *
         * @param showWindowCallback
         */
        setShowWindowCallback: function (listType, showWindowCallback) {
            this.showWindowCallback[listType] = showWindowCallback;

            return this;
        },

        /**
         * Attach callback function triggered before submitting form
         *
         * @param beforeSubmitCallback
         */
        setBeforeSubmitCallback: function (listType, beforeSubmitCallback) {
            this.beforeSubmitCallback[listType] = beforeSubmitCallback;

            return this;
        },

        /**
         * Clean object data
         *
         * @param method can be 'all' or 'current'
         */
        clean: function (method) {
            var listInfo = null;
            var listTypes = null;
            var removeConfirmed = function (listTypes) {
                this.blockConfirmed.childElements().each(function (elm) {
                    for (var i = 0, len = listTypes.length; i < len; i++) {
                        var pattern = this.blockConfirmed.id + '[' + listTypes[i] + ']';

                        if (elm.id.indexOf(pattern) == 0) {
                            elm.remove();
                            break;
                        }
                    }
                }.bind(this));
            }.bind(this);

            switch (method) {
                case 'current':
                    listInfo = this.listTypes[this.current.listType];
                    listTypes = [this.current.listType];

                    if (listInfo && listInfo.complexTypes) {
                        listTypes = listTypes.concat(listInfo.complexTypes);
                    }
                    removeConfirmed(listTypes);
                    break;

                case 'window':
                    this.blockFormFields.update();
                    this.blockMsg.hide();
                    this.blockMsgError.update();
                    if(this.blockCancelBtn) {
                        this.blockCancelBtn.show();
                    }
                    break;
                default:
                    // search in list types for its cleaning
                    if (this.listTypes[method]) {
                        listInfo = this.listTypes[method];
                        listTypes = [method];

                        if (listInfo.complexTypes) {
                            listTypes = listTypes.concat(listInfo.complexTypes);
                        }
                        removeConfirmed(listTypes);
                        // clean all
                    } else if (!method) {
                        this.current = $H({});
                        this.blockConfirmed.update();
                        this.blockFormFields.update();
                        this.blockMsg.hide();
                        this.blockMsgError.update();
                        if(this.blockCancelBtn) {
                            this.blockCancelBtn.show();
                        }
                    }
                    break;
            }
            this._getIFrameContent().body.innerHTML = '';
            this.blockIFrame.contentWindow[this.iFrameJSVarname] = {};
            this.blockFormAdd.update();
            this.blockFormConfirmed.update();
            this.blockForm.action = '';

            return this;
        },

        /**
         * Process fields data: save, restore, move saved to form and back
         *
         * @param method can be 'item_confirm', 'item_restore', 'current_confirmed_to_form', 'form_confirmed_to_confirmed'
         */
        _processFieldsData: function (method) {
            var self = this;

            /**
             * Internal function for rename fields names of some list type
             * if listType is not specified, then it won't be added as prefix to all names
             *
             * @param method can be 'current_confirmed_to_form', 'form_confirmed_to_confirmed'
             * @param blockItem
             */
            var _renameFields = function (method, blockItem, listType) {
                var pattern           = null;
                var patternFlat       = null;
                var patternPrefix     = RegExp('\\s', 'g');
                var replacement       = null;
                var replacementFlat   = null;
                var replacementPrefix = '_';
                var scopeArr          = blockItem.id.match(/.*\[\w+\]\[([^\]]+)\]$/);
                var itemId            = scopeArr[1];

                if (method == 'current_confirmed_to_form') {
                    pattern         = RegExp('(\\w+)(\\[?)');
                    patternFlat     = RegExp('(\\w+)');
                    replacement     = 'item[' + itemId + '][$1]$2';
                    replacementFlat = 'item_' + itemId + '_$1';

                    if (listType) {
                        replacement = 'list[' + listType + '][item][' + itemId + '][$1]$2';
                        replacementFlat = 'list_' + listType + '_' + replacementFlat;
                    }
                } else if (method == 'form_confirmed_to_confirmed') {
                    var stPattern = 'item\\[' + itemId + '\\]\\[(\\w+)\\](.*)';
                    var stPatternFlat = 'item_' + itemId + '_(\\w+)';

                    if (listType) {
                        stPattern = 'list\\[' + listType + '\\]\\[item\\]\\[' + itemId + '\\]\\[(\\w+)\\](.*)';
                        stPatternFlat = 'list_' + listType + '_' + stPatternFlat;
                    }
                    pattern         = new RegExp(stPattern);
                    patternFlat     = new RegExp(stPatternFlat);
                    replacement     = '$1$2';
                    replacementFlat = '$1';
                } else {
                    return false;
                }
                var rename = function (elms) {
                    for (var i = 0; i < elms.length; i++) {
                        if (elms[i].name && elms[i].type == 'file') {
                            var prefixName = 'options[files_prefix]',
                                prefixValue = 'item_' + itemId + '_';

                            self.blockFormFields.insert(new Element('input', {
                                type: 'hidden',
                                name: prefixName.replace(pattern, replacement),
                                value: prefixValue.replace(patternPrefix, replacementPrefix)
                            }));
                            elms[i].name = elms[i].name.replace(patternFlat, replacementFlat);
                        } else if (elms[i].name) {
                            elms[i].name = elms[i].name.replace(pattern, replacement);
                        }
                    }
                };

                rename(blockItem.getElementsByTagName('input'));
                rename(blockItem.getElementsByTagName('select'));
                rename(blockItem.getElementsByTagName('textarea'));
            };

            switch (method) {
                case 'item_confirm':
                    if (!$(this.confirmedCurrentId)) {
                        this.blockConfirmed.insert(new Element('div', {
                            id: this.confirmedCurrentId
                        }));
                    } else {
                        $(this.confirmedCurrentId).update();
                    }
                    this.blockFormFields.childElements().each(function (elm) {
                        $(this.confirmedCurrentId).insert(elm);
                    }.bind(this));
                    break;

                case 'item_restore':
                    this.blockFormFields.update();

                    // clone confirmed to form
                    var mageData = null;

                    $(this.confirmedCurrentId).childElements().each(function (elm) {
                        var cloned = elm.cloneNode(true);

                        if (elm.mageData) {
                            cloned.mageData = elm.mageData;
                            mageData = elm.mageData;
                        }
                        this.blockFormFields.insert(cloned);
                    }.bind(this));

                    // get confirmed values
                    var fieldsValue = {};
                    var getConfirmedValues = function (elms) {
                        for (var i = 0; i < elms.length; i++) {
                            if (elms[i].name) {
                                if ('undefined' == typeof fieldsValue[elms[i].name]) {
                                    fieldsValue[elms[i].name] = {};
                                }

                                if (elms[i].type == 'checkbox') {
                                    fieldsValue[elms[i].name][elms[i].value] = elms[i].checked;
                                } else if (elms[i].type == 'radio') {
                                    if (elms[i].checked) {
                                        fieldsValue[elms[i].name] = elms[i].value;
                                    }
                                } else {
                                    fieldsValue[elms[i].name] = Form.Element.getValue(elms[i]);
                                }
                            }
                        }
                    };

                    getConfirmedValues($(this.confirmedCurrentId).getElementsByTagName('input'));
                    getConfirmedValues($(this.confirmedCurrentId).getElementsByTagName('select'));
                    getConfirmedValues($(this.confirmedCurrentId).getElementsByTagName('textarea'));

                    // restore confirmed values
                    var restoreConfirmedValues = function (elms) {
                        for (var i = 0; i < elms.length; i++) {
                            if ('undefined' != typeof fieldsValue[elms[i].name]) {
                                if (elms[i].type != 'file') {
                                    if (elms[i].type == 'checkbox') {
                                        elms[i].checked = fieldsValue[elms[i].name][elms[i].value];
                                    } else if (elms[i].type == 'radio') {
                                        if (elms[i].value == fieldsValue[elms[i].name]) {
                                            elms[i].checked = true;
                                        }
                                    } else {
                                        elms[i].setValue(fieldsValue[elms[i].name]);
                                    }
                                }
                            }
                        }
                    };

                    restoreConfirmedValues(this.blockFormFields.getElementsByTagName('input'));
                    restoreConfirmedValues(this.blockFormFields.getElementsByTagName('select'));
                    restoreConfirmedValues(this.blockFormFields.getElementsByTagName('textarea'));

                    // Execute scripts
                    if (mageData && mageData.scripts) {
                        this.restorePhase = true;

                        try {
                            mageData.scripts.map(function (script) {
                                return eval(script);
                            });
                        } catch (e) {

                        }
                        this.restorePhase = false;
                    }
                    break;

                case 'current_confirmed_to_form':
                    var allowedListTypes = {};

                    allowedListTypes[this.current.listType] = true;
                    var listInfo = this.listTypes[this.current.listType];

                    if (listInfo.complexTypes) {
                        for (var i = 0, len = listInfo.complexTypes.length; i < len; i++) {
                            allowedListTypes[listInfo.complexTypes[i]] = true;
                        }
                    }

                    this.blockFormConfirmed.update();
                    this.blockConfirmed.childElements().each(function (blockItem) {
                        var scopeArr    = blockItem.id.match(/.*\[(\w+)\]\[([^\]]+)\]$/);
                        var listType    = scopeArr[1];
                        var itemId    = scopeArr[2];

                        if (allowedListTypes[listType] && (!this.itemsFilter[listType] ||
                            this.itemsFilter[listType].indexOf(itemId) != -1)) {
                            _renameFields(method, blockItem, listInfo.complexTypes ? listType : null);
                            this.blockFormConfirmed.insert(blockItem);
                        }
                    }.bind(this));
                    break;

                case 'form_confirmed_to_confirmed':
                    var listInfo = this.listTypes[this.current.listType];

                    this.blockFormConfirmed.childElements().each(function (blockItem) {
                        var scopeArr = blockItem.id.match(/.*\[(\w+)\]\[([^\]]+)\]$/);
                        var listType = scopeArr[1];

                        _renameFields(method, blockItem, listInfo.complexTypes ? listType : null);
                        this.blockConfirmed.insert(blockItem);
                    }.bind(this));
                    break;
            }
        },

        /**
         * Check if qty selected correctly
         *
         * @param object element
         * @param object event
         */
        changeOptionQty: function (element, event) {
            var checkQty = true;

            if ('undefined' != typeof event) {
                if (event.keyCode == 8 || event.keyCode == 46) {
                    checkQty = false;
                }
            }

            if (checkQty && (Number(element.value) <= 0 || isNaN(Number(element.value)))) {
                element.value = 1;
            }
        }
    };

    productConfigure = new ProductConfigure();
    jQuery(document).trigger('productConfigure:inited');
    jQuery(document).data('productConfigureInited', true);
});
