<html>
 <head>
  <title>Who likes buttons</title>
  <link rel="stylesheet" href="styles.css">
 </head>
 <body>
     
         

<?php
# include 'Website.html';


$servername = "localhost";
$username = "asdwtbdf_guest2";
$password = "redacted PW";
$dbname = "asdwtbdf_pardusmonsterdata";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully <br>";

$tabletitles = array(
    "lasers",
    "armours",
    "shields",
    "missiles",
    "specials",
    "drives",
    "npcs"
    );

$sqllaser = "SELECT * FROM `Lasers`";
$sqllaserheadings= "SELECT COLUMN_NAME from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME='Lasers'";

$laserresult = $conn->query($sqllaser);
$laserheadingsresult = $conn->query($sqllaserheadings);



if ($laserresult->num_rows > 0) {
  
  echo "<h1>Gun data</h1>";
  
  if ($laserheadingsresult->num_rows > 0) {
      echo "<table><tr>";
      $dummy = $laserheadingsresult->fetch_assoc();
      while($heading = $laserheadingsresult->fetch_assoc()){
          echo "<th>" . $heading["COLUMN_NAME"] . "</th>";
          
      }
    echo "</tr>";
    }
else{
    echo "No data";
}

  
  // output data of each row
  while($content = $laserresult->fetch_row()) {
    echo "<tr>";
    array_shift($content);
    foreach ($content as $key=>$value) {
        echo "<td>" . $value . "</td>";
        }
    echo "</tr>";
      
  }
  echo "</table>";
} else {
  echo "0 results";
}
$conn->close();


?>
     
 </body>

</html>
