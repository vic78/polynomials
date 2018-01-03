This class allows to parse polynomials in one variable (x),
simplify input expressions, and divide the polynomials by linear
polynomials using Horner method.

php7.2 is required because of 'J' pattern modifier.



For example, admissible polynomial expressions are

5*x^2*x^3 + 12.003*x^5 + x^12 - 12*2*x^9*x^5;
       x+x+5.005*x^2*x^3;
0.23*x^12+7*x^0;

You can also input real points using expressions like

Points (-8, -14.02,   0.123, 0.442, 355   , 1 );

and use it as data for division by Horner method.



