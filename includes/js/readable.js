(function ($) {
    /*
    https://github.com/aramkocharyan/readable-color/
    Released under the MIT licence.
    */

    /**
     * Returns either black or white to ensure this color is distinguishable with the given RGB hex.
     * This function can be used to create a readable foreground color given a background color, or vice versa.
     * It forms a radius around white where black is returned. Outside this radius, white is returned.
     *
     * @param hex An RGB hex (e.g. "#FFFFFF")
     * @requires jQuery and TinyColor
     * @param args The argument object. Properties:
     *      amount: a value in the range [0,1]. If the distance of the given hex from white exceeds this value,
     *          white is returned. Otherwise, black is returned.
     *      xMulti: a multiplier to the distance in the x-axis.
     *      yMulti: a multiplier to the distance in the y-axis.
     *      normalizeHue: either falsey or an [x,y] array range. If hex is a colour with hue in this range,
     *          then normalizeHueXMulti and normalizeHueYMulti are applied.
     *      normalizeHueXMulti: a multiplier to the distance in the x-axis if hue is normalized.
     *      normalizeHueYMulti: a multiplier to the distance in the y-axis if hue is normalized.
     * @return the RGB hex string of black or white.
     */
    getReadableColor = function (hex, args) {
        args = $.extend({
            amount: 0.5,
            xMulti: 1,
            // We want to achieve white a bit sooner in the y axis
            yMulti: 1.5,
            normalizeHue: [20, 180],
            // For colors that appear lighter (yellow, green, light blue) we reduce the distance in the x direction,
            // stretching the radius in the x axis allowing more black than before.
            normalizeHueXMulti: 1 / 2.5,
            normalizeHueYMulti: 1
        }, args);
        var color = tinycolor(hex);
        var hsv = color.toHsv();
        // Origin is white
        var coord = {x: hsv.s, y: 1 - hsv.v};
        // Multipliers
        coord.x *= args.xMulti;
        coord.y *= args.yMulti;
        if (args.normalizeHue && hsv.h > args.normalizeHue[0] && hsv.h < args.normalizeHue[1]) {
            coord.x *= args.normalizeHueXMulti;
            coord.y *= args.normalizeHueYMulti;
        }
        var dist = Math.sqrt(Math.pow(coord.x, 2) + Math.pow(coord.y, 2));
        if (dist < args.amount) {
            hsv.v = 0; // black
        } else {
            hsv.v = 1; // white
        }
        hsv.s = 0;
        return tinycolor(hsv).toHexString();
    };

})(jQuery);
