<?hh // strict

namespace kilahm\Clio\Exception;

class UnknownOption extends ClioException
{
    public function __construct(string $optionName)
    {
        $optionName = (strlen($optionName) > 1 ? '--' : '-') . $optionName;
        parent::__construct('Unknown option: ' . $optionName);
    }
}
