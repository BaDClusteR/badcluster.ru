<?php

$finder = new PhpCsFixer\Finder()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/modules',
        __DIR__ . '/templates',
    ])
    ->name(['*.php', '*.phtml'])
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return new PhpCsFixer\Config()
    ->setRules([
        // Base ruleset: PSR-12 (covers most formatting)
        '@PSR12' => true,

        // Braces on same line for functions, classes, methods, control structures
        'braces_position' => [
            'functions_opening_brace' => 'same_line',
            'classes_opening_brace' => 'same_line',
            'anonymous_classes_opening_brace' => 'same_line',
            'control_structures_opening_brace' => 'same_line',
            'anonymous_functions_opening_brace' => 'same_line',
        ],

        // Clean up imports
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,

        // Array syntax
        'array_syntax' => ['syntax' => 'short'],

        // Spacing
        'no_extra_blank_lines' => true,
        'no_trailing_whitespace' => true,
        'single_blank_line_at_eof' => true,

        // Type hints
        'declare_strict_types' => false, // don't force strict_types on existing code

        // Misc cleanup
        'no_empty_statement' => true,
        'no_whitespace_in_blank_line' => true,
        'trim_array_spaces' => true,
        'single_quote' => true,
        'cast_spaces' => ['space' => 'single'],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(false);
