<?hh // strict

namespace kilahm\Clio\Input;

use kilahm\Clio\Clio;
use kilahm\Clio\Enum\BackgroundCode;
use kilahm\Clio\Enum\EffectCode;
use kilahm\Clio\Enum\ForegroundCode;
use kilahm\Clio\Output\CliColor;
use kilahm\Clio\Output\CliCursor;
use kilahm\Clio\Output\CliFormat;
use kilahm\Clio\Output\ColorStyle;

class CliQuestion
{
    private Vector<string> $answers = Vector{};
    private bool $asked = false;
    private string $formattedQuestion = '';
    private string $prompt = ' > ';
    private CliCursor $cursor;
    private ColorStyle $answerStyle;
    private ColorStyle $sepStyle;
    private int $lineCount = 0;

    public function __construct(private string $question, private Clio $clio)
    {
        $this->cursor = new CliCursor();
        $this->answerStyle = shape(
            'fg' => ForegroundCode::cyan,
            'bg'=> BackgroundCode::reset,
            'effect' => EffectCode::bold,
        );
        $this->sepStyle = shape(
            'fg' => ForegroundCode::light_gray,
            'bg' => BackgroundCode::reset,
            'effect' => EffectCode::reset,
        );
    }


    public function withAnswers(Vector<string> $answers) : this
    {
        $this->answers = $answers;
        return $this;
    }

    public function withAnswerStyle(ColorStyle $style) : this
    {
        $this->answerStyle = $style;
        return $this;
    }

    public function withSeperatorStyle(ColorStyle $style) : this
    {
        $this->sepStyle = $style;
        return $this;
    }

    public function withPrompt(string $prompt) : this
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function getAnswer() : string
    {
        $this->formatQuestion();
        if($this->answers->isEmpty()) {
            return $this->ask();
        }

        $answer = null;
        while($answer === null || $this->answers->linearSearch($answer) === -1)
        {
            $answer = $this->ask();
            $this->asked = true;
        }
        $this->asked = false;
        return $answer;
    }

    private function formatQuestion() : void
    {
        $this->formattedQuestion = PHP_EOL
            . $this->question
            . $this->formatAnswers()
            . PHP_EOL . $this->prompt;
    }

    private function formatAnswers() : string
    {
        if($this->answers->isEmpty()) {
            return '';
        }
        $oneLineAnswers = implode(
            CliColor::make(' | ')->withStyle($this->sepStyle),
            $this->answers->map($ans ==>
                CliColor::make($ans)->withStyle($this->answerStyle)
            )
        );
        $answerFormatter = CliFormat::make($oneLineAnswers)->indent(1.0);
        $this->lineCount = $answerFormatter->lineCount();

        return PHP_EOL . CliColor::make('[ ')->withStyle($this->sepStyle)
            . $answerFormatter->getResult()
            . CliColor::make(' ]')->withStyle($this->sepStyle);
    }

    private function ask() : string
    {
        $this->clio->out($this->formattedQuestion);
        return $this->clio->getLine();
    }
}
