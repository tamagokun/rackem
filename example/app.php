<?php
require __DIR__."/../rackem.php";

session_start();

function multiplication_table($n)
	{
	return '<table>'
	. implode(array_map(function($i) use($n) { return table_row($i, $n); }, range(1, $n)))
	. '</table>';
	}
	
function table_row($i, $n)
	{
	return '<tr>'
	. implode(array_map(function($j) use($i) { return table_cell($i * $j); }, range(1, $n)))
	. '</tr>';
	}
	
function table_cell($i)
	{
	return '<td>' . $i . '</td>';
	}
	
$app = function() { return array(200, array(), array(multiplication_table(10))); };

\Rackem\Rack::run($app);
