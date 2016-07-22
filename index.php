<?php

require 'TypeInferer.php';
require 'InconsistentTypeException.php';

use Datto\Cinnabari\TypeInferer;
use Datto\Cinnabari\InconsistentTypeException;

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
    ),
    
    'slice' => array(
        array(
            'arguments' => array('string', 'integer', 'integer'),
            'return' => 'string'
        ),

        array(
            'arguments' => array('string', 'integer', 'float'),
            'return' => 'string'
        )
    ),

    'foo' => array(
        array(
            'arguments' => array('X', 'Y', 'Z'),
            'return' => 'W'
        ),

        array(
            'arguments' => array('X', 'Y`', 'Z'),
            'return' => 'W`'
        ),

        array(
            'arguments' => array('X', 'Y`', 'Z`'),
            'return' => 'W`'
        )
    )
);

$typeInferer = new TypeInferer($signatures);

$expressions = array(
    array(
        'name' => 'foo',
        'type' => 'function',
        'arguments' => array(
            array('name' => 'a', 'type' => 'parameter'),
            array('name' => 'b', 'type' => 'parameter'),
            array('name' => 'c', 'type' => 'parameter')
        )
    )
);

$expressions = array(
    array(
        'name' => 'plus',
        'type' => 'function',
        'arguments' => array(
            array('name' => 'a', 'type' => 'parameter'),
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'b', 'type' => 'parameter'),
                    array('name' => 'c', 'type' => 'parameter')
                )
            )
        )
    )
);

try {
    echo json_encode(
        $typeInferer->infer($expressions),
        JSON_PRETTY_PRINT
    ) . "\n";
} catch (InconsistentTypeException $e) {
    echo $e->getMessage() . "\n";
    echo json_encode($e->getData()) . "\n";
}
