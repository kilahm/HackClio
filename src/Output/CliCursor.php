<?hh // strict

namespace kilahm\Clio\Output;

class CliCursor
{
    public function up(int $n = 1) : void
    {
        $this->show($n . 'A');
    }

    public function down(int $n = 1) : void
    {
        $this->show($n . 'B');
    }

    public function right(int $n = 1) : void
    {
        $this->show($n . 'C');
    }

    public function left(int $n = 1) : void
    {
        $this->show($n . 'D');
    }

    public function lineDown(int $n = 1) : void
    {
        $this->show($n . 'E');
    }

    public function lineUp(int $n = 1) : void
    {
        $this->show($n . 'F');
    }

    public function startLine() : void
    {
        $this->show('1G');
    }

    public function top() : void
    {
        $this->show('H');
    }

    public function bottom() : void
    {
        $height = exec('tput lines');
        $this->show($height . 'H');
    }

    public function cls() : void
    {
        $this->show('2J');
    }

    public function cln() : void
    {
        $this->show('2K');
    }

    private function show(string $code) : void
    {
        echo '' . $code;
    }
}
