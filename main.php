<?php
 $sheet = "^(3510)16 | (45)4 (34)4 (23)4 (1423)4 | ......";
 preg_match_all("/\^?\(((\d\d)+)\)(\d+)/", $sheet, $score, PREG_SET_ORDER);
 $string_map = array();
 $tempo = array("tempo" => 60, "divisions" => 1, "unit" => null);
 $time_axis = 0; // Counter on the time axis.
 foreach ($score as $tuple) {
  for ($i = 0; $i < strlen($tuple[1]); $i += 2) {
   $string_id = substr($tuple[1], $i, 1);
   $position = substr($tuple[1], $i + 1, 1);
   if (!array_key_exists($string_id, $string_map))
    $string_map[$string_id] = array();
   if (array_key_exists($time_axis, $string_map[$string_id]))
    die("Conflict operation on the same string.\n");
   $string_map[$string_id][$time_axis] = $position;
  }
  $time_axis += $tuple[3];
 }
 print_r($string_map);
?>
