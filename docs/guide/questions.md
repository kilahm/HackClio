Asking Questions
================

You may prompt your user for a line of input by calling `$clio->ask()`.  To get the user’s response, simply call `getAnswer()` on the resulting question object.

## Simple use

```
#!php
<?hh
$answer = $clio->ask(‘What would you like to eat today?’)->getAnswer();
```

Hack Clio will send your question along with a prompt to `STDOUT`, then read a single line from `STDIN`.  The answer will be put through `trim()` so you don’t have to worry about
leading or trailing whitespace (including newlines).

## Choosing from a list of answers

You may limit the response from the user to a list of acceptable answers.  Simply call `withAnswers()` on the question object before getting the answer.

```
#!php
<?hh
$answer = $clio
    ->ask(‘What would you like to eat today?’)
    ->withAnswers(Vector{‘Toast’, ‘Waffles’, ‘Nothing’})
    ->getAnswer();
```

This will send the strings you passed as acceptable answers to `STDOUT` on a line between your question and the prompt.  If the user does not select one of the acceptable
answers, the question will be asked again with a prompt to select from the list.

## Formatting

### Formatting the question

You may format your question text before passing it into `$clio->ask()` using `$clio->format()` if you wish.

### Formatting the answers

Hack Clio will automatically format the list of acceptable answers, but if you wish to change the colors used, simply call `withAnswerStyle()` to pass in a valid `ColorStyle`
shape. This will only change the color style for the text of the answers.

```
#!php
<?hh
$style = shape(
    ‘fg’ => ForegroundCode::white,
    ‘bg’ => BackgroundCode::black,
    ‘effect’ => EffectCode::bold,
);
$answer = $clio
    ->ask(‘What day is it?’)
    ->withAnswers(Vector{‘Monday’, ‘Tuesday’, ‘Wednesday’})
    ->withAnswerStyle($style)
    ->getAnswer();
```

If you wish to also customize the seperator and brackets surrounding the answers, you may do so.

```
#!php
<?hh
$answerStyle = shape(
    ‘fg’ => ForegroundCode::white,
    ‘bg’ => BackgroundCode::black,
    ‘effect’ => EffectCode::bold,
);
$answer = $clio
    ->ask(‘What day is it?’)
    ->withAnswers(Vector{‘Monday’, ‘Tuesday’, ‘Wednesday’})
    ->withAnswerStyle($answerStyle)
    ->withAnswerBrackets(‘< ‘, ‘ >’)
    ->withSeperator(‘ OR ‘)
    ->getAnswer();
```

### Changing the prompt

By default, the prompt is the string `’ > ‘`.  If you wish to have a different prompt string, call `withPrompt()`.

```
#!php
<?hh
$answer = $clio
    ->ask(‘What day is it?’)
    ->withPrompt(‘Day: ‘)
    ->getAnswer();
```

If you would like to customize the color of the prompt, simply pass it through `$clio->format()` first.

## Saving the question

You may opt to store a question in a variable instead of getting the answer immediately.  You may do this if the question should be asked multiple times.

```
#!php
<?hh

// Get 10 lines of text
$question = $clio->ask(‘Please enter one line of text.’);
$text = Vector{};
foreach(range(0,10) as $i) {
    $text->add($question->ask());
}
```
