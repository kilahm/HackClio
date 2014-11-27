<?hh // strict

namespace kilahm\Clio\Exception;

class InvalidOptionName extends ClioException
{
    public function __construct(string $name)
    {
        parent::__construct('This script defines an invalid option name: ' . $name);
    }
}
