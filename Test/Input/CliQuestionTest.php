<?hh // strict

namespace kilahm\Clio\Test\Input;

use kilahm\Clio\Test\ClioTestCase;
use kilahm\Clio\Input\CliQuestion;

class CliQuestionTest extends ClioTestCase
{
    private function prepAnswers(Vector<string> $answers) : void
    {
        fwrite($this->in, implode(PHP_EOL, $answers));
        rewind($this->in);
    }

    private function expectOut(string $expectedOut) : void
    {
        $this->expect(stream_get_contents($this->out, -1, 0))
            ->toEqual($expectedOut);
    }
    public function testQuestionIsPrinted() : void
    {
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{'Answer'});
        $clio->ask('The Question')->getAnswer();
        $this->expectOut(PHP_EOL . 'The Question' . PHP_EOL . ' > ');
    }

    public function testQuestionGetsFirstAnswer() : void
    {
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{'Answer', 'Other Answer'});
        $answer = $clio->ask('The Question')->getAnswer();
        $this->expect($answer)->toBeIdenticalTo('Answer');
    }

    public function testQuestionKeepsAskingUntilValidAnswerIsGiven() : void
    {
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{
            'Wrong Answer',
            'Other wrong answer',
            'Right answer'
        });
        $answer = $clio
            ->ask('Q')
            ->withAnswers(Vector{'Right answer', 'Answer not given'})
            ->getAnswer();
        $this->expect($answer)->toBeIdenticalTo('Right answer');
    }
}
