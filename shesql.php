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

function shesql_connect(array $info){

  if (isset($info["type"])){

    switch ($info["type"]){
      case "sqlite":
        return __shesql_connect_sqlite($info);
        break;

      default:
        return -1; /* SHESQL_ERR_INVALID_DATABASE_TYPE */
    }

  }


  return 0;
}


function __shesql_connect_sqlite(array $info){

  $handle = NULL;
  $error = NULL;
  $logger = NULL;
  $log_message = NULL;

  if (isset($info["path"])){

  /*
   * SQLite3 returns a fatal error if it can't create the database
   * so we simply try to get it before in order to prevent the fatal error
   * and breaking the execution flow unexpectedly.
   */

    @$handle = fopen($info["path"], 'r');

    if(!$handle){

      if ($error = error_get_last()){

        if (isset($info["logger"])){
          shesql_logger_log($info["logger"],  date("Y-m-d H:i:s") . " _PHP_: {$error["message"]} _at_ {$error["file"]} _line_ {$error["line"]}");
        } else {
          error_log( date("Y-m-d H:i:s") . " _SHESQL_PHP_: {$error["message"]} _at_ {$error["file"]} _line_ {$error["line"]}");
        }

      }

      return -3; /* SHESQL_ERR_CAN_NOT_OPEN_DATABASE_FILE */
    }

    if (! $database = new SQLite3($info["path"])){
      return -4; /* SHESQL_ERR_NEW_SQLITE_OBJECT_ERROR */
    }

    if (isset($info["logger"])){

      if (isset($info["what_to_log"])){

        if (isset($info["what_to_log"]["connect"])){

          if ($info["what_to_log"]["connect"] == true)

            $log_message = "\n" . date("Y-m-d H:i:s") . " _SHESQL_CONNECT_";

            if (isset($info["what_to_log"]["microtime"])){

              if ($info["what_to_log"]["microtime"] == true){
                $log_message .= " (" . microtime(true) . ')';
              }

            }

            if ($log_message){
              shesql_logger_log($info["logger"],  $log_message );
            }

          }

        }

      $logger = $info["logger"];
      $logger["what_to_log"] = $info["what_to_log"];
    }

    return array(
      /* Used to make sure the right variable is always passed */
      "_shesql_var" => true,

      /* The database(object) itself */
      "_database" => $database,

      "logger" => $logger,

      "_type" => "sqlite",

      /* Setting the status to SHESQL_STATUS_CONNECTED */
      "_status" => 1,

      "_connected_at" => microtime(true)
    );

  } else {
    return -2; /* SHESQL_ERR_NO_SQLITE_DATABASE_PATH_SUPPLIED */
  }

  return 0;
}


