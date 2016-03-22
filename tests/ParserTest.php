<?php

class ParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;

    public function setUp()
    {
        $this->parser = new Raideer\Tweech\Parser;
    }

    /**
     * @dataProvider validIrcMessages
     */
    public function testParsing($message)
    {
        $this->parser->parse($message);
    }

    public function validIrcMessages()
    {
        return [
            [":twitchusername!twitchusername@twitchusernamtwitch.tv JOIN #channel\r\n"],
            [":twitch_username.tmi.twitch.tv 366 twitch_username #channel :End of /NAMES list\r\n"],
        ];
    }

}
