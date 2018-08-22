<?php
namespace FileConverter;

/*
 * DUMP large CSV to DB
 * csv function
 */

// header('Content-type: application/json');
// Set your CSV feed
// $obj = new Dump();
// $mapping = array(
//                 "date_d" => 'Dimension.DATE',
//                 "name" => 'Dimension.name',
//                 "sirname" => 'Dimension.sirname'
//               );
// $authSql = array(
//                   'user' => 'root',
//                   'password' => 'root',
//                   'host' =>  'localhost',
//                   'database' =>  'DB_Name',
//                   'table' => 'table_name',
//                   'csvFilePath' => 'file_name_with_path'
//                 );

// $colConditions = array(
//                 "name" => 'CASE
//                                 WHEN @name like "name_1" THEN "new_name_1"
//                                 WHEN @name like "name_2" THEN "new_name_2"
//                                 ELSE @name
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
             return $this->createContaintent($arr, $authSql, $colConditions);
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

// return $csvFileCol;

    $strCSVColumns = array_keys($csvFileCol);
    // return implode(', ', $strCSVColumns);
// return array_keys($colConditions);
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
// return $strCSVTableMapping;

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



  // $script_path = './test.sql';

   // $command = "mysql --user={$user} --password='{$password}' "
   // . "-h {$host} -D {$table} < {$script_path}";

   $command = "mysql --user=" . $authSql['user'] ." --password=" . $authSql['password'] . " -h " . $authSql['host'] ." -D " . $authSql['database'] ." < " . $fileName;
   // return $command;
  $output = shell_exec($command);
  unlink($fileName);
  return $output;

  }


}
 ?>
