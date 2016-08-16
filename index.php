<?php

require 'src/bootstrap.php';

use Datto\PhpTypeInferer\InconsistentTypeException;
use Datto\PhpTypeInferer\TypeInferer;

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
            'arguments' => array('str', 'int', 'int'),
            'return' => 'str'
        ),
                
        // TODO: add support for multiple arity functions; this is here for coverage
        array(
            'arguments' => array('str', 'flt'),
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
    ),

    'foo' => array(
        array(
            'arguments' => array(144),
            'return' => 144
        ),

        array(
            'arguments' => array(5511),
            'return' => 144
        )
    )
);

$signatures = array(
    'less' => array(
        array(
            'arguments' => array('int', 'int'),
            'return' => 'bool'
        ),
        array(
            'arguments' => array('float', 'int'),
            'return' => 'bool'
        ),
        array(
            'arguments' => array('int', 'float'),
            'return' => 'bool'
        ),
        array(
            'arguments' => array('float', 'float'),
            'return' => 'bool'
        )
    ),
    'or' => array(
        array(
            'arguments' => array('bool', 'bool'),
            'return' => 'bool'
        )
    ),
    'filter' => array(
        array(
            'arguments' => array('bool'),
            'return' => 'list'
        )
    ),
    'get' => array(
        array(
            'arguments' => array('int'),
            'return' => 'list'
        )
    )
);
$typeInferer = new TypeInferer($signatures);
$expressions = array(
    array(
        'type' => 'function',
        'name' => 'or',
        'arguments' => array(
            array(
                'type' => 'function',
                'name' => 'less',
                'arguments' => array(
                    array('type' => 'primitive', 'name' => 'int'),
                    array('type' => 'parameter', 'name' => 'a')
                )
            ),
            array(
                'type' => 'function',
                'name' => 'less',
                'arguments' => array(
                    array('type' => 'parameter', 'name' => 'b'),
                    array('type' => 'primitive', 'name' => 'int')
                )
            )
        )
    ),
);

try {
    $results = $typeInferer->infer($expressions);
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (InconsistentTypeException $e) {
    echo $e->getMessage() . "\n";
    echo json_encode($e->getData()) . "\n";
}
