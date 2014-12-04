<?hh // strict

namespace kilahm\Clio\Exception;

class MissingOptionValue extends ClioException
{
    public function __construct(string $optionName)
    {
        $optionName = (strlen($optionName) > 1 ? '--' : '-') . $optionName;
        parent::__construct($optionName . ' requires a value.');
    }
}
