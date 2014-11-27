<?hh // strict

namespace kilahm\Clio\Exception;

class MissingOptionValue extends ClioException
{
    public function __construct(string $optionName)
    {
        parent::__construct('Option "' . $optionName . '" requires a value.');
    }
}
