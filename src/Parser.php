<?php

namespace Raideer\Tweech;

class Parser
{
    /**
    * Holds the regex for parsing the message.
    *
    * @var string/regex
    */
    protected $messageRegex;
    protected $messageRegexBasic;

    /**
    * Holds regex array for parsing parameters.
    *
    * @var array
    */
    protected $paramsRegex;

    public function __construct()
    {

        /*
         * Regex for parsing the message
         * http://tools.ietf.org/html/rfc1459#section-2.3.1
         */

        $space = ' ';
        $null = '\\x00';
        $crlf = "\r\n";
        $letters = 'A-Za-z';
        $numbers = '0-9';
        $special = preg_quote('[]_^{|}');
        $tagsSpecial = preg_quote('#:-_/,');

        $trailing = "[^$null$crlf]*";
        $username = "[$letters$numbers$special]+";
        $server = "[$letters$numbers$special\.]+";

        $tags = "(?:(?:[$letters$numbers\-]+)=(?:(?:[$letters$numbers$tagsSpecial]+)?;?)?)+\s";

        $command = "(?P<command>[A-Z]+|[$numbers]{3})";

        $params = "(?P<params>$trailing)";

        $prefix = "(?:(?P<servername>$server)|(?P<nick>$username)(?:!(?P<user>$username))(?:@(?P<host>$server)))";

        $compiled = "(?:@(?P<tags>$tags))?(?::(?P<prefix>$prefix)$space)?$command[^:]*(?::$params)$crlf";

        /*
         * Regex for parsing the irc message
         * @var regex string
         */
        $this->messageRegex = "`^$compiled$`U";

        /*
         * Command specific regex for parsing parameters
         * @var array
         */
        $this->paramsRegex = [
          'PRIVMSG' => "`^(?P<chat>#$username)[$space]?:(?P<message>$trailing)$`s",
          'MODE'    => "`^(?P<chat>#$username)[$space]?(?P<type>[+-]o)(?P<user>$trailing)$`s",
          '372'     => "`^(?P<username>$username)[$space]?:(?P<motd>$trailing)$`s",
          '001'     => "`^(?P<username>$username)[$space]?:(?P<welcome>$trailing)$`s",
          '002'     => "`^(?P<username>$username)[$space]?:(?P<host>$trailing)$`s",
          '033'     => "`^(?P<username>$username)[$space]?:(?P<created>$trailing)$`s",
          '353'     => "`^($username)[$space]?\=[$space]?(?P<chat>#$username)[$space]?:(?P<users>$trailing)$$`s",
        ];
    }

    protected function removeIntegerKeys(array $array)
    {
        foreach (array_keys($array) as $key) {
            if (is_int($key)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

  /**
   * Checks each message and runs the parameters through regex.
   *
   * @param  array $parsed
   *
   * @return array
   */
  protected function parseParameters($parsed)
  {
      $command = strtoupper($parsed['command']);

      if (!array_key_exists($command, $this->paramsRegex)) {
          return $parsed;
      }

      if (!preg_match($this->paramsRegex[$command], $parsed['params'], $params)) {
          return $parsed;
      }

      $parsed = array_merge($parsed, $params);

      if ($command == 353 && array_key_exists('users', $parsed)) {
          $parsed['users'] = explode(' ', $parsed['users']);
      }

      return $this->removeIntegerKeys($parsed);
  }

  /**
   * Main parsing function.
   *
   * @param  string $message Received irc message
   *
   * @return array           Parsed
   */
  public function parse($message)
  {
    //Checking if the message is a full line
    if (strpos($message, "\r\n") === false) {
        return;
    }

    //Parsing the message
    if (!preg_match($this->messageRegex, $message, $parsed)) {
        $parsed = ['invalid' => $message];

        return $parsed;
    }

    /*
    * Raw message
    */
    $parsed['raw'] = $parsed[0];

    $parsed = $this->parseParameters($parsed);

    return array_filter($this->removeIntegerKeys($parsed));
  }
}
