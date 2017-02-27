<?hh // strict

namespace kilahm\Clio\Definition;

enum EffectCode : int as int
{
    bold      = 1;
    dark      = 2;
    italic    = 3;
    underline = 4;
    blink     = 5;
    reverse   = 7;
    concealed = 8;
}
