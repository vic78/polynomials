<?php
/** Polynomials.php */

class Polynomials
{
    const INNER_INT     = '(\b(0|[1-9]\d*)\b)';
    const INTEGER       = '(?<integer>'. self::INNER_INT. ')';
    const POWER_INTEGER = '(?<power_integer>'. self::INNER_INT. ')';
    
    const FLOAT         = '(?<float>('. self::INNER_INT. ')?(\.\d+\b))';
    const NUMBER        = '(?<number>('. self::FLOAT. '|'. self::INTEGER. '))';

    // Polynomials in one variable
    const ONE_VARIABLE     = '(?<one_variable>\bx\b)';
    const VARIABLE_POWER   = '(?<variable_power>'. self::ONE_VARIABLE. '(\s*\^\s*'. self::POWER_INTEGER. ')?)';
    const MONOMIAL_ELEMENT = '(?<monomial_element>('. self::VARIABLE_POWER. '|'. self::NUMBER. '))';
    const MONOMIAL_PRODUCT = '(?<monomial_product>'. self::MONOMIAL_ELEMENT. '(\s*\*\s*'. self::MONOMIAL_ELEMENT. ')*)';
    const POLYNOMIAL       = '(?<polynomial>-?\s*'. self::MONOMIAL_PRODUCT. '(\s*[-+]\s*'. self::MONOMIAL_PRODUCT. ')*\s*;)';
    
    const SIGN_MONOMIAL_PRODUCT = '(?<sign_monomial_product>[-+]?\s*'. self::MONOMIAL_PRODUCT. ')';
    
    const SIGNED_NUMBER = '(?<signed_number>\-?\s*'. self::NUMBER. ')';
    const POINTS = '(?<points>\bPoints\s*\(\s*'.
            self::SIGNED_NUMBER. '(\s*,\s*'. self::SIGNED_NUMBER. ')*\s*\)\s*;)';
    
    private $polynomials = [];
    private $points = [];
    
    public $polynomial_sequence = [
        0 => ['pattern' => self::POLYNOMIAL, 'name' =>  'polynomials'],
        1 => ['pattern' => self::SIGN_MONOMIAL_PRODUCT, 'name' =>  'monomials'],
        2 => ['pattern' => self::MONOMIAL_ELEMENT, 'name' =>  'monomial_elements'],
    ];
    
    public $point_sequence = [
        0 => ['pattern' => self::POINTS, 'name' =>  'points'],
        1 => ['pattern' => self::SIGNED_NUMBER, 'name' =>  'signed_numbers'],
    ];
    
    function __construct($string = '')
    {
        $this->polynomials = $this->getPolynomialsFromString($string);
        $this->points = $this->getPointsFromString($string);
    }

    public function getWordsFromString(string $string, Array &$result, int  $i, Array $sequence)
    {
        $words = [];
        $pattern = '/' . $sequence[$i]['pattern']. '/J';
        preg_match_all($pattern, $string, $words);

        if (empty($words[0])) {
            return [];
        }
        
        $result['words'] = $words;
        $result['name'] = $sequence[$i]['name'];
        
        foreach($words[0] as $key => $value) {
        $result['components'][$key] = ['item' => $value, 'components' => []];}
        
        $i++;
        if ($i < count($sequence)) {
            foreach ($result['components'] as $key => $value) {
                $this->getWordsFromString($value['item'], $result['components'][$key], $i, $sequence);
            }
        }
    }

    public function getPointsFromString($string)
    {
        $result = [];
        $numbers = [];
        
        $this->getWordsFromString($string, $result, 0, $this->point_sequence);
        if (empty($result)) {
            return [];
        }
        
        unset ($result['words']);
        
        foreach ($result['components'] as $p_key => &$p_val) {
            foreach ($p_val['components'] as $n_key => &$n_val) {
                $n_val['signum'] = strpos($n_val['item'], '-') !== false ? -1 : 1;
                
                if (strlen($p_val['words']['integer'][$n_key]) !== 0) {
                    $n_val['module'] = (int)$p_val['words']['integer'][$n_key];
                } elseif (strlen($p_val['words']['float'][$n_key]) !== 0) {
                    $n_val['module'] = (double)$p_val['words']['float'][$n_key];
                }
                
                $number = $n_val['module'] * $n_val['signum'];
                if (!in_array($number, $numbers)) {
                    $numbers[] = $number;
                }
            }
            unset($n_val);
        }
        unset($p_val);
        
        return $numbers;
    }
    
