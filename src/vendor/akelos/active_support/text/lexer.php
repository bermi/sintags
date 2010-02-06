<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
* Author Markus Baker: http://www.lastcraft.com
* Version adapted from Simple Test: http://sourceforge.net/projects/simpletest/
* For an intro to the Lexer see:
* http://www.phppatterns.com/index.php/article/articleview/106/1/2/
* @author Marcus Baker
*/

/**#@+
* lexer mode constant
*/
define("AK_LEXER_ENTER", 1);
define("AK_LEXER_MATCHED", 2);
define("AK_LEXER_UNMATCHED", 3);
define("AK_LEXER_EXIT", 4);
define("AK_LEXER_SPECIAL", 5);
/**#@-*/

/**
 *    Compounded regular expression. Any of
 *    the contained patterns could match and
 *    when one does it's label is returned.
 */
class AkLexerParallelRegex {
    public $_patterns;
    public $_labels;
    public $_regex;
    public $_case;

    /**
     *    Constructor. Starts with no patterns.
     *    @param boolean $case    True for case sensitive, false
     *                            for insensitive.
     *    @access public
     */
    public function AkLexerParallelRegex($case) {
        $this->_case = $case;
        $this->_patterns = array();
        $this->_labels = array();
        $this->_regex = null;
    }

    /**
     *    Adds a pattern with an optional label.
     *    @param mixed $pattern       Perl style regex. Must be UTF-8
     *                                encoded. If its a string, the (, )
     *                                lose their meaning unless they
     *                                form part of a lookahead or
     *                                lookbehind assertation.
     *    @param string $label        Label of regex to be returned
     *                                on a match. Label must be ASCII
     *    @access public
     */
    public function addPattern($pattern, $label = true) {
        $count = count($this->_patterns);
        $this->_patterns[$count] = $pattern;
        $this->_labels[$count] = $label;
        $this->_regex = null;
    }

    /**
     *    Attempts to match all patterns at once against
     *    a string.
     *    @param string $subject      String to match against.
     *    @param string $match        First matched portion of
     *                                subject.
     *    @return boolean             True on success.
     *    @access public
     */
    public function match($subject, &$match) {
        if (count($this->_patterns) == 0) {
            return false;
        }
        if (! preg_match($this->_getCompoundedRegex(), $subject, $matches)) {
            $match = '';
            return false;
        }

        $match = $matches[0];
        $size = count($matches);
        for ($i = 1; $i < $size; $i++) {
            if ($matches[$i] && isset($this->_labels[$i - 1])) {
                return $this->_labels[$i - 1];
            }
        }
        return true;
    }

    /**
     *    Attempts to split the string against all patterns at once
     *
     *    @param string $subject      String to match against.
     *    @param array $split         The split result: array containing, pre-match, match & post-match strings
     *    @return boolean             True on success.
     *    @access public
     *
     *    @author Christopher Smith <chris@jalakai.co.uk>
     */
    public function split($subject, &$split) {
        if (count($this->_patterns) == 0) {
            return false;
        }

        if (! preg_match($this->_getCompoundedRegex(), $subject, $matches)) {
            $split = array($subject, "", "");
            return false;
        }

        $idx = count($matches)-2;

        list($pre, $post) = preg_split($this->_patterns[$idx].$this->_getPerlMatchingFlags(), $subject, 2);

        $split = array($pre, $matches[0], $post);
        return isset($this->_labels[$idx]) ? $this->_labels[$idx] : true;
    }

