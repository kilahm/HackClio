Output Formatting
=================

Hack Clio is able to format your text before you echo it to the terminal.

```
#!php
<?hh
$style = shape(
    ‘fg’ => ForegroundCode::cyan,
    ‘bg’ => BackgroundCode::yellow,
    ‘effect’ => EffectCode::italic,
);
echo $clio
    ->format(‘Some text to format’)
    ->withStyle($style);
```

The above would print the text `Some text to format` in cyan with a yellow background in italic font.  This is achieved through the use of ANSI control codes.
For a full list of the possible codes, see (insert internal link to) ColorStyle Shapes.

## Formatting Model

The formatter is based on the ideas of margin and padding.  Margins define how far from the edges of the terminals the text will appear.  Padding defines how far from the start
of the styled text the actual string will appear.

You may define margins and padding as fractions of the width of the terminal screen or as absolute character counts.


More documentation to follow...


