<?php

class StrBuilder
{
    private $str = ""; // Atributo

    // Propiedade
    /**
     * @return string
     */
    public function getStr()
    {
        return $this->str;
    }

    // Funções
    public function append($string)
    {
        $this->str .= $string; //Concatena
    }

    public function appendLine($string)
    {
        $this->str .= $string . "\n";
    }

    public function clear()
    {
        $this->str = "";
    }
}

?>