<?php

// autoload from https://github.com/sebastianbergmann/money/blob/master/src/autoload.php
spl_autoload_register(
    function ($className) {
        $translations = array(
            'Datto\\Cinnabari\\InconsistentTypeException' => '/InconsistentTypeException.php',
            'Datto\\Cinnabari\\TypeInferer' => '/TypeInferer.php'
        );

        if (array_key_exists($className, $translations)) {
            require __DIR__ . $translations[$className];
        }
    },
    true,
    false
);
