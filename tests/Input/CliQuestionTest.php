<?hh // strict

namespace kilahm\Clio\Test\Input;

use HackPack\HackUnit\Contract\Assert;
use kilahm\Clio\Clio;
use kilahm\Clio\Input\CliQuestion;
use kilahm\Clio\Output\CliColor;

class CliQuestionTest
{

    private resource $in;

    private resource $out;

    public function __construct()
    {
        $this->in = fopen('php://memory', 'w+');
        $this->out = fopen('php://memory', 'w+');
    }

    <<Test>>
    public function testQuestionIsPrinted(Assert $assert) : void
    {
        $data = 'The Question';
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{'Answer'});

        $clio->ask($data)->getAnswer();

        $expected_data = sprintf(
            '%s%s%s > ',
            PHP_EOL,
            $data,
            PHP_EOL
        );

        $assert->string($expected_data)->is($this->getOutputFromBuffer());
    }

    <<Test>>
    public function testAnswersArePrinted(Assert $assert) : void
    {
        $question = 'The question';
        $possible_answers = Vector{'a1', 'a2', 'a3'};
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{'a3'});
        $clio
            ->ask($question)
            ->withAnswers($possible_answers)
            ->withAnswerStyle(CliColor::plain())
            ->getAnswer();

        $expected_data = sprintf(
            '%s%s%s [ %s ]%s > ',
            PHP_EOL,
            $question,
            PHP_EOL,
            implode(' | ', $possible_answers),
            PHP_EOL
        );

        $assert->string($expected_data)->is($this->getOutputFromBuffer());
    }

    <<Test>>
    public function testCustomSeperatorAndBracketsArePrinted(Assert $assert) : void
    {
        $question = 'The question';
        $possible_answers = Vector{'a1', 'a2', 'a3'};
        $answer_brackets_open = '< ';
        $answer_brackets_close = ' >';
        $answer_separator = ' OR ';
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{'a3'});
        $clio
            ->ask('The question')
            ->withAnswers($possible_answers)
            ->withAnswerStyle(CliColor::plain())
            ->withAnswerBrackets($answer_brackets_open, $answer_brackets_close)
            ->withSeperator($answer_separator)
            ->getAnswer();

        $expected_data = sprintf(
            '%s%s%s %s%s%s%s > ',
            PHP_EOL,
            $question,
            PHP_EOL,
            $answer_brackets_open,
            implode($answer_separator, $possible_answers),
            $answer_brackets_close,
            PHP_EOL
        );

        $assert->string($expected_data)->is($this->getOutputFromBuffer());
    }

    <<Test>>
    public function testQuestionGetsFirstAnswer(Assert $assert) : void
    {
        $answer = 'Answer';
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{$answer, 'Other Answer'});

        $assert->string($answer)
            ->is(
                $clio->ask('The Question')->getAnswer()
            );
    }

    <<Test>>
    public function testQuestionKeepsAskingUntilValidAnswerIsGiven(Assert $assert) : void
    {
        $answer = 'Right answer';
        $clio = $this->makeClio();
        $this->prepAnswers(Vector{
            'Wrong Answer',
            'Other wrong answer',
            $answer
        });

        $assert->string($answer)
            ->is(
                $clio
                    ->ask('Q')
                    ->withAnswers(Vector{$answer, 'Answer not given'})
                    ->getAnswer()
            );
    }

    <<TearDown>>
    public function tearDown() : void
    {
        rewind($this->in);
        ftruncate($this->in, 0);
        rewind($this->out);
        ftruncate($this->out, 0);
    }

    private function makeClio(Vector<string> $argv = Vector{}) : Clio
    {
        $clio = new Clio('unit', $argv, $this->in, $this->out);
        $clio->throwOnParseError();
        return $clio;
    }

    private function prepAnswers(Vector<string> $answers) : void
    {
        fwrite($this->in, implode(PHP_EOL, $answers));
        rewind($this->in);
    }

    private function getOutputFromBuffer() : string
    {
        return stream_get_contents($this->out, -1, 0);
    }
}
