<?php

/**
 * Copyright (C) 2016 Datto, Inc.
 *
 * This file is part of Php Type Inferer.
 *
 * Php Type Inferer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * Php Type Inferer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Php Type Inferer. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Anthony Liu <igliu@mit.edu>
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL-3.0
 * @copyright 2016 Datto, Inc.
 * @version 0.3.3
 */

// autoload from https://github.com/sebastianbergmann/money/blob/master/src/autoload.php
spl_autoload_register(
    function ($className) {
        $translations = array(
            'Datto\\PhpTypeInferer\\InconsistentTypeException' => '/InconsistentTypeException.php',
            'Datto\\PhpTypeInferer\\TypeInferer' => '/TypeInferer.php'
        );

        if (array_key_exists($className, $translations)) {
            require __DIR__ . $translations[$className];
        }
    },
    true,
    false
);
