<?php
/*
 * Copyright (c) 2017, Sohrab Monfared <sohrab.monfared@gmail.com>
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of the <organization> nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require "../shesql.php";


$records_to_create = 100000;
$delete = 0; /* in order to cleanup the old contents in database */

printf("<pre>\n");

$logger_info = array(
  "type" => "file",
  "path" => "/mnt/ramdisk/shesql.log",
);

/*
 * Must be ALWAYS initialized *BEFORE* being used in $database_info.
 */
$logger = shesql_logger_connect($logger_info);

$database_info = array(
  "type" => "sqlite",
  "path" => "/mnt/ramdisk/test_db.sqlite",
  "logger" => $logger,
  "what_to_log" => array("connect" => true, "disconnect" => true, "uptime" => true, "query" => true, "microtime" => true)
);

$db = shesql_connect($database_info);

if ($db < 1){
  die("> Error connecting to the database. Check the log files.\n");
}

if ($delete){
  shesql_raw_query_insert($db, "DELETE FROM `my_table`");
  die("> Database successfully cleaned up. Now change the \$delete to false.\n");
}

$i = 0;

$values = array();

$array_population_started = microtime(true);

printf("> Populating the values array... ");

for ($i=0; $i<$records_to_create; $i++){
  $values[] = sha1($i);
}

printf("[DONE] (%f)\n", microtime(true) - $array_population_started);


$logging_started = microtime(true);
$inserted = 0;

printf("> INSERT and logging in blocking mode...");

foreach ($values as $key => $value){
  $result = shesql_raw_query_insert($db, "INSERT INTO `my_table` VALUES($key, '$value');");
  $inserted++;
}

printf(" [%d/%d] (%f)\n", $inserted, count($values), microtime(true) - $logging_started);

shesql_disconnect($db);


printf("</pre>\n");

