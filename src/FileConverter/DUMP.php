<?php
namespace FileConverter;

/*
 * Large CSV DUMP into table
 * csv function
 */

// header('Content-type: application/json');
// Set your CSV feed
// $obj = new DUMP();
// $mapping = array(
//                 "date" => 'Dimension.date',
//                 "name" => 'Dimension.name',
//                 "address" => 'Dimension.address'
//               );
// $authSql = array(
//                   'user' => 'root',
//                   'password' => 'root',
//                   'host' =>  'localhost',
//                   'database' =>  'DB_Name',
//                   'table' => 'Table_Name',
//                   'csvFilePath' => 'path/file.csv'
//                 );
//
// $colConditions = array(
//                 "address" => 'CASE
//                                 WHEN @address like "PUNE" THEN "City"
//                                 WHEN @address like "MUMBAI" THEN "Town"
//                                 ELSE @address
//                             END'
//               );
// $res = $obj->csv($mapping, $authSql, $colConditions);
// echo json_encode($res);

class DUMP
{

  // Function to convert DUMP into associative array
  /*
    @peram1 array => CSV column namae mapping.
    @peram2 array => mysql user, password, host, tableName
    @peram3 array(optional) => mysql column conditions


  */
  public function csv($mapping, $authSql) {
    // return array_values($mapping);
    $mapper = [];
    $delimiter = ',';
    if (($handle = fopen($authSql['csvFilePath'], 'r')) !== FALSE) {
      $i = 0;
      while (($lineArray = fgetcsv($handle, 4000, $delimiter, '"')) !== FALSE) {
        $lineArray=array_map('trim',$lineArray);
        if(count(array_intersect($lineArray, array_values($mapping)))){
             $arr = [];
             foreach ($lineArray as $key => $value) {
              if (in_array($value, array_values($mapping))) {
                if (substr(array_search ($value, $mapping), 0, 1) === '@') {
                    $arr[array_search ($value, $mapping)]= "";
                 }else{
                    $arr["@".array_search ($value, $mapping)]= array_search ($value, $mapping);
                 }
                 unset($mapping[array_search ($value, $mapping)]);
              }else{
                $arr["@col".$key]= '';
              }
             }
             array_push($mapper, $arr, $mapping);
             // return $mapper;
             return self::createContaintent($mapper, $authSql);
        }
        fclose($handle);
      }
    }
  }

  /*
    @peram1 array => CSV column names
    @peram2 array => CSV column namae mapping.
    @peram3 array => mysql user, password, host, tableName
    @peram4 array(optional) => mysql column conditions
  */


  public function createContaintent($mapper, $authSql) {
    $strCSVColumns = array_keys($mapper[0]);
// return    $strCSVColumns;
    $strCSVTableMapping = "";
    foreach ($mapper[0] as $key => $value) {
      if ($value != '') {
          $strCSVTableMapping .= $value . "=" . $key . ", ";
      }
    }
    foreach ($mapper[1] as $key => $value) {
          $strCSVTableMapping .= $key . "=" . $value . ", ";
    }

    $strCSVTableMapping = rtrim($strCSVTableMapping, ", ");
    // return $strCSVTableMapping;
    $containt = "
      set @StartTime = NOW();
      LOAD DATA LOCAL INFILE '". $authSql['csvFilePath'] ."'
      INTO TABLE ". $authSql['table'] ."
      FIELDS TERMINATED BY ','
      ENCLOSED BY '\"'
      LINES TERMINATED BY '\\n' IGNORE 1 ROWS
      (
        " . implode(', ', $strCSVColumns) ."
      )
      set
        ". $strCSVTableMapping .";
        select concat ('inserted ', row_count(), ' rows ', 'Start time: ', @StartTime, ' End time: ', NOW(), ' Duration: ',  TIMEDIFF(NOW(), @StartTime)) as '';
        ";

    $fileName = 'file.sql';
    $handle = fopen($fileName, 'w') or die('Cannot open file:  '.$fileName);
    fwrite($handle, $containt);
    $command = "mysql --user=" . $authSql['user'] ." --password=" . $authSql['password'] . " -h " . $authSql['host'] ." -D " . $authSql['database'] ." < " . $fileName;
   // return $command;
    $output = shell_exec($command . " 2>&1");
    $output = str_replace("mysql: [Warning] Using a password on the command line interface can be insecure.","",$output);
    unlink($fileName);
    return $output;
  }
}
?>