    /**
     * Returns array like
     * [
     *      0 => [ 5 => 5.05 ],
     *      1 => [ 1 => 3, 0 => 1],
     * ]
     * getting string 5.05*x^5 ;  3*x + 1 ;
     * 
     * @param type $string
     * @return type Array
     */
    public function getPolynomialsFromString($string)
    {
        $result = [];
        $polynomials = [];
        
        $this->getWordsFromString($string, $result, 0, $this->polynomial_sequence);
        if (empty($result)) {
            return [];
        }
        
        unset ($result['words']);
        foreach ($result['components'] as $p_key => &$p_val) {
            unset($p_val['words']);
            $polynomials[$p_key] = [];
            foreach ($p_val['components'] as $m_key => &$m_val) {
                $m_val['signum'] = strpos($m_val['item'], '-') !== false ? -1 : 1;

                foreach ($m_val['components'] as $c_key => &$c_val) {
                    
                    if (empty($m_val['words']['one_variable'][$c_key])) {
                        $c_val['components']['degree'] = 0;
                    } elseif ( strlen($m_val['words']['power_integer'][$c_key]) === 0 ) {
                        $c_val['components']['degree'] = 1;
                    } else {
                        $c_val['components']['degree'] = $m_val['words']['power_integer'][$c_key];
                    }
                    if (strlen($m_val['words']['integer'][$c_key]) !== 0) {
                        $c_val['components']['coefficient'] = (int)$m_val['words']['integer'][$c_key];
                    } elseif (strlen($m_val['words']['float'][$c_key]) !== 0) {
                        $c_val['components']['coefficient'] = (double)$m_val['words']['float'][$c_key];
                    } else {
                        $c_val['components']['coefficient'] = 1;
                    }
                }
                unset($c_val);    

                $degree = 0;
                $coefficient = $m_val['signum'];
                
                foreach (array_column($m_val['components'], 'components') as $c_val) {
                    $coefficient *= $c_val['coefficient'];
                    $degree += $c_val['degree'];
                }
                unset($m_val['components']);
                unset($m_val['words']);
                $m_val['coefficient'] = $coefficient;
                $m_val['degree'] = $coefficient != 0 ? $degree : 'NaN';
                    
                // construct polynomial 
                if (is_int($m_val['degree'])) {
                    if (!array_key_exists($m_val['degree'], $polynomials[$p_key])) {
                        $polynomials[$p_key][$m_val['degree']] = 0;
                    } 
                    $polynomials[$p_key][$m_val['degree']] += $m_val['coefficient'];
                }
                    
                
            }
            unset($m_val);
            krsort($polynomials[$p_key]);
        }
        unset($p_val);
        
        return $polynomials;
    }
    
    public function printPolynomials()
    {
        $str = "Polynomials:\n\n";
        if (is_array($this->polynomials) && !empty($this->polynomials)) {
            foreach ($this->polynomials as $p_key => $p_val) {
                $str .= "$p_key)  ";
                $str .= $this->printPolynomial($p_val);
                $str .= "\n";
            }
            return $str. "\n";
        } else {
            return $str. "none\n\n";
        }
    }
    
    public function printPolynomial($p_val)
    {
        $str = '';
        if (is_array($p_val)) {
            if (empty($p_val)) {
                $str .= '0';
            } else {
                $new_line = true;
                foreach ($p_val as $degree => $coefficient) {
                    $sign = ( $new_line || $coefficient < 0 )? '' : '+';
                    if ( $new_line ) {
                        $new_line = false;
                    }
                    $variable = $degree == 0 ? '' : ( $degree == 1 ? "x" : "x^$degree" );
                    $coeff = $coefficient == 1 && !empty($variable) ? '' : $coefficient;
                    $astr = (!empty($coeff) && !empty($variable)) ? '*' : '';
                    $str .=  $sign. $coeff. $astr. $variable;
                }

            }
        }
        return $str;
    }
    
    public function printPoints()
    {
        $str = "Points:\n\n";
        if (is_array($this->points) && !empty($this->points)) {
            foreach ($this->points as $key => $number) {
                $str .= $key. ')  '. $number. "\n";
            }
            return $str. "\n";
        }  else {
            return $str. "none\n\n";
        }
        
    }
    
    /**
     * Finds the value of polynomials at the given point using
     * Horner's method
     * 
     * f(x) = g(x)*(x-x_0) + f(x_0)
     * 
     * Returns ['quotient' => g(x), 'reminder' => f(x_0) ]
     * 
     * @param array $points
     */
    public function applyHornerMethod($f, $x_0)
    {
        if (is_array($f) && !empty($f)) {
            $g = [];

            $f_degree = max(array_keys($f)); 
            
            $g[$f_degree - 1] = $f[$f_degree];
            for ($i = $f_degree-1; $i >= 0; $i-- ) {
                $g[$i-1] = $g[$i] * $x_0 + ( array_key_exists($i, $f) ? $f[$i] : 0);
            }
            $reminder = $g[-1];
            unset($g[-1]);
        }
        return ['quotient' => $g, 'reminder' => $reminder ];
    }
    
    public function applyHornerMethodByIndices($f_index, $x_index)
    {
        if (array_key_exists($f_index, $this->polynomials) && array_key_exists($x_index, $this->points))
        {
            return $this->applyHornerMethod($this->polynomials[$f_index], $this->points[$x_index]);
        }
    }
}