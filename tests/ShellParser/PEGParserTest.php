<?php

namespace Wikimedia\Shellbox\Tests\ShellParser;

use Shellbox\ShellParser\PEGParser;
use Shellbox\ShellParser\UnimplementedError;
use Shellbox\Tests\ShellboxTestCase;
use Wikimedia\WikiPEG\SyntaxError;

// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * @coversNothing
 */
class PEGParserTest extends ShellboxTestCase {
	public static function provideParse() {
		return [
			[ '', '<program></program>' ],
			[ 'a', '<program><complete_command><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></complete_command></program>' ],
			[ 'a b c', '<program><complete_command><simple_command><word><unquoted_literal>a</unquoted_literal></word><word><unquoted_literal>b</unquoted_literal></word><word><unquoted_literal>c</unquoted_literal></word></simple_command></complete_command></program>' ],
			[ 'a # |b', '<program><complete_command><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></complete_command></program>' ],
			[ '"a"', '<program><complete_command><simple_command><word><double_quote>a</double_quote></word></simple_command></complete_command></program>' ],
			[ "'a'", '<program><complete_command><simple_command><word><single_quote>a</single_quote></word></simple_command></complete_command></program>' ],
			[ "'a'\''a'", '<program><complete_command><simple_command><word><single_quote>a</single_quote><bare_escape>\'</bare_escape><single_quote>a</single_quote></word></simple_command></complete_command></program>' ],
			[ '"a\"\b"', '<program><complete_command><simple_command><word><double_quote>a<dquoted_escape>&quot;</dquoted_escape>\b</double_quote></word></simple_command></complete_command></program>' ],
			[ '\a', '<program><complete_command><simple_command><word><bare_escape>a</bare_escape></word></simple_command></complete_command></program>' ],
			[ '`cmd`', '<program><complete_command><simple_command><word><backquote>cmd</backquote></word></simple_command></complete_command></program>' ],
			[ '`a \`b\` c`', '<program><complete_command><simple_command><word><backquote>a <double_backquote>b</double_backquote> c</backquote></word></simple_command></complete_command></program>' ],
			// FIXME [ '`\'`', 'SyntaxError' ],
			[ '$a', '<program><complete_command><simple_command><word><named_parameter>a</named_parameter></word></simple_command></complete_command></program>' ],
			[ '$0', '<program><complete_command><simple_command><word><special_parameter>0</special_parameter></word></simple_command></complete_command></program>' ],
			[ '$1', '<program><complete_command><simple_command><word><positional_parameter>1</positional_parameter></word></simple_command></complete_command></program>' ],
			[ '$@', '<program><complete_command><simple_command><word><special_parameter>@</special_parameter></word></simple_command></complete_command></program>' ],
			[ '$aa', '<program><complete_command><simple_command><word><named_parameter>aa</named_parameter></word></simple_command></complete_command></program>' ],
			[ '${11}', '<program><complete_command><simple_command><word><braced_parameter_expansion><positional_parameter>11</positional_parameter></braced_parameter_expansion></word></simple_command></complete_command></program>' ],
			[ '${aa}', '<program><complete_command><simple_command><word><braced_parameter_expansion><named_parameter>aa</named_parameter></braced_parameter_expansion></word></simple_command></complete_command></program>' ],

			[ '${a:-}', '<program><complete_command><simple_command><word><use_default><named_parameter>a</named_parameter></use_default></word></simple_command></complete_command></program>' ],
			[ '${a:-w}', '<program><complete_command><simple_command><word><use_default><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></use_default></word></simple_command></complete_command></program>' ],
			[ '${a:=}', '<program><complete_command><simple_command><word><assign_default><named_parameter>a</named_parameter></assign_default></word></simple_command></complete_command></program>' ],
			[ '${a:=w}', '<program><complete_command><simple_command><word><assign_default><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></assign_default></word></simple_command></complete_command></program>' ],
			[ '${a := w}', 'SyntaxError' ],
			[ '${a:= w}', '<program><complete_command><simple_command><word><assign_default><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></assign_default></word></simple_command></complete_command></program>' ],
			[ '${a:=$w}', '<program><complete_command><simple_command><word><assign_default><named_parameter>a</named_parameter><word><named_parameter>w</named_parameter></word></assign_default></word></simple_command></complete_command></program>' ],
			[ '${a:?}', '<program><complete_command><simple_command><word><indicate_error><named_parameter>a</named_parameter></indicate_error></word></simple_command></complete_command></program>' ],
			[ '${a:?w}', '<program><complete_command><simple_command><word><indicate_error><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></indicate_error></word></simple_command></complete_command></program>' ],
			[ '${a:+}', '<program><complete_command><simple_command><word><use_alternative><named_parameter>a</named_parameter></use_alternative></word></simple_command></complete_command></program>' ],
			[ '${a:+w}', '<program><complete_command><simple_command><word><use_alternative><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></use_alternative></word></simple_command></complete_command></program>' ],

			[ '${a-}', '<program><complete_command><simple_command><word><use_default_unset><named_parameter>a</named_parameter></use_default_unset></word></simple_command></complete_command></program>' ],
			[ '${a-w}', '<program><complete_command><simple_command><word><use_default_unset><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></use_default_unset></word></simple_command></complete_command></program>' ],
			[ '${a=}', '<program><complete_command><simple_command><word><assign_default_unset><named_parameter>a</named_parameter></assign_default_unset></word></simple_command></complete_command></program>' ],
			[ '${a=w}', '<program><complete_command><simple_command><word><assign_default_unset><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></assign_default_unset></word></simple_command></complete_command></program>' ],
			[ '${a = w}', 'SyntaxError' ],
			[ '${a= w}', '<program><complete_command><simple_command><word><assign_default_unset><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></assign_default_unset></word></simple_command></complete_command></program>' ],
			[ '${a=$w}', '<program><complete_command><simple_command><word><assign_default_unset><named_parameter>a</named_parameter><word><named_parameter>w</named_parameter></word></assign_default_unset></word></simple_command></complete_command></program>' ],
			[ '${a?}', '<program><complete_command><simple_command><word><indicate_error_unset><named_parameter>a</named_parameter></indicate_error_unset></word></simple_command></complete_command></program>' ],
			[ '${a?w}', '<program><complete_command><simple_command><word><indicate_error_unset><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></indicate_error_unset></word></simple_command></complete_command></program>' ],
			[ '${a+}', '<program><complete_command><simple_command><word><use_alternative_unset><named_parameter>a</named_parameter></use_alternative_unset></word></simple_command></complete_command></program>' ],
			[ '${a+w}', '<program><complete_command><simple_command><word><use_alternative_unset><named_parameter>a</named_parameter><word><unquoted_literal>w</unquoted_literal></word></use_alternative_unset></word></simple_command></complete_command></program>' ],

			[ '${#a}', '<program><complete_command><simple_command><word><string_length><named_parameter>a</named_parameter></string_length></word></simple_command></complete_command></program>' ],
			[ '${11=}', '<program><complete_command><simple_command><word><assign_default_unset><positional_parameter>11</positional_parameter></assign_default_unset></word></simple_command></complete_command></program>' ],

			[ '$((1+2))', '<program><complete_command><simple_command><word><arithmetic_expansion><word><unquoted_literal>1+2</unquoted_literal></word></arithmetic_expansion></word></simple_command></complete_command></program>' ],
			[ '$(cmd)', '<program><complete_command><simple_command><word><command_expansion><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word></simple_command></complete_command></command_expansion></word></simple_command></complete_command></program>' ],
			[ '$( $(cmd) )', '<program><complete_command><simple_command><word><command_expansion><complete_command><simple_command><word><command_expansion><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word></simple_command></complete_command></command_expansion></word></simple_command></complete_command></command_expansion></word></simple_command></complete_command></program>' ],

			[ '"$a"', '<program><complete_command><simple_command><word><double_quote><named_parameter>a</named_parameter></double_quote></word></simple_command></complete_command></program>' ],
			[ '"`cmd`"', '<program><complete_command><simple_command><word><double_quote><backquote>cmd</backquote></double_quote></word></simple_command></complete_command></program>' ],
			[ '"$(cmd)"', '<program><complete_command><simple_command><word><double_quote><command_expansion><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word></simple_command></complete_command></command_expansion></double_quote></word></simple_command></complete_command></program>' ],
			[ '"$(")', 'SyntaxError' ],
			[ '"$(")"', 'SyntaxError' ],

			[ 'cmd>out', '<program><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word><io_redirect><output><word><unquoted_literal>out</unquoted_literal></word></output></io_redirect></simple_command></complete_command></program>' ],
			[ 'cmd >out <in', '<program><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word><io_redirect><output><word><unquoted_literal>out</unquoted_literal></word></output></io_redirect><io_redirect><input><word><unquoted_literal>in</unquoted_literal></word></input></io_redirect></simple_command></complete_command></program>' ],
			[ 'cmd 2>&1', '<program><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word><io_redirect><io_subject>2</io_subject><duplicate_output><word><unquoted_literal>1</unquoted_literal></word></duplicate_output></io_redirect></simple_command></complete_command></program>' ],
			[ 'cmd 2>out', '<program><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word><io_redirect><io_subject>2</io_subject><output><word><unquoted_literal>out</unquoted_literal></word></output></io_redirect></simple_command></complete_command></program>' ],
			[ '>out cmd', '<program><complete_command><simple_command><cmd_prefix><io_redirect><output><word><unquoted_literal>out</unquoted_literal></word></output></io_redirect></cmd_prefix><word><unquoted_literal>cmd</unquoted_literal></word></simple_command></complete_command></program>' ],
			[ 'cmd>>out', '<program><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word><io_redirect><append_output><word><unquoted_literal>out</unquoted_literal></word></append_output></io_redirect></simple_command></complete_command></program>' ],
			[ 'cmd <& f', '<program><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word><io_redirect><duplicate_input><word><unquoted_literal>f</unquoted_literal></word></duplicate_input></io_redirect></simple_command></complete_command></program>' ],
			[ 'cmd >& f', '<program><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word><io_redirect><duplicate_output><word><unquoted_literal>f</unquoted_literal></word></duplicate_output></io_redirect></simple_command></complete_command></program>' ],
			[ 'cmd >| f', '<program><complete_command><simple_command><word><unquoted_literal>cmd</unquoted_literal></word><io_redirect><clobber><word><unquoted_literal>f</unquoted_literal></word></clobber></io_redirect></simple_command></complete_command></program>' ],

			[ '(a)', '<program><complete_command><subshell><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></subshell></complete_command></program>' ],
			[ 'a; (b;c)', '<program><complete_command><list><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command><subshell><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></subshell></list></complete_command></program>' ],
			[ 'a=b c', '<program><complete_command><simple_command><cmd_prefix><assignment><name>a</name><word><unquoted_literal>b</unquoted_literal></word></assignment></cmd_prefix><word><unquoted_literal>c</unquoted_literal></word></simple_command></complete_command></program>' ],
			[ 'a&&b', '<program><complete_command><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command><and_if><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></and_if></complete_command></program>' ],
			[ 'a && b || c', '<program><complete_command><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command><and_if><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></and_if><or_if><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></or_if></complete_command></program>' ],
			[ '!a', '<program><complete_command><simple_command><word><unquoted_literal>!a</unquoted_literal></word></simple_command></complete_command></program>' ],
			[ '! a', '<program><complete_command><bang><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></bang></complete_command></program>' ],
			[ 'a|b', '<program><complete_command><pipeline><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></pipeline></complete_command></program>' ],
			[ 'a && b | c', '<program><complete_command><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command><and_if><pipeline><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></pipeline></and_if></complete_command></program>' ],
			[ 'a | b && c', '<program><complete_command><pipeline><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></pipeline><and_if><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></and_if></complete_command></program>' ],

			[ 'a&', '<program><complete_command><background><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></background></complete_command></program>' ],
			[ 'a&b', '<program><complete_command><list><background><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></background><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></list></complete_command></program>' ],
			[ 'a&b&&c', '<program><complete_command><list><background><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></background><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command><and_if><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></and_if></list></complete_command></program>' ],
			[ 'a&b&', '<program><complete_command><list><background><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></background><background><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></background></list></complete_command></program>' ],
			[ 'a&b;', '<program><complete_command><list><background><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></background><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></list></complete_command></program>' ],

			[ '{ a; }', '<program><complete_command><brace_group><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></brace_group></complete_command></program>' ],
			[ "{ a\n}", '<program><complete_command><brace_group><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></brace_group></complete_command></program>' ],
			[ '{ a }', 'SyntaxError' ],
			[ '{ a; } >out', '<program><complete_command><brace_group><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></brace_group><io_redirect><output><word><unquoted_literal>out</unquoted_literal></word></output></io_redirect></complete_command></program>' ],

			[ 'for p in a; do b; done', '<program><complete_command><for>p<in><word><unquoted_literal>a</unquoted_literal></word></in><do><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></do></for></complete_command></program>' ],
			[ 'for p in ; do b; done', '<program><complete_command><for>p<in></in><do><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></do></for></complete_command></program>' ],
			[ 'for p; do b; done', '<program><complete_command><for>p<do><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></do></for></complete_command></program>' ],
			[ 'for a do b done', 'SyntaxError' ],
			[ 'esac', 'SyntaxError' ],
			[ 'for p in a; do b & done', '<program><complete_command><for>p<in><word><unquoted_literal>a</unquoted_literal></word></in><do><background><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></background></do></for></complete_command></program>' ],
			[ 'for p in a; do b & c & done', '<program><complete_command><for>p<in><word><unquoted_literal>a</unquoted_literal></word></in><do><background><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></background><background><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></background></do></for></complete_command></program>' ],
			[ '
				case w in
					p1)
						x
						;;
					p2|p3)
						x
						;;
					(p4)
						x
						;;
				esac',
				'<program><complete_command><case><word><unquoted_literal>w</unquoted_literal></word><in><case_item><case_pattern><word><unquoted_literal>p1</unquoted_literal></word></case_pattern><case_consequent><simple_command><word><unquoted_literal>x</unquoted_literal></word></simple_command></case_consequent></case_item><case_item><case_pattern><word><unquoted_literal>p2</unquoted_literal></word><word><unquoted_literal>p3</unquoted_literal></word></case_pattern><case_consequent><simple_command><word><unquoted_literal>x</unquoted_literal></word></simple_command></case_consequent></case_item><case_item><case_pattern><word><unquoted_literal>p4</unquoted_literal></word></case_pattern><case_consequent><simple_command><word><unquoted_literal>x</unquoted_literal></word></simple_command></case_consequent></case_item></in></case></complete_command></program>'
			],
			[ '
				case w in
					p1)
						x
						;;
					p2|p3)
						x
						;;
					(p4)
						x
				esac',
				'<program><complete_command><case><word><unquoted_literal>w</unquoted_literal></word><in><case_item><case_pattern><word><unquoted_literal>p1</unquoted_literal></word></case_pattern><case_consequent><simple_command><word><unquoted_literal>x</unquoted_literal></word></simple_command></case_consequent></case_item><case_item><case_pattern><word><unquoted_literal>p2</unquoted_literal></word><word><unquoted_literal>p3</unquoted_literal></word></case_pattern><case_consequent><simple_command><word><unquoted_literal>x</unquoted_literal></word></simple_command></case_consequent></case_item><case_item><case_pattern><word><unquoted_literal>p4</unquoted_literal></word></case_pattern><case_consequent><simple_command><word><unquoted_literal>x</unquoted_literal></word></simple_command></case_consequent></case_item></in></case></complete_command></program>'
			],
			[ "case w in\nesac", '<program><complete_command><case><word><unquoted_literal>w</unquoted_literal></word><in></in></case></complete_command></program>' ],

			[ 'if a; then b; fi', '<program><complete_command><if><condition><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></condition><consequent><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></consequent></if></complete_command></program>' ],
			[ 'if a; then b; else c; fi', '<program><complete_command><if><condition><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></condition><consequent><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></consequent><else><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></else></if></complete_command></program>' ],
			[ 'if a; then b; elif c; then d; else e; fi', '<program><complete_command><if><condition><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></condition><consequent><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></consequent><elif_condition><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></elif_condition><elif_consequent><simple_command><word><unquoted_literal>d</unquoted_literal></word></simple_command></elif_consequent><else><simple_command><word><unquoted_literal>e</unquoted_literal></word></simple_command></else></if></complete_command></program>' ],
			[ 'if a then b fi', 'SyntaxError' ],
			[ 'while a; do b; done', '<program><complete_command><while><condition><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></condition><do><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command></do></while></complete_command></program>' ],
			[ 'until a; b; c; do d; e; done', '<program><complete_command><until><condition><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command><simple_command><word><unquoted_literal>b</unquoted_literal></word></simple_command><simple_command><word><unquoted_literal>c</unquoted_literal></word></simple_command></condition><do><simple_command><word><unquoted_literal>d</unquoted_literal></word></simple_command><simple_command><word><unquoted_literal>e</unquoted_literal></word></simple_command></do></until></complete_command></program>' ],
			[ 'f() { a; }', '<program><complete_command><function_definition><function_name>f</function_name><brace_group><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></brace_group></function_definition></complete_command></program>' ],
			[ 'f() { a; } >out', '<program><complete_command><function_definition><function_name>f</function_name><brace_group><simple_command><word><unquoted_literal>a</unquoted_literal></word></simple_command></brace_group><io_redirect><output><word><unquoted_literal>out</unquoted_literal></word></output></io_redirect></function_definition></complete_command></program>' ],

			// TODO: heredoc
			[ "a<<END\nfoo\nEND\n", 'UnimplementedError' ],
			[ 'a<<b', 'UnimplementedError' ],
			[ 'a<<-b', 'UnimplementedError' ],
			[ 'a 2<<-b', 'UnimplementedError' ],
		];
	}

	/**
	 * @dataProvider provideParse
	 */
	public function testParse( $input, $expected ) {
		$parser = new PEGParser;
		try {
			$result = $parser->parse( $input );
			$dump = $result->dump();
		} catch ( SyntaxError $e ) {
			$dump = "SyntaxError at location {$e->location}: {$e->getMessage()}";
		} catch ( UnimplementedError $e ) {
			$dump = 'UnimplementedError: ' . $e->getMessage();
		}
		if ( $expected === 'SyntaxError' || $expected === 'UnimplementedError' ) {
			$this->assertStringStartsWith( $expected, $dump );
		} else {
			$this->assertSame( $expected, $dump );
		}
	}
}
