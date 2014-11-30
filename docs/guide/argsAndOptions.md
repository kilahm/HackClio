Command line argument/option parsing
====================================

Hack Clio allows you to easily define and access any number of arguments (required or optional) and options (indicated with `-` or `--`, and are always optional).

## Command line arguments

To easily access any arguments the user passed to the initial call of your script, simply call `getArgVals()`.

```php
var_dump($clio->getArgVals());
```

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

Note that the result is a `Map<string,string>`.  This is because you can make named arguments, which then become required arguments.

```php
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
$testArg = $clio->arg(‘test’);
var_dump($testArg->getVal());
```

```sh
$ myscript.php myArg1 myArg2
string(6) “myArg1”
```

## Command line options

To define short and long options
