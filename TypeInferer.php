<?php

namespace Datto\Cinnabari;

class TypeInferer
{
    private $signatures;

    public function __construct($signatures)
    {
        $this->signatures = $signatures;
    }

    public function infer($expressions)
    {
        // disambiguate the function and parameter names by appending unique ids to names
        $labeledExpression = $expressions[0]; // TODO: only look at the first expression for now
        $this->disambiguate($labeledExpression); // 0 is the initial id

        // construct a constraints dictionary
        $this->getConstraints($labeledExpression, $constraints);

        $inconsistencies = array();
        $validTypeSettings = $this->reconstruct(
            $constraints,
            $labeledExpression,
            $inconsistencies
        );

        return $validTypeSettings;
    }

    private function disambiguate(&$expression, $id = 0)
    {
        $expression['name'] = $expression['name'] . '#' . $id;
        $id += 1;
        if ($expression['type'] === 'function') {
            for ($s = 0; $s < count($expression['arguments']); $s++) {
                $id = $this->disambiguate($expression['arguments'][$s], $id);
            }
        }
        return $id;
    }

    private function getConstraints($expression, &$constraints)
    {
        if (!isset($constraints)) {
            $constraints = array();
        }

        if ($expression['type'] === 'parameter') {
            return false;
        } else {
            $expressionName = $expression['name'];
            $functionName = substr($expressionName, 0, strrpos($expressionName, '#', -1));
            $signatures = $this->signatures[$functionName];

            // get the type restrictions imposed by previous function calls
            $callArity = count($expression['arguments']); // the arity of this specific call
            $typeRestrictions = null; // null if no restrictions; otherwise, array of valid types
            if (array_key_exists($expressionName, $constraints)) {
                if (array_key_exists('_', $constraints[$expressionName])) {
                    // this is a leader, so its constraints are all under the magic key '_'
                    $typeRestrictions = $constraints[$expressionName]['_'];
                } else {
                    // this is a follower, so its constraints are indexed by its leader's types
                    $typeRestrictions = array();
                    foreach ($constraints[$expressionName] as $leaderType => $value) {
                        foreach ($constraints[$expressionName][$leaderType] as $key => $setting) {
                            // we use object keys to ensure uniqueness
                            $typeRestrictions[$setting[0]] = true;
                        };
                    }
                    $typeRestrictions = array_keys($typeRestrictions);
                }
            }

            // filter out the signatures of this function by 1) arity and 2) type restrictions
            $viableSignatures = $signatures;
            if ($typeRestrictions !== null) {
                $viableSignatures = array_filter(
                    $signatures,
                    function ($signature) use ($callArity, $typeRestrictions) {
                        // compare arity
                        if ($callArity !== count($signature['arguments'])) {
                            return false;
                        }

                        // the signature's return type must be in the typeRestrictions array
                        $returnType = $signature['return'];
                        return array_search($returnType, $typeRestrictions) !== false;
                    }
                );
            }
            
            // record the viable signatures in the constraints entries for each child
            for ($c = 0; $c < count($expression['arguments']); $c++) {
                $child = $expression['arguments'][$c];
                $childName = $child['name'];

                // TODO: arbitrary arity
                if ($c === 0) { // first child is special
                    $constraints[$childName] = array('_' => array()); // constraints indexed by magic '_'
                    foreach ($viableSignatures as $key => $signature) {
                        // add type signature[st] to the types of the first child
                        $constraints[$childName]['_'][$signature['arguments'][$c]] = true;
                    }
                    $constraints[$childName]['_'] = array_keys($constraints[$childName]['_']);
                } else {
                    $constraints[$childName] = array(); // otherwise, index constraints by the first child's types
                    foreach ($viableSignatures as $key => $signature) {
                        // the previous child is referred to as the "leader"
                        $leaderType = $signature['arguments'][$c - 1]; // the leader's type
                        $followerType = $signature['arguments'][$c]; // this child's, the follower's, type
                        $expressionType = $signature['return']; // the expression's type
                        
                        // safely append the [followerType, expressionType] to the follower's constraints
                        if (array_key_exists($leaderType, $constraints[$childName])) {
                            $constraints[$childName][$leaderType][] = [$followerType, $expressionType];
                        } else {
                            $constraints[$childName][$leaderType] = array(array($followerType, $expressionType));
                        }
                    }
                }

                // recurse on this child
                $this->getConstraints($child, $constraints);
            }
        }
    }

