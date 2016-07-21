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
    )
);

$typeInferer = new TypeInferer($signatures);

$expressions = array(
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

            array('name' => 'a', 'type' => 'parameter')
        )
    )
);

try {
    echo $typeInferer->infer($expressions) . "\n";
} catch (InconsistentTypeException $e) {
    echo $e->getMessage() . "\n";
    echo json_encode($e->getData()) . "\n";
}