    /**
     *    Compounds the patterns into a single
     *    regular expression separated with the
     *    "or" operator. Caches the regex.
     *    Will automatically escape (, ) and / tokens.
     *    @param array $patterns    List of patterns in order.
     *    @access private
     */
    public function _getCompoundedRegex() {
        if ($this->_regex == null) {
            $cnt = count($this->_patterns);
            for ($i = 0; $i < $cnt; $i++) {

                // Replace lookaheads / lookbehinds with marker
                $m = "\1\1";
                $pattern = preg_replace(
                array (
                '/\(\?(i|m|s|x|U)\)/U',
                '/\(\?(\-[i|m|s|x|U])\)/U',
                '/\(\?\=(.*)\)/sU',
                '/\(\?\!(.*)\)/sU',
                '/\(\?\<\=(.*)\)/sU',
                '/\(\?\<\!(.*)\)/sU',
                '/\(\?\:(.*)\)/sU',
                ),
                array (
                $m.'SO:\\1'.$m,
                $m.'SOR:\\1'.$m,
                $m.'LA:IS:\\1'.$m,
                $m.'LA:NOT:\\1'.$m,
                $m.'LB:IS:\\1'.$m,
                $m.'LB:NOT:\\1'.$m,
                $m.'GRP:\\1'.$m,
                ),
                $this->_patterns[$i]
                );
                // Quote the rest
                $pattern = str_replace(
                array('/', '(', ')'),
                array('\/', '\(', '\)'),
                $pattern
                );

                // Restore lookaheads / lookbehinds
                $pattern = preg_replace(
                array (
                '/'.$m.'SO:(.{1})'.$m.'/',
                '/'.$m.'SOR:(.{2})'.$m.'/',
                '/'.$m.'LA:IS:(.*)'.$m.'/sU',
                '/'.$m.'LA:NOT:(.*)'.$m.'/sU',
                '/'.$m.'LB:IS:(.*)'.$m.'/sU',
                '/'.$m.'LB:NOT:(.*)'.$m.'/sU',
                '/'.$m.'GRP:(.*)'.$m.'/sU',
                ),
                array (
                '(?\\1)',
                '(?\\1)',
                '(?=\\1)',
                '(?!\\1)',
                '(?<=\\1)',
                '(?<!\\1)',
                '(?:\\1)',
                ),
                $pattern
                );

                $this->_patterns[$i] = '('.$pattern.')';
            }
            $this->_regex = "/" . implode("|", $this->_patterns) . "/" . $this->_getPerlMatchingFlags();
        }
        return $this->_regex;
    }

    /**
     *    Accessor for perl regex mode flags to use.
     *    @return string       Perl regex flags.
     *    @access private
     */
    public function _getPerlMatchingFlags() {
        return ($this->_case ? "msS" : "msSi");
    }
}

/**
 *    States for a stack machine.
 */
class AkLexerStateStack {
    public $_stack;

    /**
     *    Constructor. Starts in named state.
     *    @param string $start        Starting state name.
     *    @access public
     */
    public function AkLexerStateStack($start) {
        $this->_stack = array($start);
    }

    /**
     *    Accessor for current state.
     *    @return string       State.
     *    @access public
     */
    public function getCurrent() {
        return $this->_stack[count($this->_stack) - 1];
    }

    /**
     *    Adds a state to the stack and sets it
     *    to be the current state.
     *    @param string $state        New state.
     *    @access public
     */
    public function enter($state) {
        array_push($this->_stack, $state);
    }

    /**
     *    Leaves the current state and reverts
     *    to the previous one.
     *    @return boolean    False if we drop off
     *                       the bottom of the list.
     *    @access public
     */
    public function leave() {
        if (count($this->_stack) == 1) {
            return false;
        }
        array_pop($this->_stack);
        return true;
    }
}

/**
 *    Accepts text and breaks it into tokens.
 *    Some optimisation to make the sure the
 *    content is only scanned by the PHP regex
 *    parser once. Lexer modes must not start
 *    with leading underscores.
 */
class AkLexer
{
    public $_regexes;
    public $_parser;
    public $_mode;
    public $_mode_handlers;
    public $_case;

    /**
     *    Sets up the lexer in case insensitive matching
     *    by default.
     *    @param AkParser $parser  Handling strategy by
     *                                    reference.
     *    @param string $start            Starting handler.
     *    @param boolean $case            True for case sensitive.
     *    @access public
     */
    public function __construct(&$parser, $start = 'accept', $case = false) {
        $this->_regexes = array();
        $this->init($parser, $start, $case);
        $this->_mode = new AkLexerStateStack($start);
        $this->_mode_handlers = array();
    }
    
