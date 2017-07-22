### shesql (shes-kiu-el)
An elegant SQL connector for PHP.

### Features
 - Designed for performance, security *AND* simplicity in mind.
 - No surprises! Totally predictable.
 - Multi-policy.
 - Advanced logging.

### Logging
 - Non-blocking. (disabled by default)
 - Microseconds accuracy.
 - Supports file and remote logging.
 - Custom logging: 
   - "connect": "When did it connect."
   - "disconnect": "When did it disconnect."
   - "uptime": "For how long."
   - "microtime": Using `microtime(true)` as an addition to `date("Y-m-d H:i:s")`
   - "query": "What did it do and if failed, why?!"

`grep`-friendly!

### Supported databases
 - SQLite (INSERT)

### TODO
 - OOP interface.
 - MySQL support.
 - CRUD for SQLite.
 - Remote logging. (Redis, etc.)
 - Caching.
 - Input validation.
 - Logging the query size.

### Usage
```
$db = shesql_connect($database_info);

$result = shesql_raw_query_insert($db, "INSERT INTO `my_table` VALUES(1, 'ABC');");

shesql_disconnect($db);
```
(A working example is included in `index.php`)

### Example of a log file
```
2017-07-22 12:33:35 _SHESQL_CONNECT_
2017-07-22 12:33:35 _SHESQL_QUERY_STARTED_
2017-07-22 12:33:35 _SHESQL_QUERY_STRING_ [INSERT INTO `my_table` VALUES(6, 'XYZ');]
2017-07-22 12:33:35 _SHESQL_QUERY_FAILED_ [UNIQUE constraint failed: my_table.key]
2017-07-22 12:33:35 _SHESQL_DISCONNECT_
2017-07-22 12:33:35 _SHESQL_UPTIME_ 0.0022270679473877

2017-07-22 12:34:12 _SHESQL_CONNECT_
2017-07-22 12:34:12 _SHESQL_QUERY_STARTED_
2017-07-22 12:34:12 _SHESQL_QUERY_STRING_ [INSERT INTO `my_table` VALUES(7, 'XYZ');]
2017-07-22 12:34:12 _SHESQL_QUERY_SUCCESSFUL_
2017-07-22 12:34:12 _SHESQL_DISCONNECT_
2017-07-22 12:34:12 _SHESQL_UPTIME_ 0.15848016738892
```
<br/>

With `microtime`:
```
2017-07-22 12:29:30 _SHESQL_CONNECT_ (1500710370.2917)
2017-07-22 12:29:30 _SHESQL_QUERY_STARTED_ (1500710370.2918)
2017-07-22 12:29:30 _SHESQL_QUERY_STRING_ [INSERT INTO `my_table` VALUES('XYZ');] (1500710370.2919)
2017-07-22 12:29:30 _SHESQL_QUERY_FAILED_ [table my_table has 2 columns but 1 values were supplied] (1500710370.2922)
2017-07-22 12:29:30 _SHESQL_DISCONNECT_ (1500710370.2924)
2017-07-22 12:29:30 _SHESQL_UPTIME_ 0.00060391426086426

2017-07-22 12:29:30 _SHESQL_CONNECT_ (1500710370.7667)
2017-07-22 12:29:30 _SHESQL_QUERY_STARTED_ (1500710370.7668)
2017-07-22 12:29:30 _SHESQL_QUERY_STRING_ [INSERT INTO `my_table` VALUES('XYZ');] (1500710370.7669)
2017-07-22 12:29:30 _SHESQL_QUERY_FAILED_ [table my_table has 2 columns but 1 values were supplied] (1500710370.7672)
2017-07-22 12:29:30 _SHESQL_DISCONNECT_ (1500710370.7673)
2017-07-22 12:29:30 _SHESQL_UPTIME_ 0.00050687789916992

2017-07-22 12:31:01 _SHESQL_CONNECT_ (1500710461.1028)
2017-07-22 12:31:01 _SHESQL_QUERY_STARTED_ (1500710461.103)
2017-07-22 12:31:01 _SHESQL_QUERY_STRING_ [INSERT INTO `my_table` VALUES(6 'XYZ');] (1500710461.1031)
2017-07-22 12:31:01 _SHESQL_QUERY_FAILED_ [near "'XYZ'": syntax error] (1500710461.1032)
2017-07-22 12:31:01 _SHESQL_DISCONNECT_ (1500710461.1035)
2017-07-22 12:31:01 _SHESQL_UPTIME_ 0.00045299530029297

2017-07-22 12:31:08 _SHESQL_CONNECT_ (1500710468.5942)
2017-07-22 12:31:08 _SHESQL_QUERY_STARTED_ (1500710468.5945)
2017-07-22 12:31:08 _SHESQL_QUERY_STRING_ [INSERT INTO `my_table` VALUES(6, 'XYZ');] (1500710468.5945)
2017-07-22 12:31:08 _SHESQL_QUERY_SUCCESSFUL_ (1500710468.7867)
2017-07-22 12:31:08 _SHESQL_DISCONNECT_ (1500710468.7871)
2017-07-22 12:31:08 _SHESQL_UPTIME_ 0.1924889087677
```

And in case of a PHP-specific error:
```
2017-07-22 12:37:35 _PHP_: fopen(/root/test_db.sqlite): failed to open stream: Permission denied _at_ /var/www/shesql/public_html/shesql.php _line_ 37
```

With a fallback to PHP's default logger if something goes really wrong!
```
# tail -f /var/log/nginx/error.log

2017/07/22 03:45:53 [error] 968#0: *656 FastCGI sent in stderr: "PHP message: _SHESQL_PHP_: fopen(/root/test.log): failed to open stream: Permission denied _at_ /var/www/shesql/public_html/shesql.php _line_ 214" while reading response header from upstream, client: 127.0.0.1, server: shesql.local, request: "GET /shesql/shesql.php HTTP/1.1", upstream: "fastcgi://unix:/var/www/shesql/fastcgi/php5-fpm.sock:", host: "shesql.local"

```

## Benchmarks

Logging 100000 INSERT on SQLite in blocking(default) mode: 42.178443

Logging 100000 INSERT on SQLite in non-blocking mode: 34.413708
<br/>

Logging 200000 INSERT on SQLite in blocking(default) mode: 96.533679

Logging 200000 INSERT on SQLite in non-blocking mode: 89.514524

Note: In order to use the scripts in benchmarks/ directory, you need to have your database placed in a ramfs so the HDD and database writes won't be the bottleneck.

GNU/Linux example:
```
# mkdir /mnt/ramdisk
# mount -t tmpfs -o size=10m tmpfs /mnt/ramdisk
# chown USERNAME:USERNAME /mnt/ramdisk/test_db.sqlite
$ cd benchmarks/
$ php5 file_logging_blocking.php
```

### Versus your existing solution: (ask yourself :D)
How your code will behave if your sqlite file is not existing?

How about when it exists but you don't have read+execute permission on the directory which has the sqlite file in it?

(Try `<?php SQLite3("/root/test.sqlite") ?>`)
<br />

Where your logs are stored?

How about the PHP-specific errors such as warnings?


### Pitfalls
While using the procedural method for closing a logger instance, make sure you update your database instance as well:
```
shesql_logger_disconnect($logger);
shesql_disconnect_the_logger($db);

```
(Or you'll get `Warning:  fwrite(): 4 is not a valid stream resource` since PHP is not passing arguments by pointer/reference)