    private function reconstruct($constraints, $expression, $err)
    {
        $reconstruction = array();

        // base case: parameters
        if ($expression['type'] === 'parameter') {
            $expressionName = $expression['name'];
            if (array_key_exists('_', $constraints[$expressionName])) {
                foreach ($constraints[$expressionName]['_'] as $key => $type) {
                    $reconstruction[$type] = array(array());
                    $reconstruction[$type][0][$expressionName] = $type;
                };
            } else {
                foreach ($constraints[$expressionName] as $key => $value) {
                    $reconstruction[$key] = array_map(
                        function ($type) use ($expressionName) {
                            $object = array();
                            $object[$expressionName] = $type;
                            return $object;
                        },
                        $constraints[$expressionName][$key]
                    );
                }
            }
            return $reconstruction;
        }

        // first, reconstruct all the children
        $reconstructedKids = array();
        for ($c = 0; $c < count($expression['arguments']); $c++) {
            $child = $expression['arguments'][$c];
            $childReconstruction = $this->reconstruct($constraints, $child, $err);
            if (count(array_keys($childReconstruction)) === 0) {
                return array();
            }
            $reconstructedKids[] = $childReconstruction;
        }

        // make it easier to access the children
        $leader = $reconstructedKids[0]; // TODO: arbitrary arity
        if (count($reconstructedKids) === 1) {
            return $leader;
        }
        $follower = $reconstructedKids[1];

        // finally, combine the constructed children
        if ($expression['arguments'][1]['type'] === 'parameter') { // second argument is a parameter -> easy
            foreach ($leader as $leaderType => $value) {
                $followerSettings = $follower[$leaderType];
                $reconstruction = $this->consolidate(
                    $reconstruction,
                    $this->parameterProduct($leader[$leaderType], $followerSettings, $err)
                );
            }
        } else {
            // get the correspondence dictionary to link the constructed kids together
            $correspondence = $constraints[$expression['arguments'][1]['name']]; // aka constraint

            // fill in the gaps of follower's reconstruction
            foreach ($follower as $followerType) {
                $follower[$followerType] = array_map(
                    function ($settings) {
                        return array($settings, null); // placeholder for the expression return types
                    },
                    $follower[$followerType]
                );
            }

            // use the correspondence information to perform the more complex reconstruction
            foreach ($correspondence as $leaderType => $value) {
                $signaturePartials = $correspondence[$leaderType];
                foreach ($signaturePartial as $key => $signaturePartial) {
                    $followerType = $signaturePartial[0];
                    $expressionType = $signaturePartial[1];

                    if (array_key_exists($leaderType, $leader) && array_key_exists($followerType, $follower)) {
                        $follower[$followerType] = array_map(
                            function (&$settings) use ($expressionType) {
                                $settings[1] = $expressionType;
                            },
                            $follower[$followerType]
                        );

                        $reconstruction = $this->consolidate(
                            $reconstruction,
                            $this->functionProduct(
                                $leader[$leaderType],
                                $follower[$followerType],
                                $err
                            )
                        );
                    }
                }
            }
        }

        return $reconstruction;
    }

    private function consolidate($a, $b)
    {
        $object = array();
        foreach ($a as $keyA => $valueA) {
            $object[$keyA] = $a[$keyA];
        }
        foreach ($b as $keyB => $valueB) {
            if (array_key_exists($keyB, $object)) {
                // TODO: make less ambiguous
                $object[$keyB] = array_merge($object[$keyB], $b[$keyB]);
            } else {
                $object[$keyB] = $b[$keyB];
            }
        }
        return $object;
    }

    private function parameterProduct($setListA, $setListB, $err)
    {
        return $this->product(
            $setListA,
            $setListB,
            $err,
            // get stripped set
            function ($set) {
                $setKey = array_keys($set)[0];
                $typeOfSet = $set[$setKey][0];
                $strippedSet = array();
                $strippedSet[$setKey] = $typeOfSet;
                return $strippedSet;
            },
            // get return type
            function ($set) {
                $setKey = array_keys($set)[0];
                $returnType = $set[$setKey][1];
                return $returnType;
            }
        );
    }

    private function functionProduct($setListA, $setListB, $err)
    {
        return $this->product(
            $setListA,
            $setListB,
            $err,
            // get stripped set
            function ($set) {
                return $set[0];
            },
            // get return type
            function ($set) {
                return $set[1];
            }
        );
    }

    private function product($setListA, $setListB, $err, $getStrippedSet, $getReturnType)
    {
        $object = array();
    
        // if (typeof setListA !== 'objectect' || typeof setListB !== 'objectect') {
        //     return object;
        // }
    
        foreach ($setListA as $keyA => $setA) {
            foreach ($setListB as $keyB => $setB) {
                $strippedSetB = $getStrippedSet($setB);
                $returnType = $getReturnType($setB);
    
                try {
                    $newSetting = $this->merge($setA, $strippedSetB);
                } catch (Exception $e) {
                    // inconsistent constraints
                    if (!array_key_exists($e['name'], $err)) {
                        $err[$e['name']] = $e['types'];
                    }
                    continue;
                }
    
                if (array_key_exists($returnType, $object)) {
                    $object[$returnType][] = $newSetting;
                } else {
                    $object[$returnType] = [$newSetting];
                }
            }
        }
    
        if (count(array_keys($object)) === 0) {
            return array();
        } else {
            return $object;
        }
    }

    private function merge($a, $b)
    {
        $object = array();
    
        foreach ($a as $keyA => $value) {
            $index = strrpos($keyA, '#', -1);
            $disambiguatedKeyA = $keyA;
            if ($index !== false) {
                $disambiguatedKeyA = substr($keyA, 0, $index);
            }
            $object[$disambiguatedKeyA] = $a[$keyA];
        }
    
        foreach ($b as $keyB => $value) {
            $index = strrpos($keyB, '#', -1);
            $disambiguatedKeyB = $keyB;
            if ($index !== false) {
                $disambiguatedKeyB = substr($keyB, 0, $index);
            }
            
            if (array_key_exists($disambiguatedKeyB, $object)) {
                $proposedType = $object[$disambiguatedKeyB];
                if ($proposedType !== $b[$keyB]) {
                    throw new InconsistentTypeException(array(
                        'name' => $disambiguatedKeyB,
                        'types' => array($proposedType, $b[$keyB])
                    ));
                }
            }

            $object[$disambiguatedKeyB] = $b[$keyB];
        }
    
        return $object;
    }
}
