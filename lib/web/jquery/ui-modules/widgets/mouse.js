/*!
 * jQuery UI Mouse 1.14.1
 * https://jqueryui.com
 *
 * Copyright OpenJS Foundation and other contributors
 * Released under the MIT license.
 * https://jquery.org/license
 */

//>>label: Mouse
//>>group: Widgets
//>>description: Abstracts mouse-based interactions to assist in creating certain widgets.
//>>docs: https://api.jqueryui.com/mouse/

( function( factory ) {
    "use strict";

    if ( typeof define === "function" && define.amd ) {

        // AMD. Register as an anonymous module.
        define( [
            "jquery",
            "../version",
            "../widget"
        ], factory );
    } else {

        // Browser globals
        factory( jQuery );
    }
} )( function( $ ) {
    "use strict";

    var mouseHandled = false;
    $( document ).on( "mouseup", function() {
        mouseHandled = false;
    } );

    return $.widget( "ui.mouse", {
        version: "1.14.1",
        options: {
            cancel: "input, textarea, button, select, option",
            distance: 1,
            delay: 0
        },
        _mouseInit: function() {
            var that = this;

            this.element
                .on( "mousedown." + this.widgetName, function( event ) {
                    return that._mouseDown( event );
                } )
                .on( "click." + this.widgetName, function( event ) {
                    if ( true === $.data( event.target, that.widgetName + ".preventClickEvent" ) ) {
                        $.removeData( event.target, that.widgetName + ".preventClickEvent" );
                        event.stopImmediatePropagation();
                        return false;
                    }
                } );

            this.started = false;
        },

        // TODO: make sure destroying one instance of mouse doesn't mess with
        // other instances of mouse
        _mouseDestroy: function() {
            this.element.off( "." + this.widgetName );
            if ( this._mouseMoveDelegate ) {
                this.document
                    .off( "mousemove." + this.widgetName, this._mouseMoveDelegate )
                    .off( "mouseup." + this.widgetName, this._mouseUpDelegate );
            }
        },

        _mouseDown: function( event ) {

            // don't let more than one widget handle mouseStart
            if ( mouseHandled ) {
                return;
            }

            this._mouseMoved = false;

            // We may have missed mouseup (out of window)
            if ( this._mouseStarted ) {
                this._mouseUp( event );
            }

            this._mouseDownEvent = event;

            var that = this,
                btnIsLeft = event.which === 1,
                elIsCancel = typeof this.options.cancel === "string" ?
                    $( event.target ).closest( this.options.cancel ).length :
                    false;
            if ( !btnIsLeft || elIsCancel || !this._mouseCapture( event ) ) {
                return true;
            }

            this.mouseDelayMet = !this.options.delay;
            if ( !this.mouseDelayMet ) {
                this._mouseDelayTimer = setTimeout( function() {
                    that.mouseDelayMet = true;
                }, this.options.delay );
            }

            if ( this._mouseDistanceMet( event ) && this._mouseDelayMet( event ) ) {
                this._mouseStarted = ( this._mouseStart( event ) !== false );
                if ( !this._mouseStarted ) {
                    event.preventDefault();
                    return true;
                }
            }

            // Click event may never have fired (Gecko & Opera)
            if ( true === $.data( event.target, this.widgetName + ".preventClickEvent" ) ) {
                $.removeData( event.target, this.widgetName + ".preventClickEvent" );
            }

            // These delegates are required to keep context
            this._mouseMoveDelegate = function( event ) {
                return that._mouseMove( event );
            };
            this._mouseUpDelegate = function( event ) {
                return that._mouseUp( event );
            };

            this.document
                .on( "mousemove." + this.widgetName, this._mouseMoveDelegate )
                .on( "mouseup." + this.widgetName, this._mouseUpDelegate );

            event.preventDefault();

            mouseHandled = true;
            return true;
        },

        _mouseMove: function( event ) {

            // Only check for mouseups outside the document if you've moved inside the document
            // at least once.
            if ( this._mouseMoved && !event.which ) {

                // Support: Safari <=8 - 9
                // Safari sets which to 0 if you press any of the following keys
                // during a drag (#14461)
                if ( event.originalEvent.altKey || event.originalEvent.ctrlKey ||
                    event.originalEvent.metaKey || event.originalEvent.shiftKey ) {
                    this.ignoreMissingWhich = true;
                } else if ( !this.ignoreMissingWhich ) {
                    return this._mouseUp( event );
                }
            }

            if ( event.which || event.button ) {
                this._mouseMoved = true;
            }

            if ( this._mouseStarted ) {
                this._mouseDrag( event );
                return event.preventDefault();
            }

            if ( this._mouseDistanceMet( event ) && this._mouseDelayMet( event ) ) {
                this._mouseStarted =
                    ( this._mouseStart( this._mouseDownEvent, event ) !== false );
                if ( this._mouseStarted ) {
                    this._mouseDrag( event );
                } else {
                    this._mouseUp( event );
                }
            }

            return !this._mouseStarted;
        },

        _mouseUp: function( event ) {
            this.document
                .off( "mousemove." + this.widgetName, this._mouseMoveDelegate )
                .off( "mouseup." + this.widgetName, this._mouseUpDelegate );

            if ( this._mouseStarted ) {
                this._mouseStarted = false;

                if ( event.target === this._mouseDownEvent.target ) {
                    $.data( event.target, this.widgetName + ".preventClickEvent", true );
                }

                this._mouseStop( event );
            }

            if ( this._mouseDelayTimer ) {
                clearTimeout( this._mouseDelayTimer );
                delete this._mouseDelayTimer;
            }

            this.ignoreMissingWhich = false;
            mouseHandled = false;
            event.preventDefault();
        },

        _mouseDistanceMet: function( event ) {
            return ( Math.max(
                    Math.abs( this._mouseDownEvent.pageX - event.pageX ),
                    Math.abs( this._mouseDownEvent.pageY - event.pageY )
                ) >= this.options.distance
            );
        },

        _mouseDelayMet: function( /* event */ ) {
            return this.mouseDelayMet;
        },

        // These are placeholder methods, to be overriden by extending plugin
        _mouseStart: function( /* event */ ) {},
        _mouseDrag: function( /* event */ ) {},
        _mouseStop: function( /* event */ ) {},
        _mouseCapture: function( /* event */ ) {
            return true;
        }
    } );

} );
