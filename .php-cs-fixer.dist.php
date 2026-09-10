<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        // The project imports global classes rather than writing them fully
        // qualified, so the Symfony preset's opposite preference is turned off.
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'phpdoc_to_comment' => false,
    ])
    ->setFinder($finder)
;
