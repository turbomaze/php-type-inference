<?php

require 'TypeInferer.php';
require 'InconsistentTypeException.php';

use Datto\Cinnabari\TypeInferer;
use Datto\Cinnabari\InconsistentTypeException;

function formatTypeSettings($setting)
{
    $formatted = "";
    foreach ($setting as $name => $type) {
        $formatted .= $name . "::" . $type . ", ";
    }
    return substr($formatted, 0, strlen($formatted) - 2);
}

function printSettingsList($settingsDictionary)
{
    foreach ($settingsDictionary as $returnType => $settings) {
        echo 'RETURN TYPE: ' . $returnType . "\n";
        foreach ($settings as $key => $setting) {
            echo '    ' . formatTypeSettings($setting) . "\n";
        }
    }
}

$signatures = array(
    'plus' => array(
        array(
            'arguments' => array('int', 'int'),
            'return' => 'int'
        ),

        array(
            'arguments' => array('float', 'int'),
            'return' => 'float'
        ),

        array(
            'arguments' => array('int', 'float'),
            'return' => 'float'
        ),

        array(
            'arguments' => array('float', 'float'),
            'return' => 'float'
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

        array(
            'arguments' => array('str', 'int', 'float'),
            'return' => 'str'
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

$typeInferer = new TypeInferer($signatures, $argv[1]);

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
        'name' => 'slice',
        'type' => 'function',
        'arguments' => array(
            array('name' => 'a', 'type' => 'parameter'),
            array('name' => 'b', 'type' => 'parameter'),
            array('name' => 'c', 'type' => 'parameter')
        )
    )
);

try {
    $results = $typeInferer->infer($expressions);
    if ($argv[1] === '1') {
        printSettingsList($results);
    }
} catch (InconsistentTypeException $e) {
    echo $e->getMessage() . "\n";
    echo json_encode($e->getData()) . "\n";
}
