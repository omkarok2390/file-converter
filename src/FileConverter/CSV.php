<?php
namespace FileConverter;

/*
 * Converts CSV to JSON
 * toArray function
 */

// header('Content-type: application/json');
// // Set your CSV feed
// $obj = new CSV();
// $feed = 'files/test2.csv';
// $target = array('Start Date', 'End Date','Event Title');

// echo $obj->toJson($feed, $target);
// echo $obj->toXML($feed, $target);

class CSV
{

// Function to convert associative array into json
  public function toJson($feed, $target) {
    $keys = array();
    $newArray = array();
    $data = self::toArray($feed, ',', $target);
    $count = count($data) - 1;
    $labels = array_shift($data);
    foreach ($labels as $label) {
      $keys[] = $label;
    }
    $keys[] = 'id';
    for ($i = 0; $i < $count; $i++) {
      $data[$i][] = $i;
    }
    for ($j = 0; $j < $count; $j++) {
      $d = array_combine($keys, $data[$j]);
      $newArray[$j] = $d;
    }
    return json_encode($newArray);
  }

  // Function to convert CSV into associative array
  public function toArray($file, $delimiter, $target) {
    if (($handle = fopen($file, 'r')) !== FALSE) {
      $i = 0;
      while (($lineArray = fgetcsv($handle, 4000, $delimiter, '"')) !== FALSE) {
        $lineArray=array_map('trim',$lineArray);
        if(count(array_intersect($lineArray, $target)) >= count($target)){
             $arr = [];
        }
        for ($j = 0; $j < count($lineArray); $j++) {
          $arr[$i][$j] = $lineArray[$j];
        }
        $i++;
      }
      fclose($handle);
    }
    return $arr;
  }

  public function toXML($feed, $target){
    $json = self::toJson($feed, $target);
    $array = json_decode($json, true);
    return $xml = self::arrayToXML($array, false);
  }

  // Function to convert  array to XML
  public function arrayToXML($array, $xml = false){

      if($xml === false){
          $xml = new SimpleXMLElement('<result/>');
      }

      foreach($array as $key => $value){
          if(is_array($value)){
              self::arrayToXML($value, $xml->addChild($key));
          } else {
              $xml->addChild($key, $value);
          }
      }

      return $xml->asXML();
  }
}
 ?>
