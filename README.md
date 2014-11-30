HackClio
========
[![Build Status](https://travis-ci.org/kilahm/HackClio.svg?branch=master)](https://travis-ci.org/kilahm/HackClio)

Fluent command line input and output library for Hack.  This library is useful to making command line scripts that require user interaction.

## Installation

The only option currently supported is to install through [Composer](https://getcomposer.org/).  Add the following line to your `required` block:

```json
“kilahm/hack-clio”: “~0.1”
```

## Features

Hack Clio allows you to define arguments and options for the command line invocation of your script. There is a built in help compiler that allows
you to print a user friendly description of the arguments and options you define.

\[planned feature\] You can easily ask for input from your user including input validation.

Format text output with colors, indentation, centering, etc.  All of this is also “responsive” to the width of your terminal window.

See the [documentation](http://hackclio.readthedocs.org/en/latest/) for more details.
