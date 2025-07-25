/*!
 * jQuery UI Effects Shake 1.14.1
 * https://jqueryui.com
 *
 * Copyright OpenJS Foundation and other contributors
 * Released under the MIT license.
 * https://jquery.org/license
 */

//>>label: Shake Effect
//>>group: Effects
//>>description: Shakes an element horizontally or vertically n times.
//>>docs: https://api.jqueryui.com/shake-effect/
//>>demos: https://jqueryui.com/effect/

( function( factory ) {
    "use strict";

    if ( typeof define === "function" && define.amd ) {

        // AMD. Register as an anonymous module.
        define( [
            "jquery",
            "../version",
            "../effect"
        ], factory );
    } else {

        // Browser globals
        factory( jQuery );
    }
} )( function( $ ) {
    "use strict";

    return $.effects.define( "shake", function( options, done ) {

        var i = 1,
            element = $( this ),
            direction = options.direction || "left",
            distance = options.distance || 20,
            times = options.times || 3,
            anims = times * 2 + 1,
            speed = Math.round( options.duration / anims ),
            ref = ( direction === "up" || direction === "down" ) ? "top" : "left",
            positiveMotion = ( direction === "up" || direction === "left" ),
            animation = {},
            animation1 = {},
            animation2 = {},

            queuelen = element.queue().length;

        $.effects.createPlaceholder( element );

        // Animation
        animation[ ref ] = ( positiveMotion ? "-=" : "+=" ) + distance;
        animation1[ ref ] = ( positiveMotion ? "+=" : "-=" ) + distance * 2;
        animation2[ ref ] = ( positiveMotion ? "-=" : "+=" ) + distance * 2;

        // Animate
        element.animate( animation, speed, options.easing );

        // Shakes
        for ( ; i < times; i++ ) {
            element
                .animate( animation1, speed, options.easing )
                .animate( animation2, speed, options.easing );
        }

        element
            .animate( animation1, speed, options.easing )
            .animate( animation, speed / 2, options.easing )
            .queue( done );

        $.effects.unshift( element, queuelen, anims + 1 );
    } );

} );
