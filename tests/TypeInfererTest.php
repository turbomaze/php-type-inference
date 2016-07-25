<?php

namespace Datto\Cinnabari\Tests;

use Datto\Cinnabari\InconsistentTypeException;
use Datto\Cinnabari\TypeInferer;
use PHPUnit_Framework_TestCase;

class TypeInfererTest extends PHPUnit_Framework_TestCase
{
    private static function getBasicSignatures()
    {
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

        return $signatures;
    }

    public function testBasicPlus()
    {
        // arrange
        $signatures = self::getBasicSignatures();
        $typeInferer = new TypeInferer($signatures);
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
        $expected = array(
            'ordering' => array('b', 'a'),
            'hierarchy' => array(
                'int' => array(
                    'int' => true,
                    'flt' => true
                ),
                'flt' => array(
                    'int' => true,
                    'flt' => true
                ),
                'str' => array(
                    'str' => true
                )
            )
        );

        // act
        $result = $typeInferer->infer($expressions);

        // assert
        $this->assertEquals($result, $expected);
    }
}
