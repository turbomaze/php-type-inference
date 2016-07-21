<?php

require 'TypeInferer.php';
require 'InconsistentTypeException.php';

use Datto\Cinnabari\TypeInferer;
use Datto\Cinnabari\InconsistentTypeException;

// set up variables
$signatures = array(
    'plus' => array(
        array(
            'arguments' => array('integer', 'integer'),
            'return' => 'integer'
        ),

        array(
            'arguments' => array('float', 'integer'),
            'return' => 'float'
        ),

        array(
            'arguments' => array('integer', 'float'),
            'return' => 'float'
        ),

        array(
            'arguments' => array('float', 'float'),
            'return' => 'float'
        ),

        array(
            'arguments' => array('string', 'string'),
            'return' => 'string'
        )
    ),

    'substr' => array(
        array(
            'arguments' => array('string', 'integer'),
            'return' => 'string'
        )
    )
);

$typeInferer = new TypeInferer($signatures);

// test 1: simple plus
$simplePlusTest = array(
    array(
        'name' => 'plus',
        'type' => 'function',
        'arguments' => array(
            array('name' => 'a', 'type' => 'parameter'),
            array('name' => 'b', 'type' => 'parameter')
        )
    )
);
$simplePlusAnswer = array(
    'integer' => array(array('a' => 'integer', 'b' => 'integer')),
    'float' => array(
        array('a' => 'integer', 'b' => 'float'),
        array('a' => 'float', 'b' => 'integer'),
        array('a' => 'float', 'b' => 'float')
    ),
    'string' => array(array('a' => 'string', 'b' => 'string'))
);
assert(
    $typeInferer->infer($simplePlusTest) == $simplePlusAnswer,
    'Simple plus'
);

// test 1: force a type on child
$substrTest = array(
    array(
        'name' => 'substr',
        'type' => 'function',
        'arguments' => array(
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'a', 'type' => 'parameter'),
                    array('name' => 'b', 'type' => 'parameter')
                ),
            ),

            array('name' => 'c', 'type' => 'parameter')
        )
    )
);
$substrAnswer = array(
    'string' => array(array('a' => 'string', 'b' => 'string', 'c' => 'integer')),
);
assert(
    $typeInferer->infer($substrTest) == $substrAnswer,
    'Substr'
);

echo "All tests passed!\n";
