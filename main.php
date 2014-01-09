<?php
define("STRING_FRET", 8);
define("STRING_PRESS", 6);
define("STRING_RELEASE", 7);

 count($argv) == 3 or die("Usage: php -e $argv[0] <config_script> <score_script>\n");
 $latency = read_config($argv[1]);
 $sheet = file_get_contents($argv[2]) or die("Error reading $argv[2]\n");
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
   $string_map[$string_id][$time_axis] = array("position" => $position, "duration" => (int)$tuple[3]);
  }
  $time_axis += $tuple[3];
 }
 $tempo["unit"] = (int)round((60000/$tempo["tempo"])/$tempo["divisions"]);
 print_r($string_map);

class guitar_string
{
 private $id = null;
 private $state = array("position" => 1, "pressed" => false);

 public function guitar_string($id)
 {
  0 < $id && $id <= 16 or die("Invalid ID for guitar_string class.\n");
  $this->id = $id - 1;
 }

 private function fret()
 {
  return $this->id.STRING_FRET;
 }

 private function find($position)
 {
  return $this->id.$position;
 }

 public function play($position, $latency)
 {
  $result = array();
  $time = 0;
  if ($position == 0) {
   if ($this->state["pressed"]) {
    $time -= $latency["release"];
    $result["release"] = array("time" => $time, "code" => $this->release());
   }
   $this->state["pressed"] = false;
  }
  else {
   if ($this->state["position"] != $position) {
    $time -= $latency["press"];
    $result["press"] = array("time" => $time, "code" => $this->press());
    $time -= $latency["move"][$this->state["position"]][$position];
    $result["move"] = array("time" => $time, "code" => $this->find($position));
    if ($this->state["pressed"]) {
     $time -= $latency["release"];
     $result["release"] = array("time" => $time, "code" => $this->release());
    }
   }
   else if (!$this->state["pressed"]) {
    $time -= $latency["press"];
    $result["press"] = array("time" => $time, "code" => $this->press());
   }
   $this->state["position"] = $position;
   $this->state["pressed"] = true;
  }
  $result["fret"] = array("time" => 0, "code" => $this->fret());
  return $result;
 }

 private function press()
 {
  return $this->id.STRING_PRESS;
 }

 private function release()
 {
  return $this->id.STRING_RELEASE;
 }
}

function read_config($source)
{
 $latency = array();
 $config = file_get_contents($source) or die("Error reading $source\n");
 $config = preg_split("/\n|\r/", $config, -1, PREG_SPLIT_NO_EMPTY);
 foreach ($config as $line) {
  $data = preg_split("/\s/", $line, -1, PREG_SPLIT_NO_EMPTY);
  switch ($data[0]) {
   case "fret_latency":
    count($data) == 2 or die("Error fret_latency assignment\n");
    !array_key_exists("fret", $latency) or die("Conflict assignment of fret_latency\n");
    $latency["fret"] = to_positive_int($data[1], "Error format of fret_latency\n");
   break;
   case "move_latency":
    count($data) == 4 or die("Error move_latency assignment\n");
    if (!array_key_exists("move", $latency))
     $latency["move"] = array();
    $data[1] = to_positive_int($data[1], "Error format of move_latency parameter #1\n");
    $data[2] = to_positive_int($data[2], "Error format of move_latency parameter #2\n");
    if (!array_key_exists($data[1], $latency["move"]))
     $latency["move"][$data[1]] = array();
    !array_key_exists($data[2], $latency["move"][$data[1]]) or die("Conflict assignment of move_latency $data[1] $data[2]\n");
    $data[1] != $data[2] or die("move_latency cannot apply to the same position\n");
    $latency["move"][$data[1]][$data[2]] = to_positive_int($data[3], "Error format of move_latency parameter #3\n");
   break;
   case "press_latency":
    count($data) == 2 or die("Error press_latency assignment\n");
    !array_key_exists("press", $latency) or die("Conflict assignment of press_latency\n");
    $latency["press"] = to_positive_int($data[1], "Error format of press_latency\n");
   break;
   case "release_latency":
    count($data) == 2 or die("Error release_latency assignment\n");
    !array_key_exists("release", $latency) or die("Conflict assignment of release_latency\n");
    $latency["release"] = to_positive_int($data[1], "Error format of release_latency\n");
   break;
  }
 }
 foreach ($latency["move"] as $s => $path) {
  foreach (array_keys($path) as $t) {
   if (!array_key_exists($t, $latency["move"]))
    $latency["move"][$t] = array();
   if (!array_key_exists($s, $latency["move"][$t]))
    $latency["move"][$t][$s] = $latency["move"][$s][$t];
  }
 }
 return $latency;
}

function to_positive_int($n, $e)
{
 preg_match("/^\s*[1-9]\d*\s*$/", $n) or die($e);
 return (int)$n;
}
?>
