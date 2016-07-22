<?php

require 'TypeInferer.php';
require 'InconsistentTypeException.php';

use Datto\Cinnabari\TypeInferer;
use Datto\Cinnabari\InconsistentTypeException;

function formatTypeSettings($setting)
{
    $formatted = "";
    foreach (array_reverse($setting) as $name => $type) {
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
                    array('name' => 'c', 'type' => 'parameter'),
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
        printSettingsList($results);
    }
} catch (InconsistentTypeException $e) {
    echo $e->getMessage() . "\n";
    echo json_encode($e->getData()) . "\n";
}
