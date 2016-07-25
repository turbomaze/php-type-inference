<?php

namespace Datto\Cinnabari;

class InconsistentTypeException extends \Exception
{
    private $data;

    public function __construct($data, $code = 0, Exception $previous = null)
    {
        parent::__construct('ERROR: inconsistent type constraints.', $code, $previous);

        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
