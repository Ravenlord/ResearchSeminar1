<?php
require(__DIR__.'/config.php');

define('NODE1', 'node1');
define('NODE2', 'node2');
define('DISTANCE', 'distance');

function outputSIF($fileHandle, $fileHandleEdges, $node1, $node2, $distance) {
  if($distance < 150) {
    fwrite($fileHandle, "m$node1\tmr\tm$node2\n");
    fwrite($fileHandle, "m$node2\tmr\tm$node1\n");
    fwrite($fileHandleEdges, "m$node1\t(mr)\tm$node2\t=\t$distance\n");
    fwrite($fileHandleEdges, "m$node2\t(mr)\tm$node1\t=\t$distance\n");
  }
}

$fileHandle = fopen('graph.sif', 'w+');
$fileHandleEdges = fopen('graph.eda', 'w+');
fwrite($fileHandleEdges, "InteractionStrength\n");
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
$result = $mysqli->query('SELECT * FROM `distances` ORDER BY node1 ASC');
while ($row = $result->fetch_assoc()) {
  outputSIF($fileHandle, $fileHandleEdges, $row[NODE1], $row[NODE2], $row[DISTANCE]);
}
fclose($fileHandle);
fclose($fileHandleEdges);
$result->close();
$mysqli->close();