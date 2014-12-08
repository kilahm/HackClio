<?hh // strict

namespace kilahm\Clio\Enum;

enum CliOptionType : string as string
{
    Value = 'Value';
    Accumulator = 'Accumulator';
    Flag = 'Flag';
    Path = 'Path';
    MultiValued = 'Multi-valued';
}

enum ForegroundCode : int as int
{
    normal     = 39;
    black      = 30;
    red        = 31;
    green      = 32;
    yellow     = 33;
    blue       = 34;
    magenta    = 35;
    cyan       = 36;
    light_gray = 37;

    dark_gray     = 90;
    light_red     = 91;
    light_green   = 92;
    light_yellow  = 93;
    light_blue    = 94;
    light_magenta = 95;
    light_cyan    = 96;
    white         = 97;

}

enum BackgroundCode : int as int
{
    normal     = 49;
    black      = 40;
    red        = 41;
    green      = 42;
    yellow     = 43;
    blue       = 44;
    magenta    = 45;
    cyan       = 46;
    light_gray = 47;

    dark_gray     = 100;
    light_red     = 101;
    light_green   = 102;
    light_yellow  = 103;
    light_blue    = 104;
    light_magenta = 105;
    light_cyan    = 106;
    white         = 107;
}

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

enum CliTextAlign : string
{
    Left   = 'left';
    Right  = 'right';
    Center = 'center';
}
