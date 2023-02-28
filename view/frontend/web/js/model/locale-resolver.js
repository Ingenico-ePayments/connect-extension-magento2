define([], function () {
    "use strict"

    return {
        /**
         * Port of \Worldline\Connect\Locale\Resolver::getBaseLocale() to
         * reflect to locale changing functionality
         *
         * @param {String} locale
         * @returns {String}
         */
        getBaseLocale: function (locale) {
            locale = locale.replace(/-/, '_', locale);

            let parts = locale.split('_');
            if (parts.length === 2 && locale.length <= 6) {
                return locale;
            }

            if (parts.length > 2) {
                let region = parts[parts.length - 1];
                if (region.length > 2) {
                    region = parts[parts.length - 2];
                }

                return parts[0] + '_' + region;
            }

            throw 'Invalid locale';
        }
    };
})
