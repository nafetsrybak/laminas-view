<?php

declare(strict_types=1);

namespace LaminasTest\View\Helper;

use DOMDocument;
use Generator;
use Laminas\View;
use Laminas\View\Helper;
use Laminas\View\Helper\Doctype;
use PHPUnit\Framework\TestCase;

use function array_shift;
use function assert;
use function count;
use function sprintf;
use function strtolower;
use function substr_count;
use function ucfirst;
use function var_export;

use const PHP_EOL;

class HeadScriptTest extends TestCase
{
    /** @var Helper\HeadScript */
    public $helper;

    /** @var Helper\EscapeHtmlAttr */
    public $attributeEscaper;

    /** @var string */
    public $basePath;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->basePath         = __DIR__ . '/_files/modules';
        $this->helper           = new Helper\HeadScript();
        $this->attributeEscaper = new Helper\EscapeHtmlAttr();
    }

    public function testHeadScriptReturnsObjectInstance(): void
    {
        $placeholder = $this->helper->__invoke();
        $this->assertInstanceOf(Helper\HeadScript::class, $placeholder);
    }

    public function testAppendThrowsExceptionWithInvalidArguments(): void
    {
        $this->expectException(View\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument passed to append');
        $this->helper->append('foo');
    }

    public function testPrependThrowsExceptionWithInvalidArguments(): void
    {
        $this->expectException(View\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument passed to prepend');
        $this->helper->prepend('foo');
    }

    public function testSetThrowsExceptionWithInvalidArguments(): void
    {
        $this->expectException(View\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument passed to set');
        $this->helper->set('foo');
    }

    public function testOffsetSetThrowsExceptionWithInvalidArguments(): void
    {
        $this->expectException(View\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument passed to offsetSet');
        $this->helper->offsetSet(1, 'foo');
    }

    private function inflectAction(string $type): string
    {
        return ucfirst(strtolower($type));
    }

    private function executeOverloadAppend(string $type): void
    {
        $action = 'append' . $this->inflectAction($type);
        $string = 'foo';
        for ($i = 0; $i < 3; ++$i) {
            $string .= ' foo';
            $this->helper->$action($string);
            $values = $this->helper->getArrayCopy();
            $this->assertCount($i + 1, $values);
            if ('file' === $type) {
                $this->assertEquals($string, $values[$i]->attributes['src']);
            } elseif ('script' === $type) {
                $this->assertEquals($string, $values[$i]->source);
            }
            $this->assertEquals('text/javascript', $values[$i]->type);
        }
    }

    private function executeOverloadPrepend(string $type): void
    {
        $action = 'prepend' . $this->inflectAction($type);
        $string = 'foo';
        for ($i = 0; $i < 3; ++$i) {
            $string .= ' foo';
            $this->helper->$action($string);
            $values = $this->helper->getArrayCopy();
            $this->assertCount($i + 1, $values);
            $first = array_shift($values);
            if ('file' === $type) {
                $this->assertEquals($string, $first->attributes['src']);
            } elseif ('script' === $type) {
                $this->assertEquals($string, $first->source);
            }
            $this->assertEquals('text/javascript', $first->type);
        }
    }

    private function executeOverloadSet(string $type): void
    {
        $action = 'set' . $this->inflectAction($type);
        $string = 'foo';
        for ($i = 0; $i < 3; ++$i) {
            $this->helper->appendScript($string);
            $string .= ' foo';
        }
        $this->helper->$action($string);
        $values = $this->helper->getArrayCopy();
        $this->assertCount(1, $values);
        if ('file' === $type) {
            $this->assertEquals($string, $values[0]->attributes['src']);
        } elseif ('script' === $type) {
            $this->assertEquals($string, $values[0]->source);
        }
        $this->assertEquals('text/javascript', $values[0]->type);
    }

    private function executeOverloadOffsetSet(string $type): void
    {
        $action = 'offsetSet' . $this->inflectAction($type);
        $string = 'foo';
        $this->helper->$action(5, $string);
        $values = $this->helper->getArrayCopy();
        $this->assertCount(1, $values);
        if ('file' === $type) {
            $this->assertEquals($string, $values[5]->attributes['src']);
        } elseif ('script' === $type) {
            $this->assertEquals($string, $values[5]->source);
        }
        $this->assertEquals('text/javascript', $values[5]->type);
    }

    public function testOverloadAppendFileAppendsScriptsToStack(): void
    {
        $this->executeOverloadAppend('file');
    }

    public function testOverloadAppendScriptAppendsScriptsToStack(): void
    {
        $this->executeOverloadAppend('script');
    }

    public function testOverloadPrependFileAppendsScriptsToStack(): void
    {
        $this->executeOverloadPrepend('file');
    }

    public function testOverloadPrependScriptAppendsScriptsToStack(): void
    {
        $this->executeOverloadPrepend('script');
    }

    public function testOverloadSetFileOverwritesStack(): void
    {
        $this->executeOverloadSet('file');
    }

    public function testOverloadSetScriptOverwritesStack(): void
    {
        $this->executeOverloadSet('script');
    }

    public function testOverloadOffsetSetFileWritesToSpecifiedIndex(): void
    {
        $this->executeOverloadOffsetSet('file');
    }

    public function testOverloadOffsetSetScriptWritesToSpecifiedIndex(): void
    {
        $this->executeOverloadOffsetSet('script');
    }

    public function testOverloadingThrowsExceptionWithInvalidMethod(): void
    {
        $this->expectException(View\Exception\BadMethodCallException::class);
        $this->expectExceptionMessage('Method "fooBar" does not exist');
        /** @psalm-suppress UndefinedMagicMethod */
        $this->helper->fooBar('foo');
    }

    public function testSetScriptRequiresAnArgument(): void
    {
        $this->expectException(View\Exception\BadMethodCallException::class);
        $this->expectExceptionMessage('Method "setScript" requires at least one argument');
        /** @psalm-suppress TooFewArguments */
        $this->helper->setScript();
    }

    public function testOffsetSetScriptRequiresTwoArguments(): void
    {
        $this->expectException(View\Exception\BadMethodCallException::class);
        $this->expectExceptionMessage('Method "offsetSetScript" requires at least two arguments, an index and source');
        /** @psalm-suppress TooFewArguments */
        $this->helper->offsetSetScript(1);
    }

    public function testHeadScriptAppropriatelySetsScriptItems(): void
    {
        $this->helper->__invoke('FILE', 'foo', 'set')
                     ->__invoke('SCRIPT', 'bar', 'prepend')
                     ->__invoke('SCRIPT', 'baz', 'append');
        $items = $this->helper->getArrayCopy();
        for ($i = 0; $i < 3; ++$i) {
            $item = $items[$i];
            switch ($i) {
                case 0:
                    $this->assertObjectHasAttribute('source', $item);
                    $this->assertEquals('bar', $item->source);
                    break;
                case 1:
                    $this->assertObjectHasAttribute('attributes', $item);
                    $this->assertTrue(isset($item->attributes['src']));
                    $this->assertEquals('foo', $item->attributes['src']);
                    break;
                case 2:
                    $this->assertObjectHasAttribute('source', $item);
                    $this->assertEquals('baz', $item->source);
                    break;
            }
        }
    }

    public function testToStringRendersValidHtml(): void
    {
        $this->helper->__invoke('FILE', 'foo', 'set')
                     ->__invoke('SCRIPT', 'bar', 'prepend')
                     ->__invoke('SCRIPT', 'baz', 'append');
        $string = $this->helper->toString();

        $scripts = substr_count($string, '<script ');
        $this->assertEquals(3, $scripts);
        $scripts = substr_count($string, '</script>');
        $this->assertEquals(3, $scripts);
        $scripts = substr_count($string, 'src="');
        $this->assertEquals(1, $scripts);
        $scripts = substr_count($string, '><');
        $this->assertEquals(1, $scripts);

        $this->assertStringContainsString('src="foo"', $string);
        $this->assertStringContainsString('bar', $string);
        $this->assertStringContainsString('baz', $string);

        assert($string !== '');

        $doc = new DOMDocument();
        $dom = $doc->loadHtml($string);
        $this->assertTrue($dom);
    }

    public function testCapturingCapturesToObject(): void
    {
        $this->helper->captureStart();
        echo 'foobar';
        $this->helper->captureEnd();
        $values = $this->helper->getArrayCopy();
        $this->assertEquals(1, count($values), var_export($values, true));
        $item = array_shift($values);
        $this->assertStringContainsString('foobar', $item->source);
    }

    public function testIndentationIsHonored(): void
    {
        $this->helper->setIndent(4);
        $this->helper->appendScript('
var foo = "bar";
    document.write(foo.strlen());');
        $this->helper->appendScript('
var bar = "baz";
document.write(bar.strlen());');
        $string = $this->helper->toString();

        $scripts = substr_count($string, '    <script');
        $this->assertEquals(2, $scripts);
        $this->assertStringContainsString('    //', $string);
        $this->assertStringContainsString('var', $string);
        $this->assertStringContainsString('document', $string);
        $this->assertStringContainsString('    document', $string);
    }

    public function testDoesNotAllowDuplicateFiles(): void
    {
        $this->helper->__invoke('FILE', '/js/prototype.js');
        $this->helper->__invoke('FILE', '/js/prototype.js');
        $this->assertEquals(1, count($this->helper));
    }

    public function testRenderingDoesNotRenderArbitraryAttributesByDefault(): void
    {
        $this->helper->__invoke()->appendFile('/js/foo.js', 'text/javascript', ['bogus' => 'deferred']);
        $test = $this->helper->__invoke()->toString();
        $this->assertStringNotContainsString('bogus="deferred"', $test);
    }

    public function testCanRenderArbitraryAttributesOnRequest(): void
    {
        $this->helper->__invoke()->appendFile('/js/foo.js', 'text/javascript', ['bogus' => 'deferred'])
             ->setAllowArbitraryAttributes(true);
        $test = $this->helper->__invoke()->toString();
        $this->assertStringContainsString('bogus="deferred"', $test);
    }

    public function testCanPerformMultipleSerialCaptures(): void
    {
        $this->helper->__invoke()->captureStart();
        echo 'first capture';
        $this->helper->__invoke()->captureEnd();

        $this->helper->__invoke()->captureStart();
        echo 'second capture';
        $this->helper->__invoke()->captureEnd();

        self::assertStringContainsString('first capture', (string) $this->helper);
        self::assertStringContainsString('second capture', (string) $this->helper);
    }

    public function testCannotNestCaptures(): void
    {
        $this->helper->__invoke()->captureStart();
        echo "this is something captured";
        try {
            $this->helper->__invoke()->captureStart();
            $this->helper->__invoke()->captureEnd();
            $this->fail('Should not be able to nest captures');
        } catch (View\Exception\ExceptionInterface $e) {
            $this->helper->__invoke()->captureEnd();
            $this->assertStringContainsString('Cannot nest', $e->getMessage());
        }
    }

    public function testTurnOffAutoEscapeDoesNotEncodeAmpersand(): void
    {
        $this->helper->setAutoEscape(false)->appendFile('test.js?id=123&foo=bar');
        $this->assertEquals(
            '<script type="text/javascript" src="test.js?id=123&foo=bar"></script>',
            $this->helper->toString()
        );
    }

    public function testConditionalScript(): void
    {
        $this->helper->__invoke()->appendFile('/js/foo.js', 'text/javascript', ['conditional' => 'lt IE 7']);
        $test = $this->helper->__invoke()->toString();
        $this->assertStringContainsString('<!--[if lt IE 7]>', $test);
    }

    public function testConditionalScriptWidthIndentation(): void
    {
        $this->helper->__invoke()->appendFile('/js/foo.js', 'text/javascript', ['conditional' => 'lt IE 7']);
        $this->helper->__invoke()->setIndent(4);
        $test = $this->helper->__invoke()->toString();
        $this->assertStringContainsString('    <!--[if lt IE 7]>', $test);
    }

    public function testConditionalScriptNoIE(): void
    {
        $this->helper->setAllowArbitraryAttributes(true);
        $this->helper->appendFile(
            '/js/foo.js',
            'text/javascript',
            ['conditional' => '!IE']
        );
        $test = $this->helper->toString();

        $this->assertStringContainsString('<!--[if !IE]><!--><', $test);
        $this->assertStringContainsString('<!--<![endif]-->', $test);
    }

    public function testConditionalScriptNoIEWidthSpace(): void
    {
        $this->helper->setAllowArbitraryAttributes(true);
        $this->helper->appendFile(
            '/js/foo.js',
            'text/javascript',
            ['conditional' => '! IE']
        );
        $test = $this->helper->toString();

        $this->assertStringContainsString('<!--[if ! IE]><!--><', $test);
        $this->assertStringContainsString('<!--<![endif]-->', $test);
    }

    public function testContainerMaintainsCorrectOrderOfItems(): void
    {
        $this->helper->offsetSetFile(1, 'test1.js');
        $this->helper->offsetSetFile(20, 'test2.js');
        $this->helper->offsetSetFile(10, 'test3.js');
        $this->helper->offsetSetFile(5, 'test4.js');

        $test = $this->helper->toString();

        $attributeEscaper = $this->attributeEscaper;

        $expected = sprintf(
            '<script type="%2$s" src="%3$s"></script>%1$s'
            . '<script type="%2$s" src="%4$s"></script>%1$s'
            . '<script type="%2$s" src="%5$s"></script>%1$s'
            . '<script type="%2$s" src="%6$s"></script>',
            PHP_EOL,
            $attributeEscaper('text/javascript'),
            $attributeEscaper('test1.js'),
            $attributeEscaper('test4.js'),
            $attributeEscaper('test3.js'),
            $attributeEscaper('test2.js')
        );

        $this->assertEquals($expected, $test);
    }

    public function testConditionalWithAllowArbitraryAttributesDoesNotIncludeConditionalScript(): void
    {
        $this->helper->__invoke()->setAllowArbitraryAttributes(true);
        $this->helper->__invoke()->appendFile('/js/foo.js', 'text/javascript', ['conditional' => 'lt IE 7']);
        $test = $this->helper->__invoke()->toString();

        $this->assertStringNotContainsString('conditional', $test);
    }

    public function testNoEscapeWithAllowArbitraryAttributesDoesNotIncludeNoEscapeScript(): void
    {
        $this->helper->__invoke()->setAllowArbitraryAttributes(true);
        $this->helper->__invoke()->appendScript('// some script', 'text/javascript', ['noescape' => true]);
        $test = $this->helper->__invoke()->toString();

        $this->assertStringNotContainsString('noescape', $test);
    }

    public function testNoEscapeDefaultsToFalse(): void
    {
        $this->helper->__invoke()->appendScript('// some script' . PHP_EOL, 'text/javascript', []);
        $test = $this->helper->__invoke()->toString();

        $this->assertStringContainsString('//<!--', $test);
        $this->assertStringContainsString('//-->', $test);
    }

    public function testNoEscapeTrue(): void
    {
        $this->helper->__invoke()->appendScript('// some script' . PHP_EOL, 'text/javascript', ['noescape' => true]);
        $test = $this->helper->__invoke()->toString();

        $this->assertStringNotContainsString('//<!--', $test);
        $this->assertStringNotContainsString('//-->', $test);
    }

    public function testSupportsCrossOriginAttribute(): void
    {
        $this->helper->__invoke()->appendScript(
            '// some script' . PHP_EOL,
            'text/javascript',
            ['crossorigin' => true]
        );
        $test = $this->helper->__invoke()->toString();

        $this->assertStringContainsString('crossorigin="', $test);
    }

    public function testOmitsTypeAttributeIfEmptyValueAndHtml5Doctype(): void
    {
        $view = new View\Renderer\PhpRenderer();
        $view->plugin(Doctype::class)->setDoctype(View\Helper\Doctype::HTML5);
        $this->helper->setView($view);

        $this->helper->__invoke()->appendScript('// some script' . PHP_EOL, '');
        $test = $this->helper->__invoke()->toString();
        $this->assertStringNotContainsString('type', $test);
    }

    public function testSupportsAsyncAttribute(): void
    {
        $this->helper->__invoke()->appendScript(
            '// some script' . PHP_EOL,
            'text/javascript',
            ['async' => true]
        );
        $test = $this->helper->__invoke()->toString();
        $this->assertStringContainsString('async="', $test);
    }

    public function testOmitsTypeAttributeIfNoneGivenAndHtml5Doctype(): void
    {
        $view = new View\Renderer\PhpRenderer();
        $view->plugin(Doctype::class)->setDoctype(View\Helper\Doctype::HTML5);
        $this->helper->setView($view);

        $this->helper->__invoke()->appendScript('// some script' . PHP_EOL);
        $test = $this->helper->__invoke()->toString();
        $this->assertDoesNotMatchRegularExpression('#type="text/javascript"#i', $test);
    }

    public function testSupportsNonceAttribute(): void
    {
        ($this->helper)()->appendScript(
            '// some js',
            'text/javascript',
            ['nonce' => 'random']
        );

        self::assertStringContainsString(
            'nonce="random"',
            (string) ($this->helper)()
        );
    }

    /** @return Generator<string, array<int, string> */
    public function booleanAttributeDataProvider(): Generator
    {
        $values = ['async', 'defer', 'nomodule'];

        foreach ($values as $name) {
            yield $name => [$name];
        }
    }

    /** @dataProvider booleanAttributeDataProvider */
    public function testBooleanAttributesUseTheKeyNameAsTheValue(string $attribute): void
    {
        ($this->helper)()->appendScript(
            '// some js',
            'text/javascript',
            [$attribute => 'whatever']
        );

        self::assertStringContainsString(
            sprintf('%1$s="%1$s"', $attribute),
            (string) ($this->helper)()
        );
    }

    /** @dataProvider booleanAttributeDataProvider */
    public function testBooleanAttributesCanBeAppliedToModules(string $attribute): void
    {
        ($this->helper)()->appendScript(
            '// some js',
            'module',
            [$attribute => 'whatever']
        );

        self::assertStringContainsString(
            sprintf('%1$s="%1$s"', $attribute),
            (string) ($this->helper)()
        );

        self::assertStringContainsString(
            'type="module"',
            (string) ($this->helper)()
        );
    }
}
