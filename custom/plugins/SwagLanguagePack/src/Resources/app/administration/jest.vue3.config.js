const { join, resolve } = require('path');

const artifactsPath = process.env.ARTIFACTS_PATH ? join(process.env.ARTIFACTS_PATH, '/build/artifacts/jest') : 'coverage';

// declare fallback for default setup
process.env.ADMIN_PATH = process.env.ADMIN_PATH || join(__dirname, '../../../../../../../src/Administration/Resources/app/administration');

module.exports = {
    displayName: {
        name: 'LanguagePack Administration',
        color: 'lime'
    },

    preset: './node_modules/@shopware-ag/jest-preset-sw6-admin/jest-preset.js',
    globals: {
        adminPath: process.env.ADMIN_PATH,
    },

    rootDir: './',

    moduleDirectories:[
        '<rootDir>/node_modules',
        resolve(join(process.env.ADMIN_PATH, '/node_modules')),
    ],

    testMatch: [
        '<rootDir>/test/**/*.spec.vue3.js',
    ],

    collectCoverage: true,

    coverageDirectory: artifactsPath,

    coverageReporters: [
        'text',
        'cobertura',
        'html-spa',
    ],

    collectCoverageFrom: [
        '<rootDir>/src/**/Resources/app/administration/src/**/*.js',
        '<rootDir>/src/**/Resources/app/administration/src/**/*.ts',
    ],

    coverageProvider: 'v8',

    reporters: [
        'default',
        ['./node_modules/jest-junit/index.js', {
            suiteName: 'LanguagePack Administration',
            outputDirectory: artifactsPath,
            outputName: 'administration.junit.xml',
        }],
    ],

    setupFilesAfterEnv: [
        resolve(join(process.env.ADMIN_PATH, '/test/_setup/prepare_vue3_environment.js')),
    ],

    moduleNameMapper: {
        '^\@shopware-ag\/admin-extension-sdk\/es\/(.*)': resolve(join(process.env.ADMIN_PATH, '/node_modules')) + '/@shopware-ag/admin-extension-sdk/umd/$1',
        '^@administration(.*)$': `${process.env.ADMIN_PATH}/src$1`,
        vue$: '@vue/compat/dist/vue.cjs.js',
    },

    transformIgnorePatterns: [
        '/node_modules/(?!(uuidv7|other)/)',
    ],

    testEnvironmentOptions: {
        customExportConditions: ['node', 'node-addons'],
    },
};
