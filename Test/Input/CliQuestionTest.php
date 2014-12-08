<?hh // strict

namespace kilahm\Clio\Test\Input;

use kilahm\Clio\Test\ClioTestCase;
use kilahm\Clio\Input\CliQuestion;
use kilahm\Clio\Output\CliColor;

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

    public function testAnswersArePrinted() : void
    {
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{'a3'});
        $clio
            ->ask('The question')
            ->withAnswers(Vector{'a1', 'a2', 'a3'})
            ->withAnswerStyle(CliColor::plain())
            ->getAnswer();
        $this->expectOut(PHP_EOL . 'The question'
            . PHP_EOL . ' [ a1 | a2 | a3 ]'
            . PHP_EOL . ' > ');
    }

    public function testCustomSeperatorAndBracketsArePrinted() : void
    {
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{'a3'});
        $clio
            ->ask('The question')
            ->withAnswers(Vector{'a1', 'a2', 'a3'})
            ->withAnswerStyle(CliColor::plain())
            ->withAnswerBrackets('< ', ' >')
            ->withSeperator(' OR ')
            ->getAnswer();
        $this->expectOut(PHP_EOL . 'The question' . PHP_EOL . ' < a1 OR a2 OR a3 >' . PHP_EOL . ' > ');
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
