<?hh // strict

namespace kilahm\Clio\Test;

use HackPack\HackUnit\Core\TestCase;
use kilahm\Clio\Clio;

class ClioTestCase extends TestCase
{
    protected resource $in;
    protected resource $out;

    public function __construct(string $name)
    {
        $this->in = fopen('php://memory', 'w+');
        $this->out = fopen('php://memory', 'w+');
        parent::__construct($name);
    }

    <<__Override>>
    public function tearDown() : void
    {
        rewind($this->in);
        ftruncate($this->in, 0);
        rewind($this->out);
        ftruncate($this->out, 0);
    }

    protected function makeClio(Vector<string> $argv = Vector{}) : Clio
    {
        $clio = new Clio('unit', $argv, $this->in, $this->out);
        $clio->throwOnParseError();
        return $clio;
    }
}
