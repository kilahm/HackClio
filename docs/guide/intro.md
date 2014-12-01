Using Hack Clio
===============

Hack Clio strives to presents a fluent and straightforward API to allow your script to interact with its user through an ANSI compliant terminal. User prompts and
other formatted output is handled through the use of ANSI escape codes.

## Instantiating `Clio`

The recommended way of instantiating the Clio class as it will extract and interpret `$_SERVER[‘argv’]`.

```
#!php
<?hh // strict
$clio = Clio::fromCli();
```

 If you wish to supply your own set of arguments for Clio to parse, you may instantiate the class directly with the name of the executable and
a `Vector<string>` containing individual arguments and options (with the leading `-` or `--`)


## Format of examples

All of the example code below assumes there is a variable named `$clio` with an instance of `Clio` obtained through the factory method as described above.

Often there will be two code blocks.  The top one is typically an excerpt from a php script and the bottom one is invoking the script from the command line along with what is sent to
`STDOUT`.  The name of the script in all of the examples is `myscript.php`.

