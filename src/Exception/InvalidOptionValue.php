<?hh // strict

namespace kilahm\Clio\Exception;

class InvalidOptionValue extends ClioException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
