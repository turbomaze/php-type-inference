<?php

require 'src/bootstrap.php';

use Datto\Cinnabari\InconsistentTypeException;
use Datto\Cinnabari\TypeInferer;

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
    ),

    'floor' => array(
        array(
            'arguments' => array('int'),
            'return' => 'int'
        ),

        array(
            'arguments' => array('flt'),
            'return' => 'int'
        )
    )
);

$typeInferer = new TypeInferer($signatures);

$expressions = array(
    array(
        'name' => 'plus',
        'type' => 'function',
        'arguments' => array(
            array('name' => 'c', 'type' => 'parameter'),
            array('name' => 'a', 'type' => 'parameter')
        )
    ),
    array(
        'name' => 'substr',
        'type' => 'function',
        'arguments' => array(
            array('name' => 'd', 'type' => 'parameter'),
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'b', 'type' => 'parameter'),
                    array('name' => 'a', 'type' => 'parameter')
                )
            )
        )
    )
);

$expressions = array(
    array(
        'name' => 'plus',
        'type' => 'function',
        'arguments' => array(
            array('name' => 'a', 'type' => 'parameter'),
            array('name' => 'b', 'type' => 'parameter')
        )
    )
);

try {
    $results = $typeInferer->infer($expressions);
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (InconsistentTypeException $e) {
    echo $e->getMessage() . "\n";
    echo json_encode($e->getData()) . "\n";
}
