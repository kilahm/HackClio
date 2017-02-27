<?hh // strict

namespace kilahm\Clio\Definition;

enum CliOptionType : string as string
{
    Value = 'Value';
    Accumulator = 'Accumulator';
    Flag = 'Flag';
    Path = 'Path';
    MultiValued = 'Multi-valued';
}
