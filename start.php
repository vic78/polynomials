<?php
/** start.php */

include 'Polynomials.php';


function calculation($text)
{
    $polynomials = new Polynomials($text);
    $str = '';
    $str .= $polynomials->printPolynomials();
    $str .= $polynomials->printPoints();
    
    $res = $polynomials->applyHornerMethodByIndices(5, 5);
    if ($res) {
        $str .= "\n Quotient:\n". $polynomials->printPolynomial($res['quotient']);
        $str .= "\n Reminder:\n". $res['reminder'];
        $str .= "\n";
    }
    return $str;
}


if (isset($argv[1])) {
    $filename = $argv[1];
    if (!is_file($filename)) {
        echo "$filename isn't a file or doesn't exist!\n";
    } else {
        $text = file_get_contents($filename);
        echo calculation($text);
        echo "\n\n";
    }
} else {
    echo "No input file specified!\n";
}

