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

function shesql_connect($info){

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

function __shesql_connect_sqlite($info){

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

}

function shesql_uptime($shesql){

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

function shesql_disconnect(&$shesql){

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


function shesql_raw_query_insert(&$shesql, $query){
/*
 *  This function does *NOT* provide any input validation/sanitation
 *  and it's only used to do a raw INSERT query.
 */
  $database = NULL;
  $result = NULL;
  $log_message = NULL;
  $log_query = false;
  $log_microtime = false;

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

          if (isset($shesql["logger"]["what_to_log"]["microtime"])){
            if ($shesql["logger"]["what_to_log"]["microtime"] == true){
              $log_microtime = true;
            }
          }

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
    @$result = $database->exec($query);
  }

  $shesql["_last_query_finished_at"] = microtime(true);
  
  if ($result){

    $shesql["_last_query_successful"] = true;

    if ($log_query){

      $log_message = date("Y-m-d H:i:s") . " _SHESQL_QUERY_SUCCESSFUL_";

      if ($log_microtime){
        $log_message .= " (" . microtime(true) . ')';
      }

      if ($log_message){
        shesql_logger_log($shesql["logger"],  $log_message );
      }

    }

    if ($shesql["_type"] == "sqlite"){
      return $database->lastInsertRowID();
    }

    return 1;

  } else {

    $shesql["_last_query_successful"] = false;
    $shesql["_last_error_message"] = $database->lastErrorMsg();
    $shesql["_last_error_code"] = $database->lastErrorCode();

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


function shesql_logger_connect($info){

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


function __shesql_logger_connect_file($info){

  $handle = NULL;
  $error = NULL;

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
      }
    }

    return array(
      "_shesql_logger_var_" => true,
      "_type" => "file",
      "_file_handler" => $handle,
      "_status" => 1,
      "_connected_at" => microtime(true)
    );

  }

  return 0;
}

function shesql_logger_log(&$shesql_logger, $message){

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


function shesql_get_last_error_message($shesql){

  if ( !isset($shesql["_shesql_var"]) ){
    return -1; /* SHESQL_ERR_INVALID_SHESQL_VARIABLE */
  }

  if ( isset($shesql["_last_error_message"])){
    return $shesql["_last_error_message"];
  }

  return 0;
}

