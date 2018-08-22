<?php
namespace FileConverter;

/*
 * Large CSV DUMP into table
 * csv function
 */

// header('Content-type: application/json');
// Set your CSV feed
// $obj = new Dump();
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
//                 "li_type" => 'CASE
//                                 WHEN @li_type like "STANDARD" THEN "Premium Campaigns"
//                                 WHEN @li_type like "AD_EXCHANGE" THEN "Private Ad Exchange"
//                                 ELSE @li_type
//                             END'
//               );
// $res = $obj->csv($mapping, $authSql, $colConditions);
// echo json_encode($res);

class Dump
{

  // Function to convert Dump into associative array
  /*
    @peram1 array => CSV column namae mapping.
    @peram2 array => mysql user, password, host, tableName
    @peram3 array(optional) => mysql column conditions


  */
  public function csv($mapping, $authSql, $colConditions = []) {
    // return array_values($mapping);
    $delimiter = ',';
    if (($handle = fopen($authSql['csvFilePath'], 'r')) !== FALSE) {
      $i = 0;
      while (($lineArray = fgetcsv($handle, 4000, $delimiter, '"')) !== FALSE) {
        $lineArray=array_map('trim',$lineArray);
        if(count(array_intersect($lineArray, array_values($mapping))) >= count(array_values($mapping))){
             $arr = [];
             foreach ($lineArray as $key => $value) {
              if (in_array($value, array_values($mapping))) {
                $arr["@".array_search ($value, $mapping)]= array_search ($value, $mapping);
              }else{
                $arr["@col".$key]= '';
              }
             }
             // return $arr;
             return self::createContaintent($arr, $authSql, $colConditions);
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

  public function createContaintent($csvFileCol, $authSql, $colConditions = []) {
    $strCSVColumns = array_keys($csvFileCol);
    $strCSVTableMapping = "";
    foreach ($csvFileCol as $key => $value) {
        if (in_array($value, array_keys($colConditions))) {
          // echo "==" . $key . "<br>";
        $strCSVTableMapping .= $value . "=" . $colConditions[$value] . ", ";

        }
      if ($value != '') {
        $strCSVTableMapping .= $value . "=" . $key . ", ";
      }
    }
    $strCSVTableMapping = rtrim($strCSVTableMapping, ", ");
    $containt = "
      TRUNCATE TABLE ". $authSql['table'] .";
      set @StartTime = NOW();
      LOAD DATA LOCAL INFILE '". $authSql['csvFilePath'] ."'
      INTO TABLE 00_dfp_data
      FIELDS TERMINATED BY ','
      ENCLOSED BY '\"'
      LINES TERMINATED BY '\\n' IGNORE 1 ROWS
      (
        " . implode(', ', $strCSVColumns) ."
      )
      set
        ". $strCSVTableMapping .";
        select concat ('Updated ', row_count(), ' rows ', 'Start time: ', @StartTime, ' End time: ', NOW(), ' Duration: ',  TIMEDIFF(NOW(), @StartTime)) as '';
        ";

    $fileName = 'file.sql';
    $handle = fopen($fileName, 'w') or die('Cannot open file:  '.$fileName);
    fwrite($handle, $containt);
    $command = "mysql --user=" . $authSql['user'] ." --password=" . $authSql['password'] . " -h " . $authSql['host'] ." -D " . $authSql['database'] ." < " . $fileName;
   // return $command;
    $output = shell_exec($command);
    return $output;
  }
}
?>
