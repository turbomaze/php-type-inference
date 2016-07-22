<?php

require 'TypeInferer.php';
require 'InconsistentTypeException.php';

use Datto\Cinnabari\TypeInferer;
use Datto\Cinnabari\InconsistentTypeException;

$signatures = array(
    'plus' => array(
        array(
            'arguments' => array('int', 'int'),
            'return' => 'int'
        ),

        array(
            'arguments' => array('flt', 'int'),
            'return' => 'flt'
        ),

        array(
            'arguments' => array('int', 'flt'),
            'return' => 'flt'
        ),

        array(
            'arguments' => array('flt', 'flt'),
            'return' => 'flt'
        ),

        array(
            'arguments' => array('str', 'str'),
            'return' => 'str'
        )
    ),

    'substr' => array(
        array(
            'arguments' => array('str', 'int'),
            'return' => 'str'
        )
    ),
    
    'slice' => array(
        array(
            'arguments' => array('str', 'flt', 'int'),
            'return' => 'str'
        ),

        array(
            'arguments' => array('str', 'flt', 'flt'),
            'return' => 'str'
        )
    )
);

$typeInferer = new TypeInferer($signatures, $argv[1]);

$expressions = array(
    array(
        'name' => 'slice',
        'type' => 'function',
        'arguments' => array(
            array('name' => 'a', 'type' => 'parameter'),
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'b', 'type' => 'parameter'),
                    array('name' => 'd', 'type' => 'parameter')
                )
            ),
            array('name' => 'b', 'type' => 'parameter')
        )
    )
);

try {
    $results = $typeInferer->infer($expressions);
    if ($argv[1] === '1') {
        echo json_encode($results, JSON_PRETTY_PRINT);
    }
} catch (InconsistentTypeException $e) {
    echo $e->getMessage() . "\n";
    echo json_encode($e->getData()) . "\n";
}
