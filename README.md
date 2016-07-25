PHP Type Inference
===

Given a list of function signatures and expressions involving those functions, this project allows you to infer the most general type information about each parameter of those expressions.


## Features

Functions can have overloaded arguments and overloaded return types, can be supplied as arguments to other functions, and can have an arbitrary number of parameters.

If two disconnected expressions use some of the same parameters, then the inconsistencies between each of their locally valid type settings are ironed out. If there are no such type settings that satisfy all of the constraints imposed by the expressions, then an error is thrown.
