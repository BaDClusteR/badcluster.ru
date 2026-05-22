import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import globals from 'globals';

export default tseslint.config(
    {
        ignores: ['dist/', 'node_modules/', '*.config.*']
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    {
        languageOptions: {
            globals: {
                ...globals.browser
            }
        },
        rules: {
            // Relax rules that conflict with our patterns
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/no-unused-vars': ['warn', {
                argsIgnorePattern: '^_',
                varsIgnorePattern: '^_'
            }],
            '@typescript-eslint/no-empty-object-type': 'off',

            // Code quality
            'no-console': ['warn', {allow: ['warn', 'error']}],
            'prefer-const': 'warn',
            'no-var': 'error',
            'eqeqeq': ['error', 'always', {null: 'ignore'}],
            'no-irregular-whitespace': 'off'
        }
    }
);
