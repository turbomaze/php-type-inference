<?php

namespace Datto\Cinnabari\Tests;

use Datto\Cinnabari\InconsistentTypeException;
use Datto\Cinnabari\TypeInferer;
use PHPUnit_Framework_TestCase;

class TypeInfererTest extends PHPUnit_Framework_TestCase
{
    private static function getSignatures()
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
            )
        );

        return $signatures;
    }

    public function testBasicPlus()
    {
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

        $this->verify($expressions, $expected);
    }

    public function testUnrelatedPlus()
    {
        $expressions = array(
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'a', 'type' => 'parameter'),
                    array('name' => 'b', 'type' => 'parameter')
                )
            ),
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'c', 'type' => 'parameter'),
                    array('name' => 'd', 'type' => 'parameter')
                )
            )
        );
        $expected = array(
            'ordering' => array('b', 'a', 'd', 'c'),
            'hierarchy' => array(
                'int' => array(
                    'int' => array(
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
                    ),
                    'flt' => array(
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
                ),
                'flt' => array(
                    'int' => array(
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
                    ),
                    'flt' => array(
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
                ),
                'str' => array(
                    'str' => array(
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
                )
            )
        );

        $this->verify($expressions, $expected);
    }

    public function testOverlappingPlus()
    {
        $expressions = array(
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'a', 'type' => 'parameter'),
                    array('name' => 'b', 'type' => 'parameter')
                )
            ),
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'b', 'type' => 'parameter'),
                    array('name' => 'c', 'type' => 'parameter')
                )
            )
        );
        $expected = array(
            'ordering' => array('b', 'a', 'c'),
            'hierarchy' => array(
                'int' => array(
                    'int' => array(
                        'int' => true,
                        'flt' => true
                    ),
                    'flt' => array(
                        'int' => true,
                        'flt' => true
                    )
                ),
                'flt' => array(
                    'int' => array(
                        'int' => true,
                        'flt' => true
                    ),
                    'flt' => array(
                        'int' => true,
                        'flt' => true
                    )
                ),
                'str' => array(
                    'str' => array(
                        'str' => true
                    )
                )
            )
        );

        $this->verify($expressions, $expected);
    }

    public function testThreeOverlappingPlus()
    {
        $expressions = array(
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'a', 'type' => 'parameter'),
                    array('name' => 'b', 'type' => 'parameter')
                )
            ),
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'b', 'type' => 'parameter'),
                    array('name' => 'c', 'type' => 'parameter')
                )
            ),

            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'c', 'type' => 'parameter'),
                    array('name' => 'a', 'type' => 'parameter')
                )
            )
        );
        $expected = array(
            'ordering' => array('b', 'a', 'c'),
            'hierarchy' => array(
                'int' => array(
                    'int' => array(
                        'int' => true,
                        'flt' => true
                    ),
                    'flt' => array(
                        'int' => true,
                        'flt' => true
                    )
                ),
                'flt' => array(
                    'int' => array(
                        'int' => true,
                        'flt' => true
                    ),
                    'flt' => array(
                        'int' => true,
                        'flt' => true
                    )
                ),
                'str' => array(
                    'str' => array(
                        'str' => true
                    )
                )
            )
        );

        $this->verify($expressions, $expected);
    }

    public function testThreeArgumentFunction()
    {
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
        $expected = array(
            'ordering' => array('c', 'b', 'a'),
            'hierarchy' => array(
                'int' => array(
                    'int' => array(
                        'str' => true
                    )
                )
            )
        );

        $this->verify($expressions, $expected);
    }

    public function testNestedThreeArgumentFunction()
    {
        $expressions = array(
            array(
                'name' => 'substr',
                'type' => 'function',
                'arguments' => array(
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
                                    array('name' => 'c', 'type' => 'parameter')
                                )
                            ),
                            array('name' => 'd', 'type' => 'parameter')
                        )
                    ),
                    array('name' => 'e', 'type' => 'parameter')
                )
            )
        );
        $expected = array(
            'ordering' => array('e', 'd', 'c', 'b', 'a'),
            'hierarchy' => array(
                'int' => array(
                    'int' => array(
                        'int' => array(
                            'int' => array(
                                'str' => true
                            )
                        )
                    )
                )
            )
        );

        $this->verify($expressions, $expected);
    }

    public function testPrimitiveType()
    {
        $expressions = array(
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'a', 'type' => 'parameter'),
                    array('name' => 'str', 'type' => 'primitive')
                )
            )
        );

        $expected = array(
            'ordering' => array('a'),
            'hierarchy' => array(
                'str' => true
            )
        );
        
        $this->verify($expressions, $expected);
    }

    public function testNestedPrimitiveTypes()
    {
        $expressions = array(
            array(
                'name' => 'plus',
                'type' => 'function',
                'arguments' => array(
                    array(
                        'name' => 'plus',
                        'type' => 'function',
                        'arguments' => array(
                            array('name' => 'a', 'type' => 'parameter'),
                            array('name' => 'b', 'type' => 'parameter')
                        )
                    ),
                    array(
                        'name' => 'plus',
                        'type' => 'function',
                        'arguments' => array(
                            array('name' => 'str', 'type' => 'primitive'),
                            array('name' => 'c', 'type' => 'parameter')
                        )
                    )
                )
            )
        );

        $expected = array(
            'ordering' => array('c', 'b', 'a'),
            'hierarchy' => array(
                'str' => array(
                    'str' => array(
                        'str' => true
                    )
                )
            )
        );
        
        $this->verify($expressions, $expected);
    }

    public function testLocalInconsistency()
    {
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
                        )
                    ),
                    array('name' => 'a', 'type' => 'parameter')
                )
            )
        );
        
        $this->verifyException($expressions);
    }

    public function testCrossExpressionInconsistency()
    {
        $expressions = array(
            array(
                'name' => 'substr',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'a', 'type' => 'parameter'),
                    array('name' => 'b', 'type' => 'parameter')
                )
            ),
            array(
                'name' => 'substr',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'c', 'type' => 'parameter'),
                    array('name' => 'a', 'type' => 'parameter')
                )
            )
        );
        
        $this->verifyException($expressions);
    }

    public function testExceptionData()
    {
        $expressions = array(
            array(
                'name' => 'substr',
                'type' => 'function',
                'arguments' => array(
                    array('name' => 'a', 'type' => 'parameter'),
                    array('name' => 'a', 'type' => 'parameter')
                )
            )
        );
        $expected = array('name' => 'a', 'types' => array('int', 'str'));

        try {
            $this->inferTypes($expressions);
        } catch (InconsistentTypeException $e) {
            $data = $e->getData();
            $this->assertEquals($data, $expected);
        }
    }

    private function verify($expressions, $expected)
    {
        $result = $this->inferTypes($expressions);

        $this->assertEquals($result, $expected);
    }

    private function verifyException($expressions)
    {
        $this->setExpectedException('Datto\Cinnabari\InconsistentTypeException');

        $this->inferTypes($expressions);
    }

    private function inferTypes($expressions)
    {
        // arrange
        $signatures = self::getSignatures();
        $typeInferer = new TypeInferer($signatures);

        // act
        return $typeInferer->infer($expressions);
    }
}
