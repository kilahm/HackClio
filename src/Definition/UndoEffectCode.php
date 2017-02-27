<?hh // strict

namespace kilahm\Clio\Definition;

enum UndoEffectCode : int as int
{
    bold      = 22;
    dark      = 22;
    italic    = 23;
    underline = 24;
    blink     = 25;
    reverse   = 27;
    concealed = 28;
}
