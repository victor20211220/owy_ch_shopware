const { join, resolve } = require('path');

module.exports = () => {
    return {
        resolve: {
            alias: {
                '@ottBase': resolve(
                    join(__dirname, '..', 'src'),
                ),

                '@tablesort': resolve(
                    join(__dirname, '..', 'node_modules', 'tablesort'),
                ),

                '@custom-select': resolve(
                    join(__dirname, '..', 'node_modules', 'custom-select'),
                ),

                'custom-event-polyfill': resolve(
                    join(__dirname, '..', 'node_modules', 'custom-event-polyfill'),
                ),

                '@DocumentHelper': resolve(
                    join(__dirname, '..', 'src', 'helper', 'document.helper'),
                ),

                '@ElementHelper': resolve(
                    join(__dirname, '..', 'src', 'helper', 'element.helper'),
                ),

                '@FadeHelper': resolve(
                    join(__dirname, '..', 'src', 'helper', 'fade.helper'),
                ),

                '@UrlHelper': resolve(
                    join(__dirname, '..', 'src', 'helper', 'url.helper'),
                ),
            },
        },
    };
};
