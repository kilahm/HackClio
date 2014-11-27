HackClio
========

Fluent command line input and output library for Hack.  This library is useful to making command line scripts that require user interaction.

Use
===

## Command line argument/options parsing

To easily access any arguments the user passed to the inital call of your script, simply access them through `getArgVals()`.

Contents of `myscript.php`
```php
<?hh // strict
$clio = new Clio();
var_dump($clio->getArgVals());
```

Run from the command line
```sh
$ myscript.php myArg1 myArg2
object(HH\Map)#1 (2) {
  [“1”]=>
  string(6) “myArg1”
  [“2”]=>
  string(6) “myArg2”
  }
}
```

Note the the result is a `Map<string,string>`.  This is because you can make named arguments, which then become required arguments.

```php
<?hh // strict
$clio = new Clio();
$clio->arg(‘test’);
var_dump($clio->getArgVals());
```

```sh
$ myscript.php myArg1 myArg2
object(HH\Map)#1 (2) {
  [“test”]=>
  string(6) “myArg1”
  [“2”]=>
  string(6) “myArg2”
  }
}
```

You may also save the argument object to be passed around later.

```php
<?hh // strict
$clio = new Clio();
$testArg = $clio->arg(‘test’);
var_dump($testArg->getVal());
```

```sh
$ myscript.php myArg1 myArg2
string(6) “myArg1”
```

More documentation to follow...
