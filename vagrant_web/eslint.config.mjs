import js from '@eslint/js';
import globals from 'globals';
import pluginVue from 'eslint-plugin-vue';

const warningRules = rules => Object.fromEntries(Object.entries(rules).map(([name, value]) => [
    name,
    Array.isArray(value) ? ['warn', ...value.slice(1)] : 'warn',
]));

const vue2EssentialRules = Object.assign(
    {},
    ...pluginVue.configs['flat/vue2-essential'].map(config => config.rules || {}),
);

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/vue2-essential'],
    {
        files: ['resources/js/**/*.{js,vue}', 'scripts/**/*.mjs', 'tests/js/**/*.js', '*.config.mjs'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
                axios: 'readonly',
                Lang: 'readonly',
                Popper: 'readonly',
            },
        },
        rules: {
            'no-unused-vars': ['error', { argsIgnorePattern: '^_', caughtErrors: 'none' }],
            'vue/multi-word-component-names': 'off',
            'vue/no-deprecated-slot-attribute': 'warn',
            'vue/no-mutating-props': 'warn',
        },
    },
    {
        files: ['resources/js/**/*.{js,vue}'],
        rules: {
            ...warningRules(js.configs.recommended.rules),
            ...warningRules(vue2EssentialRules),
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_', caughtErrors: 'none' }],
            'vue/multi-word-component-names': 'off',
            'vue/no-deprecated-slot-attribute': 'warn',
            'vue/no-mutating-props': 'warn',
        },
    },
    {
        ignores: [
            'node_modules/**',
            'public/**',
            'storage/**',
            'resources/js/lang-js-translation.js',
        ],
    },
];
