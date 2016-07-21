<?php

namespace Datto\Cinnabari;

class TypeInferer
{
    private $signatures;
    private $signatureDictionary;

    public function __construct($signatures)
    {
        $this->signatures = $signatures;

        // expand the flattened signatures into a hierarchical, associative array structure
        $this->signatureDictionary = array();
        foreach ($signatures as $name => $overloadList) {
            $this->signatureDictionary[$name] = array();
            foreach ($overloadList as $key => $signature) {
                $reference = &$this->signatureDictionary[$name];
                for ($s = 0; $s < count($signature['arguments']); $s++) {
                    $currentType = $signature['arguments'][$s];
                    if (!array_key_exists($currentType, $reference)) {
                        $reference[$currentType] = array();
                    }
                    $reference = &$reference[$currentType];
                }
                $reference = $signature['return'];
            }
        }
    }

    public function infer($expressions)
    {
        // disambiguate the function and parameter names by appending unique ids to names
        $labeledExpression = $expressions[0]; // TODO: only look at the first expression for now
        $this->disambiguate($labeledExpression); // 0 is the initial id

        // construct a constraints dictionary
        $this->getConstraints($labeledExpression, $constraints);
        // echo json_encode($constraints, JSON_PRETTY_PRINT)."\n\n";

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
            $functionName = $this->getNameFromIdentifier($expression['name']);
            $signatures = $this->signatures[$functionName];

            // get the type restrictions imposed by previous function calls
            $callArity = count($expression['arguments']); // the arity of this specific call
            $typeRestrictions = $this->getTypeRestrictions($constraints, $expressionName); // null or array of types

            // filter out the signatures of this function by 1) arity and 2) type restrictions
            $viableSignatures = $this->filterSignatures($signatures, $callArity, $typeRestrictions);
            
            // record the viable signatures in the constraints entries for each child
            foreach ($viableSignatures as $key => $signature) {
                for ($c = 0; $c < count($expression['arguments']); $c++) {
                    $child = $expression['arguments'][$c];
                    $childName = $child['name'];
                    if (!array_key_exists($childName, $constraints)) {
                        $constraints[$childName] = array();
                    }
                    $childConstraint = &$constraints[$childName];

                    // create nested associate arrays for each previous argument
                    for ($s = 0; $s < $c; $s++) {
                        $parameterType = $signature['arguments'][$s];
                        if (!array_key_exists($parameterType, $childConstraint)) {
                            $childConstraint[$parameterType] = array();
                        }
                        $childConstraint = &$childConstraint[$parameterType];
                    }

                    // append a new type possibility to the innermost object
                    $childConstraint[$signature['arguments'][$c]] = true;
                }
            }

            // recurse on children
            for ($c = 0; $c < count($expression['arguments']); $c++) {
                $child = $expression['arguments'][$c];
                $this->getConstraints($child, $constraints);
            }
        }
    }

    private function getTypeRestrictions($constraints, $expressionName)
    {
        $typeRestrictions = array();
        if (array_key_exists($expressionName, $constraints)) {
            // recursively accumulate the keys of boolean terminals from the constraints into an array
            $this->searchConstraintsForTerminals($constraints[$expressionName], $typeRestrictions);
        }
        return array_keys($typeRestrictions);
    }

    private function searchConstraintsForTerminals($constraints, &$terminalTypes)
    {
        // here
        foreach ($constraints as $type => $value) {
            if (gettype($value) === 'boolean') {
                $terminalTypes[$type] = true;
            } else {
                $this->searchConstraintsForTerminals($constraints[$type], $terminalTypes);
            }
        }
    }

    private function filterSignatures($viableSignatures, $callArity, $typeRestrictions)
    {
        if (count($typeRestrictions) !== 0) {
            $viableSignatures = array_filter(
                $viableSignatures,
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

        return $viableSignatures;
    }

    private function reconstruct($constraints, $expression, $err)
    {
        // base case: parameters
        if ($expression['type'] === 'parameter') {
            return $this->reconstructParameter($expression['name'], $constraints[$expression['name']]);
        } else {
            // first, reconstruct all the children
            $reconstructedKids = array();
            for ($c = 0; $c < count($expression['arguments']); $c++) {
                $child = $expression['arguments'][$c];
                $reconstructedKids[] = $this->reconstruct($constraints, $child, $err);
            }

            $functionName = $this->getNameFromIdentifier($expression['name']);
            $rawProduct = $this->getSiblingProduct(
                $this->signatureDictionary[$functionName],
                $reconstructedKids
            );
            if (array_key_exists($expression['name'], $constraints)) {
                $returnType = array_keys($rawProduct)[0];
                $reconstruction = array(
                    'settings' => $rawProduct[$returnType],
                    'return' => $returnType
                );
                return $this->reconstructFunction($functionName, $constraints[$expression['name']], $reconstruction);
            } else {
                return $rawProduct;
            }
        }
    }

    private function reconstructParameter($name, $constraint)
    {
        $reconstruction = array();
        foreach ($constraint as $type => $hierarchy) {
            if (gettype($constraint[$type]) === 'boolean') {
                $reconstruction[] = array(
                    'settings' => array($this->getNameFromIdentifier($name) => $type),
                    'return' => $type
                );
            } else {
                $reconstruction[$type] = $this->reconstructParameter($name, $constraint[$type]);
            }
        }
        return $reconstruction;
    }

    private function reconstructFunction($name, $constraint, $value)
    {
        $reconstruction = array();
        foreach ($constraint as $type => $hierarchy) {
            if (gettype($constraint[$type]) === 'boolean') {
                $reconstruction[] = $value;
            } else {
                $reconstruction[$type] = $this->reconstructFunction($name, $constraint[$type], $value);
            }
        }
        return $reconstruction;
    }

    private function getSiblingProduct($signature, $siblings)
    {
        $product = array();
        if (count($siblings) === 1) {
            foreach ($siblings[0] as $key => $configuration) {
                $returnType = $signature[$configuration['return']];
                if (!array_key_exists($returnType, $product)) {
                    $prod[$returnType] = array();
                }
                $product[$returnType][] = $configuration['settings'];
            }
            return $product;
        } else {
            foreach ($siblings[0] as $key => $configuration) {
                // offset the rest of the children by this config's return
                $offsetSiblings = array();
                for ($o = 1; $o < count($siblings); $o++) {
                    $offsetSiblings[] = $siblings[$o][$configuration['return']];
                }

                // offset the signature as well
                $offsetSignature = $signature[$configuration['return']];

                $product = $this->consolidate(
                    $product,
                    $this->cartesianProduct(
                        $this->getSiblingProduct($offsetSignature, $offsetSiblings),
                        $configuration['settings']
                    )
                );
            }
            return $product;
        }
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

    private function cartesianProduct($labeledSet, $incrementalSet)
    {
        $product = array();
        foreach ($labeledSet as $returnType => $settings) {
            $product[$returnType] = array_map(
                function ($setting) use ($incrementalSet) {
                    return $this->merge($setting, $incrementalSet);
                },
                $settings
            );
        }
        return $product;
    }

    private function merge($a, $b)
    {
        $object = array();
    
        foreach ($a as $keyA => $value) {
            $disambiguatedKeyA = $this->getNameFromIdentifier($keyA);
            $object[$disambiguatedKeyA] = $a[$keyA];
        }
    
        foreach ($b as $keyB => $value) {
            $disambiguatedKeyB = $this->getNameFromIdentifier($keyB);
            
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

    private function getNameFromIdentifier($id)
    {
        $index = strrpos($id, '#', -1);
        $disambiguatedId = $id;
        if ($index !== false) {
            $disambiguatedId = substr($id, 0, $index);
        }
        return $disambiguatedId;
    }
}