function shesql_uptime(array $shesql){

  if ( !isset($shesql["_shesql_var"]) ){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if ( isset($shesql["_status"]) ){

    if ( isset($shesql["_connected_at"]) ){
      return microtime(true) - $shesql["_connected_at"];
    }

  }

  return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
}

function shesql_disconnect(array &$shesql){

  $database = NULL;
  $log_message = NULL;

  if (!isset($shesql["_shesql_var"])){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if (isset($shesql["_database"])){

    if (isset($shesql["_status"])){

      $database = $shesql["_database"];

      if ($shesql["_status"] == 1){
        /* 
         * Not using the $shesql["_database"]->close();
         * so it will work on older versions of PHP as well.
         */
        $database->close();
        unset($shesql["_database"]);
        $shesql["_status"] = 2; /* SHESQL_STATUS_CONNECTED */
        $shesql["_disconnected_at"] = microtime(true);

        if (isset($shesql["logger"])){

          if (isset($shesql["logger"]["what_to_log"])){

            if (isset($shesql["logger"]["what_to_log"]["disconnect"])){

              if ($shesql["logger"]["what_to_log"]["disconnect"] == true)

                $log_message = date("Y-m-d H:i:s") . " _SHESQL_DISCONNECT_";

                if (isset($shesql["logger"]["what_to_log"]["microtime"])){

                  if ($shesql["logger"]["what_to_log"]["microtime"] == true){
                    $log_message .= " (" . microtime(true) . ')';
                  }

                }

                if (isset($shesql["logger"]["what_to_log"]["uptime"])){
                  if ($shesql["logger"]["what_to_log"]["uptime"] == true){
                    $log_message .= "\n" . date("Y-m-d H:i:s") . " _SHESQL_UPTIME_ " . ($shesql["_disconnected_at"] - $shesql["_connected_at"]);
                  }
                }

                if ($log_message){
                  shesql_logger_log($shesql["logger"],  $log_message );
                }

              }

            } /* Nothing to log */

        }

        return 1;
      }

      return -2; /* SHESQL_ERR_NOT_CONNECTED */
    }

  }

  return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
}


function __shesql_logger_is_microtime_enabled(array &$shesql){
/* TODO: Implement it! */
}


function __shesql_raw_query(array &$shesql, $query, $query_type=0){
  /*
   * 0 -> SELECT
   * 1 -> INSERT
   * 2 -> UPDATE
   * 3 -> DELETE
   */

  $database = NULL;
  $query_result = NULL;
  $log_message = NULL;
  $log_query = false;
  $log_microtime = false;
  $select_return_rows = array();


  if ( !isset($shesql["_shesql_var"]) ){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if ( !isset($shesql["_database"]) ){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if ( !isset($shesql["_status"]) ){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if ( !isset($shesql["_type"]) ){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if ( $shesql["_status"] != 1 ){
    return -2; /* SHESQL_ERR_NOT_CONNECTED */
  }

  $database = $shesql["_database"];

  $shesql["_last_query_started_at"] = microtime(true);

  if (isset($shesql["logger"])){
    if (isset($shesql["logger"]["what_to_log"])){

      if (isset($shesql["logger"]["what_to_log"]["query"])){
        if ($shesql["logger"]["what_to_log"]["query"] == true){
          $log_query = true;
        }
      }

      if (isset($shesql["logger"]["what_to_log"]["microtime"])){
        if ($shesql["logger"]["what_to_log"]["microtime"] == true){
          $log_microtime = true;
        }
      }

    }
  }

  if ($log_query){

    $log_message = date("Y-m-d H:i:s") . " _SHESQL_QUERY_STARTED_";

    if ($log_microtime){
      $log_message .= " (" . $shesql["_last_query_started_at"] . ')';
    }

    if (isset($shesql["logger"]["_type"])){

      if ($shesql["logger"]["_type"] == "file" ){
      /*
       *  If the logger is storing the logs on a file,
       *  We just append a newline to it so we use less
       *  systemcall and do the write(2) only once.
       */

        $log_message .= "\n" . date("Y-m-d H:i:s") . " _SHESQL_QUERY_STRING_ [" . $query;

        if ($log_microtime){
          $log_message .= "] (" . microtime(true) . ')';
        } else {
          $log_message .= ']';
        }

      }

    }

    if ($log_message){
      shesql_logger_log($shesql["logger"],  $log_message );
    }

  }

  if ($shesql["_type"] == "sqlite"){
    @$query_result = $database->query($query);
  }

  $shesql["_last_query_finished_at"] = microtime(true);
  
  if ($query_result){

    $shesql["_last_query_successful"] = true;

    if ($log_query){

      $log_message = date("Y-m-d H:i:s") . " _SHESQL_QUERY_SUCCESSFUL_";

      if ($log_microtime){
        $log_message .= " (" . microtime(true) . ')';
      }

      shesql_logger_log($shesql["logger"],  $log_message );

    }


    if ($shesql["_type"] == "sqlite"){

      if ($query_type === 0){ /* SELECT */

        while ($row = $query_result->fetchArray(SQLITE3_ASSOC)){
          $select_return_rows[] = $row;
        }
        if (count($select_return_rows)){

          if (!isset($select_return_rows[1])){
            return $select_return_rows[0];
          }
  
          return $select_return_rows;
        }

      }

      if ($query_type === 1){ /* INSERT */
        return $database->lastInsertRowID();
      }

    }

  } else {

    $shesql["_last_query_successful"] = false;

    if ($shesql["_type"] == "sqlite"){
      $shesql["_last_error_message"] = $database->lastErrorMsg();
      $shesql["_last_error_code"] = $database->lastErrorCode();
    }

    if ($log_query){

      $log_message = date("Y-m-d H:i:s") . " _SHESQL_QUERY_FAILED_ [" . $shesql["_last_error_message"];

      if ($log_microtime){
        $log_message .= "] (" . microtime(true) . ')';
      } else {
        $log_message .= ']';
      }

      if ($log_message){
        shesql_logger_log($shesql["logger"],  $log_message );
      }

    }

  }

  return 0;
}


function shesql_logger_connect(array $info){

  if (isset($info["type"])){  

    switch ($info["type"]){

      case "file":
        return __shesql_logger_connect_file($info);
        break;

      default:
        return -1; /* SHESQL_LOGGER_ERR_INVALID_TYPE */

    }

  }

  return 0; /* SHESQL_LOGGER_ERR_INVALID_VARIABLE */
}


function __shesql_logger_connect_file(array $info){

  $handle = NULL;
  $error = NULL;
  $blocking = true;

  if (isset($info["path"])){

    @$handle = fopen($info["path"], 'a');

    if (!$handle){

      $error = error_get_last();

      /* We log the error to PHP's default logger if we can't open the specified log file. */
      error_log( date("Y-m-d H:i:s") . "_SHESQL_PHP_: {$error["message"]} _at_ {$error["file"]} _line_ {$error["line"]}");

      return -2; /* SHESQL_LOGGER_ERR_CAN_NOT_OPEN_LOG_FILE */
    }

    if (isset($info["blocking"])){
      if ($info["blocking"] == false){
        stream_set_blocking($handle, false);
        $blocking = false;
      }
    }

    return array(
      "_shesql_logger_var_" => true,
      "_type" => "file",
      "_file_handler" => $handle,
      "_blocking" => $blocking,
      "_status" => 1,
      "_connected_at" => microtime(true)
    );

  }

  return 0;
}


function shesql_logger_log(array &$shesql_logger, $message){

  $result = false;
  $error = NULL;

  if ( !isset($shesql_logger["_shesql_logger_var_"]) ){
    return -1; /* SHESQL_LOGGER_ERR_INVALID_LOGGER_VARIABLE */
  }

  if ( !isset($shesql_logger["_type"]) ){
    return -1;
  }

  if ( !isset($shesql_logger["_status"]) ){
    return -1;
  }


  if ($shesql_logger["_status"] != 1){
    return -2; /* SHESQL_LOGGER_ERR_NOT_CONNECTED */
  }

  if ($shesql_logger["_type"] == "file"){
    if ( !isset($shesql_logger["_file_handler"]) ){
      return -3; /* SHESQL_LOGGER_ERR_INVALID_FILE_HANDLER */
    }
  }

  $shesql_logger["_last_log_started_at"] = microtime(true);

  if ($shesql_logger["_type"] == "file"){

    $result = fwrite($shesql_logger["_file_handler"], $message."\n");

  }

  if ($result){

    $shesql_logger["_last_log_finished_at"] = microtime(true);

    if ($shesql_logger["_type"] == "file"){
      /*
       * In case of logging into file, we also need to make sure
       * that the we've logged the whole message.
       */
      if ($result == strlen($message)){
        $shesql_logger["_last_log_successful"] = true;
      } else {

        if ($error = error_get_last()){
          error_log( date("Y-m-d H:i:s") . " _SHESQL_PHP_: {$error["message"]} _at_ {$error["file"]} _line_ {$error["line"]}");
        }

      }

    }

    $shesql_logger["_last_log_length"] = $result;
    $shesql_logger["_last_log_execution_time"] = $shesql_logger["_last_log_finished_at"] - $shesql_logger["_last_log_started_at"];

    return 1;

  } else {

    /* We log the error to PHP's default logger if we can't write in the specified log file. */
    error_log( date("Y-m-d H:i:s") . " _SHESQL_PHP_: {$error["message"]} _at_ {$error["file"]} _line_ {$error["line"]}");

    return -4; /* SHESQL_LOGGER_ERR_PHP_FWRITE */
  }

  return 0;
}


function shesql_get_last_error_message(array $shesql){

  if ( !isset($shesql["_shesql_var"]) ){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if ( isset($shesql["_last_error_message"])){
    return $shesql["_last_error_message"];
  }

  return 0;
}

function shesql_logger_disconnect(array &$shesql_logger){

  if ( !isset($shesql_logger["_shesql_logger_var_"]) ){
    return -1; /* SHESQL_LOGGER_ERR_INVALID_LOGGER_VARIABLE */
  }

  if ( isset($shesql_logger["_file_handler"]) ){

    if ( isset($shesql_logger["_status"]) ){

      if ($shesql_logger["_status"] == 1){
        $shesql_logger["_status"] = 2; /* SHESQL_LOGGER_DISCONNECTED */
        return fclose($shesql_logger["_file_handler"]);
      }

    }

  }

  return 0;
}

function shesql_disconnect_the_logger(&$shesql){

  if (!isset($shesql["_shesql_var"])){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if (!isset($shesql["logger"])){
    return -2; /* SHESQL_ERR_NOT_A_VALID_LOGGER */
  }

  if (!isset($shesql["logger"]["_shesql_logger_var_"])){
    return -2; /* SHESQL_ERR_NOT_A_VALID_LOGGER */
  }

  unset($shesql["logger"]);

  return 0;
}

function shesql_logger_set_blocking_mode_file(array &$shesql_logger, $mode){

  $result = NULL;

  if ( !isset($shesql_logger["_shesql_logger_var_"]) ){
    return -1; /* SHESQL_LOGGER_ERR_INVALID_LOGGER_VARIABLE */
  }

  if ( isset($shesql_logger["_file_handler"]) ){

    if ( isset($shesql_logger["_status"]) && isset($shesql_logger["_blocking"]) ){

      if ($shesql_logger["_status"] == 1){
        if ($mode == 0){

          if ($shesql_logger["_blocking"] == true){

            $result = stream_set_blocking($shesql_logger["_file_handler"], false);

            shesql_logger_log($shesql_logger, date("Y-m-d H:i:s") . " _SHESQL_LOGGER_FILE_IS_NON_BLOCKING_NOW_");

          }

          $shesql_logger["_blocking"] = false;

        } else {
          if ($shesql_logger["_blocking"] == false){

            $result = stream_set_blocking($shesql_logger["_file_handler"], true);

            shesql_logger_log($shesql_logger, date("Y-m-d H:i:s") . " _SHESQL_LOGGER_FILE_IS_BLOCKING_NOW_");

            $shesql_logger["_blocking"] = true;

          }
        }

      }

    }

    return $result;
  }

  return 0;
}


function shesql_string_is_safe_for_database($string){

  /*
   *  According to: http://php.net/manual/en/function.mysql-real-escape-string.php
   */

  $invalid_characters = array(
    "\x00",
    "\n",
    "\r",
    "\\",
    "'",
    "\"",
    "\x1a"
  );

  foreach ($invalid_characters as $invalid_char){

    if (mb_substr_count($string, $invalid_char)){
      return 0; /* SHESQL_CONDITION_ERR_ILLEGAL_CHARACTER */
    }

  }

  return 1;

}


function shesql_query_select(array &$shesql, array $query_array, array $conditions_array=NULL){

  $sql_query = NULL;
  $condition_query = NULL;
  $log_microtime = false;
  $log_sqli = false;  
  $log_array = array();
  $invalid_strings_found = array();
  $log_to_php = true;

  if ( !isset($shesql["_shesql_var"]) ){
    if ($shesql["_shesql_var"] != true){
      return -1;
    }
  }

  if ( isset($shesql["logger"]) ){
    if (isset($shesql["logger"]["_shesql_logger_var_"])){
      if ($shesql["logger"]["_shesql_logger_var_"] == true){
        $log_to_php = false;
      }
    }
  }

  $sql_query = __shesql_generate_sql_from_select_query_array($query_array);

  if (!$sql_query){
    return -2;
  }

  foreach ($query_array["columns"] as $column_name){
    if (!shesql_string_is_safe_for_database($column_name)){
      $invalid_strings_found[] = $column_name;
    }
  }

  if (!shesql_string_is_safe_for_database($query_array["table"])){
    $invalid_strings_found[] = $query_array["table"];
  }

  if ($conditions_array){

    $condition_query = __shesql_conditions_to_sql($conditions_array);

    foreach ($conditions_array as $condition){

      if (!shesql_string_is_safe_for_database($condition["column"])){
        $invalid_strings_found[] = $condition["column"];
      }

      if (!shesql_string_is_safe_for_database($condition["value"])){
        $invalid_strings_found[] = $condition["value"];
      }

    }

  }


  if(count($invalid_strings_found)){

    $log_array["query"] = $sql_query;
    $log_array["invalid_strings"] = $invalid_strings_found;

    if ($log_to_php){

      /* XXX: IMPLEMENT LOG TO PHP */

    } else {
      __shesql_logger_log_security_alert($log_array, 0/* SQL_INJECTION */, $shesql["logger"]);
    }

    return -3; /* HACKING_ATTEMPT */
  }

  if ($condition_query){
    $sql_query .= $condition_query;
  }

  return __shesql_raw_query($shesql, $sql_query, 0/*SELECT*/);
}


function __shesql_generate_sql_from_select_query_array(array $query_array){

  $query = NULL;
  $total_columns_count = 0;
  $current_column = 0;

  if (!isset($query_array["columns"])){
    return -2; /* SHESQL_INVALID_QUERY_ARRAY */
  } else {
    if (!is_array($query_array["columns"])){
      return -2; /* SHESQL_INVALID_QUERY_ARRAY */
    }
  }

  if (!isset($query_array["table"])){
    return -2; /* SHESQL_INVALID_QUERY_ARRAY */
  } else {
    if (!is_string($query_array["table"])){
      return -2; /* SHESQL_INVALID_QUERY_ARRAY */
    }
  }

  $total_columns_count = count($query_array["columns"]);

  $query = "SELECT";

  if ($total_columns_count == 1 && array_search('*', $query_array["columns"]) == 0){

    $query .= " * ";

  } else {

    foreach ($query_array["columns"] as $column){

      $current_column++;

      /* TODO: What if the column was '*' in between of other column names?  */
      $query .= " `$column`";

      if ($current_column < $total_columns_count){
        $query .= ", ";
      } else {
        $query .= ' ';
      }

    }

  }

  $query .= "FROM `{$query_array["table"]}`";

  return $query;
}


function __shesql_logger_log_security_alert(array $log_array, $type=0, &$shesql_logger=NULL){

  /* XXX
   * ESCAPING THE STRING BEFORE STORING IT ANYWHERE!
   */
  $current_invalid_string = 0;
  $log_microtime = false;

  if (!isset($log_array["query"])){
    return -1;
  }

  if (!isset($log_array["invalid_strings"])){
    return -1;
  }

  if (isset($shesql_logger["what_to_log"])){
    if (isset($shesql_logger["what_to_log"]["microtime"])){
      if ($shesql_logger["what_to_log"]["microtime"] == true){
        $log_microtime = true;
      }
    }
  }

  $total_invalid_strings = count($log_array["invalid_strings"]);

  if ($shesql_logger){

    $log_message = date("Y-m-d H:i:s") . " _SHESQL_SECURITY_ALERT_SQL_INJECTION_";
    $log_message .= " [_ATTACKER_IP_ " . __shesql_get_client_ip() . ']';
    $log_message .= " [{$log_array["query"]}] ";

    if ($total_invalid_strings){

      $log_message .= '[';

      foreach ($log_array["invalid_strings"] as $invalid){

        $current_invalid_string++;
        $log_message .= $invalid;

        if ($current_invalid_string < $total_invalid_strings){
          $log_message .= ', ';
        }

        $log_message .= ']';

      }

    }

    if ($log_microtime){
      $log_message .= " (" . microtime(true) . ')';
    }

    if (shesql_logger_log($shesql_logger,  $log_message)){
      return 1;
    }

  }

  return 0;
}


function __shesql_conditions_to_sql(array $conditions){

  $total_conditions = 0;
  $current_condition = 0;
  $query = NULL;

  if (!is_array($conditions)){
    return -1; /* SHESQL_CONDITION_ERR_NOT_ARRAY */
  }

  if (empty($conditions)){
    return -2; /* SHESQL_CONDITION_ERR_EMPTY_ARRAY */
  }

  $total_conditions = count($conditions);

  $query = " WHERE";

  foreach ($conditions as $condition){

    $current_condition++;

    $query .= " `{$condition["column"]}`";

    switch ($condition["condition"]){

      case '=':
      case "eq":
        $query .= '=';
        break;

      case "!=":
      case "ne":
        $query .= "!=";
        break;

      case "<":
      case "lt":
        $query .= '<';
        break;

      case ">":
      case "gt":
        $query .= '>';
        break;

      case "<=":
      case "le":
        $query .= "<=";
        break;

      case ">=":
      case "ge":
        $query .= '>=';
        break;

      case "LIKE":
      case "like":
        $query .= " LIKE ";
        break;

      default:
        return -3; /* SHESQL_CONDITION_ERR_INVALID_CONDITION */

    }

    if (intval($condition["value"])){

      $query .= $condition["value"];

    } else {

      $query .= '"';

      if ($condition["condition"] == "like" || $condition["condition"] == "LIKE"){
        $query .= '%';
      }

      $query .= $condition["value"];

      if ($condition["condition"] == "like" || $condition["condition"] == "LIKE"){
        $query .= '%';
      }

      $query .= '"';

    }

      if ($current_condition < $total_conditions){
        $query .= " AND";
      }

  }

  if ($current_condition > 0 && ($current_condition == $total_conditions)){
    return $query;
  }

  return 0;
}


function __shesql_get_client_ip() {
    $ip = '';

    if (isset($_SERVER["HTTP_CLIENT_IP"]))
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    else if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    else if(isset($_SERVER["HTTP_X_FORWARDED"]))
        $ip = $_SERVER["HTTP_X_FORWARDED"];
    else if(isset($_SERVER["HTTP_FORWARDED_FOR"]))
        $ip = $_SERVER["HTTP_FORWARDED_FOR"];
    else if(isset($_SERVER["HTTP_FORWARDED"]))
        $ip = $_SERVER["HTTP_FORWARDED"];
    else if(isset($_SERVER["REMOTE_ADDR"]))
        $ip = $_SERVER["REMOTE_ADDR"];
    else
        $ip = "UNKNOWN";

    return $ip;
}
