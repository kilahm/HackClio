Command line argument/option parsing
====================================

Hack Clio allows you to easily define and access any number of arguments (required or optional) and options (indicated with `-` or `--`, and are always optional).
Arguments are taken from the invocation of your script.  The entire command is split on non-quoted space characters.

```
$ myscript.php -a arg1 --long-option arg2 arg3 -more --options
```

The above script invocation would produce the following items as generic arguments (i.e., are the elements of `$_SERVER[‘argv’]`)

* myscript.php
* -a
* arg1
* --long-option
* arg2
* arg3
* -more
* --options

## Command line arguments

To easily access any arguments the user passed to the initial call of your script, simply call `getArgVals()`.

```
#!php
<?hh
var_dump($clio->getArgVals());
```

```
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

```
#!php
<?hh
$clio->arg(‘test’);
var_dump($clio->getArgVals());
```

```
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

```
#!php
<?hh
$testArg = $clio->arg(‘test’);
var_dump($testArg->getVal());
```

```
$ myscript.php myArg1 myArg2
string(6) “myArg1”
```

## Command line options

Defining options is very similar to arguments, but there are more options for defining options.

To define an option, invoke `$clio->arg(‘name’)` where `’name’` is the string used to identify the option when invoking your script.

```
#!php
<?hh
$option = $clio->opt(‘name’);
```

The above script would recognize the option `--name`.

Option names may be composed of letters, dash (`-`), and underscore (`_`).  The first character of the option name must be a letter.

### Long and Short Options

If the name of the option is a single letter, it is considered a “short option” which may be set with a single dash character.  Multiple short options may be set by
chaining all of the letters together after a single dash.

```
$ myscript -abcef -er
```

The above script invocation would set flags a, b, c, e, f, e, and r (a single option may appear multiple times, see accumulator type options below).  The exception to this rule
is if an option accepts a value.

If the name of an option is more than one letter, it is a “long option” which may be set with two dash characters.  You may only set one long option per double dash.

```
$ myscript --long-optionA --other_long_option
```

The above script would set flags long-optionA and other\_long\_option.

### Option types

There are four types of option:

- Flag
- Accumulator
- Value
- Path

#### Flag Options

Flag options are the simplest and the default.  They have no value and are merely present or not.  You can ask the option if it was present when your script was invoked.

```
#!php
<?hh
$option = $clio->opt(‘name’);
var_dump($option->wasPresent());
```

```
$ myscript.php --name; myscript.php;
bool(true)
bool(false)
```

#### Accumulator Options

Accumulator options will count the number of times they are present.  They are useful for options like verbosity. You may retrieve the number of times the option
occurred when your script was invoked by calling `getCount()`;

```
#!php
<?hh
$option = $clio->opt(‘v’)->accumulates();
var_dump($option->getCount());
```

```
$ myscript.php -v; myscript.php -vvv;
int(1)
int(3)
```

#### Value Options

Options may accept values that can be retrieved by calling `getVal()`.  You must tell Clio the option should be accepting a value because that changes how the
script invocation line is parsed.

```
#!php
<?hh
$flag = $clio->opt(‘a’); // Define a flag option
$option = $clio->opt(‘n’)->aka(‘name’)->withValue(); // Define a value option

var_dump($option->getVal());
```

Short option values will either be the rest of the current argument, or the entirety of the following argument as long as the following argument does not start with a dash.
Long option values may either be separated from the option name by `=` or may be the following argument.

```
$ myscript.php -na Name; myscript.php -anName;
string(1) “a”
string(4) “Name”
```

The first time the script is invoked, the short option `n` is first in the list.  The rest of the argument is interpreted as the value of the option, which is the single
character `”a”`.  The second time, the flag `a` is set, then the option `n` is encountered.  The rest of the argument, and thus the value of the option is the string `”Name”`.

```
$ myscript.php --name Name; myscript.php --name=”Name”
string(4) “Name”
string(4) “Name”
```

Both invocations produce the same value for the option.  The value does not include the quotes in the second case because bash parses them before the arguments are passed to your script.
If your shell treats quotes differently, then you should account for that in your script.

Value options may also be given a default value.  Simply pass a string into the `withValue()` method.

```
#!php
$option = $clio->opt(‘name’)->withValue(‘Default name’)
```

#### Path Options

Path options are identical to value options except that an exception will be thrown if the value is not an existing path when the arguments and options are parsed.

### Option aliases
Each option has a primary name and may have any number of aliases.

```
#!php
<?hh

$option = $clio->opt(‘long-name’)->aka(‘a’);
var_dump($option->wasPresent());
```

```
$ myscript.php -a; myscript --long-name;
bool(true)
bool(true)
```