    public function init(&$parser, $start = 'accept', $case = false){
        $this->_case = $case;
        $this->_parser = &$parser;
    }

    /**
     *    Adds a token search pattern for a particular
     *    parsing mode. The pattern does not change the
     *    current mode.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @access public
     */
    public function addPattern($pattern, $mode = 'accept') {
        if (! isset($this->_regexes[$mode])) {
            $this->_regexes[$mode] = new AkLexerParallelRegex($this->_case);
        }
        $this->_regexes[$mode]->addPattern($pattern);
    }

    /**
     *    Adds a pattern that will enter a new parsing
     *    mode. Useful for entering parenthesis, strings,
     *    tags, etc.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @param string $new_mode     Change parsing to this new
     *                                nested mode.
     *    @access public
     */
    public function addEntryPattern($pattern, $mode, $new_mode) {
        if (! isset($this->_regexes[$mode])) {
            $this->_regexes[$mode] = new AkLexerParallelRegex($this->_case);
        }
        $this->_regexes[$mode]->addPattern($pattern, $new_mode);
    }

    /**
     *    Adds a pattern that will exit the current mode
     *    and re-enter the previous one.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Mode to leave.
     *    @access public
     */
    public function addExitPattern($pattern, $mode) {
        if (! isset($this->_regexes[$mode])) {
            $this->_regexes[$mode] = new AkLexerParallelRegex($this->_case);
        }
        $this->_regexes[$mode]->addPattern($pattern, '__exit');
    }

    /**
     *    Adds a pattern that has a special mode. Acts as an entry
     *    and exit pattern in one go, effectively calling a special
     *    parser handler for this token only.
     *    @param string $pattern      Perl style regex, but ( and )
     *                                lose the usual meaning.
     *    @param string $mode         Should only apply this
     *                                pattern when dealing with
     *                                this type of input.
     *    @param string $special      Use this mode for this one token.
     *    @access public
     */
    public function addSpecialPattern($pattern, $mode, $special) {
        if (! isset($this->_regexes[$mode])) {
            $this->_regexes[$mode] = new AkLexerParallelRegex($this->_case);
        }
        $this->_regexes[$mode]->addPattern($pattern, "_$special");
    }

    /**
     *    Adds a mapping from a mode to another handler.
     *    @param string $mode        Mode to be remapped.
     *    @param string $handler     New target handler.
     *    @access public
     */
    public function mapHandler($mode, $handler) {
        $this->_mode_handlers[$mode] = $handler;
    }

    /**
     *    Splits the page text into tokens. Will fail
     *    if the handlers report an error or if no
     *    content is consumed. If successful then each
     *    unparsed and parsed token invokes a call to the
     *    held listener.
     *    @param string $raw        Raw HTML text.
     *    @return boolean           True on success, else false.
     *    @access public
     */
    public function parse($raw) {
        if (! isset($this->_parser)) {
            return false;
        }

        $initialLength = strlen($raw);
        $length = $initialLength;
        $pos = 0;
        while (is_array($parsed = $this->_reduce($raw))) {
            list($unmatched, $matched, $mode) = $parsed;
            $currentLength = strlen($raw);
            $matchPos = $initialLength - $currentLength - strlen($matched);
            if (! $this->_dispatchTokens($unmatched, $matched, $mode, $pos, $matchPos)) {
                return false;
            }
            if ($currentLength == $length) {
                return false;
            }
            $length = $currentLength;
            $pos = $initialLength - $currentLength;
        }
        if (!$parsed) {
            return false;
        }
        return $this->_invokeParser($raw, AK_LEXER_UNMATCHED, $pos);
    }

