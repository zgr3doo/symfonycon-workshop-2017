<?php
declare(strict_types=1);

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use PHPUnit\Framework\Assert;
use SymfonyCon\Mastermind\Game\Code;
use SymfonyCon\Mastermind\Game\CodeMaker;
use SymfonyCon\Mastermind\Game\DecodingBoard;
use SymfonyCon\Mastermind\Game\DecodingBoards;
use SymfonyCon\Mastermind\Game\Feedback;
use SymfonyCon\Mastermind\Game\GameUuid;
use SymfonyCon\Mastermind\UseCase\MakeGuessUseCase;
use SymfonyCon\Mastermind\UseCase\StartGameUseCase;
use SymfonyCon\Mastermind\UseCase\ViewDecodingBoardUseCase;

class MastermindContext implements Context
{
    /**
     * @var int
     */
    private $numberOfAttempts = 12;

    /**
     * @var GameUuid
     */
    private $gameUuid;

    /**
     * @var DecodingBoards
     */
    private $decodingBoards;

    public function __construct()
    {
        $this->decodingBoards = new class implements DecodingBoards
        {
            private $boards = [];

            public function get(GameUuid $uuid): DecodingBoard
            {
                return $this->boards[(string) $uuid];
            }

            public function put(DecodingBoard $decodingBoard)
            {
                $this->boards[(string) $decodingBoard->gameUuid()] = $decodingBoard;
            }
        };
    }

    /**
     * @Given a decoding board of :numberOfAttempts attempts
     */
    public function aDecodingBoardOfGuesses($numberOfAttempts)
    {
        $this->numberOfAttempts = (int)$numberOfAttempts;
    }

    /**
     * @Given the code maker placed the :code pattern on the board
     */
    public function theCodeMakerPlacedThePatternOnTheBoard($code)
    {
        // we're not doing much with the code yet, but at some point we'll need to stub the CodeMaker to return it
        $codeLength = substr_count($code, ' ') + 1;

        $startGameUseCase = new StartGameUseCase(
            $this->decodingBoards,
            new class(Code::fromString($code)) implements CodeMaker
            {
                private $code;

                public function __construct(Code $code)
                {
                    $this->code = $code;
                }

                public function newCode(int $length): Code
                {
                    return $this->code;
                }
            },
            $this->numberOfAttempts
        );
        $this->gameUuid = $startGameUseCase->execute($codeLength);
    }

    /**
     * @When I try to break the code with :code
     */
    public function iTryToBreakTheCodeWith($code)
    {
        $makeGuessUseCase = new MakeGuessUseCase($this->decodingBoards);
        $makeGuessUseCase->execute($this->gameUuid, Code::fromString($code));
    }

    /**
     * @Then the code maker should give me :feedback feedback on my guess
     */
    public function theCodeMakerShouldGiveMeFeedbackOnMyGuess($feedback)
    {
        $viewDecodingBoardUseCase = new ViewDecodingBoardUseCase($this->decodingBoards);
        $board = $viewDecodingBoardUseCase->execute($this->gameUuid);

        Assert::assertInstanceOf(Feedback::class, $board->lastFeedback(), 'Feedback on the last guess attempt was given.');
        Assert::assertSame(substr_count($feedback, 'X'), $board->lastFeedback()->exactHits());
        Assert::assertSame(substr_count($feedback, 'O'), $board->lastFeedback()->colourHits());
    }

    /**
     * @When I try to break the code with an invalid pattern :number times
     */
    public function iTryToBreakTheCodeWithAnInvalidPatternTimes($number)
    {
        throw new PendingException();
    }

    /**
     * @When I break the code in the final guess
     */
    public function iBreakTheCodeInTheFinalGuess()
    {
        throw new PendingException();
    }

    /**
     * @Then I should win the game
     */
    public function iShouldWinTheGame()
    {
        throw new PendingException();
    }

    /**
     * @Then I should loose the game
     */
    public function iShouldLooseTheGame()
    {
        throw new PendingException();
    }
}