    /**
     *    Sends the matched token and any leading unmatched
     *    text to the parser changing the lexer to a new
     *    mode if one is listed.
     *    @param string $unmatched    Unmatched leading portion.
     *    @param string $matched      Actual token match.
     *    @param string $mode         Mode after match. A boolean
     *                                false mode causes no change.
     *    @param int $pos         Current byte index location in raw doc
     *                                thats being parsed
     *    @return boolean             False if there was any error
     *                                from the parser.
     *    @access private
     */
    public function _dispatchTokens($unmatched, $matched, $mode = false, $initialPos, $matchPos) {
        if (! $this->_invokeParser($unmatched, AK_LEXER_UNMATCHED, $initialPos) ){
            return false;
        }
        if ($this->_isModeEnd($mode)) {
            if (! $this->_invokeParser($matched, AK_LEXER_EXIT, $matchPos)) {
                return false;
            }
            return $this->_mode->leave();
        }
        if ($this->_isSpecialMode($mode)) {
            $this->_mode->enter($this->_decodeSpecial($mode));
            if (! $this->_invokeParser($matched, AK_LEXER_SPECIAL, $matchPos)) {
                return false;
            }
            return $this->_mode->leave();
        }
        if (is_string($mode)) {
            $this->_mode->enter($mode);
            return $this->_invokeParser($matched, AK_LEXER_ENTER, $matchPos);
        }
        return $this->_invokeParser($matched, AK_LEXER_MATCHED, $matchPos);
    }

    /**
     *    Tests to see if the new mode is actually to leave
     *    the current mode and pop an item from the matching
     *    mode stack.
     *    @param string $mode    Mode to test.
     *    @return boolean        True if this is the exit mode.
     *    @access private
     */
    public function _isModeEnd($mode) {
        return ($mode === '__exit');
    }

    /**
     *    Test to see if the mode is one where this mode
     *    is entered for this token only and automatically
     *    leaves immediately afterwoods.
     *    @param string $mode    Mode to test.
     *    @return boolean        True if this is the exit mode.
     *    @access private
     */
    public function _isSpecialMode($mode) {
        return (strncmp($mode, '_', 1) == 0);
    }

    /**
     *    Strips the magic underscore marking single token
     *    modes.
     *    @param string $mode    Mode to decode.
     *    @return string         Underlying mode name.
     *    @access private
     */
    public function _decodeSpecial($mode) {
        return substr($mode, 1);
    }

    /**
     *    Calls the parser method named after the current
     *    mode. Empty content will be ignored. The lexer
     *    has a parser handler for each mode in the lexer.
     *    @param string $content        Text parsed.
     *    @param boolean $is_match      Token is recognised rather
     *                                  than unparsed data.
     *    @param int $pos         Current byte index location in raw doc
     *                                thats being parsed
     *    @access private
     */
    public function _invokeParser($content, $is_match, $pos) {
        if (($content === '') || ($content === false)) {
            return true;
        }
        $handler = $this->_mode->getCurrent();
        if (isset($this->_mode_handlers[$handler])) {
            $handler = $this->_mode_handlers[$handler];
        }
        return $this->_parser->$handler($content, $is_match, $pos);
    }

    /**
     *    Tries to match a chunk of text and if successful
     *    removes the recognised chunk and any leading
     *    unparsed data. Empty strings will not be matched.
     *    @param string $raw         The subject to parse. This is the
     *                               content that will be eaten.
     *    @return array              Three item list of unparsed
     *                               content followed by the
     *                               recognised token and finally the
     *                               action the parser is to take.
     *                               True if no match, false if there
     *                               is a parsing error.
     *    @access private
     */
    public function _reduce(&$raw) {
        if (! isset($this->_regexes[$this->_mode->getCurrent()])) {
            return false;
        }
        if ($raw === "") {
            return true;
        }
        if ($action = $this->_regexes[$this->_mode->getCurrent()]->split($raw, $split)) {
            list($unparsed, $match, $raw) = $split;
            return array($unparsed, $match, $action);
        }
        return true;
    }
}


