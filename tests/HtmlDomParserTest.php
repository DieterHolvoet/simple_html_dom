<?php

use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;
use voku\helper\SimpleHtmlDomNodeInterface;

/**
 * Class HtmlDomParserTest
 *
 * @internal
 */
final class HtmlDomParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $filename
     *
     * @return string|null
     */
    protected function loadFixture($filename)
    {
        $path = __DIR__ . '/fixtures/' . $filename;
        if (\file_exists($path)) {
            return \file_get_contents($path);
        }

        return null;
    }

    public function testConstructWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        new HtmlDomParser(['foo']);
    }

    public function testLoadHtmlWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        $document = new HtmlDomParser();
        $document->loadHtml(['foo']);
    }

    public function testLoadWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        $document = new HtmlDomParser();
        $document->load(['foo']);
    }

    public function testLoadHtmlFileWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        $document = new HtmlDomParser();
        $document->loadHtmlFile(['foo']);
    }

    public function testLoadFileWithInvalidArgument()
    {
        $this->expectException(\TypeError::class);

        $document = new HtmlDomParser();
        $document->load_file(['foo']);
    }

    public function testLoadHtmlFileWithNotExistingFile()
    {
        $this->expectException(\RuntimeException::class);

        $document = new HtmlDomParser();
        $document->loadHtmlFile('/path/to/file');
    }

    public function testLoadHtmlFileWithNotLoadFile()
    {
        $this->expectException(\RuntimeException::class);

        $document = new HtmlDomParser();
        $document->loadHtmlFile('http://fobar');
    }

    public function testMethodNotExist()
    {
        $this->expectException(\BadMethodCallException::class);

        $document = new HtmlDomParser();
        /** @noinspection PhpUndefinedMethodInspection */
        $document->bar();
    }

    public function testStaticMethodNotExist()
    {
        $this->expectException(\BadMethodCallException::class);

        /** @noinspection PhpUndefinedMethodInspection */
        HtmlDomParser::bar();
    }

    public function testNotExistProperty()
    {
        $document = new HtmlDomParser();

        /** @noinspection PhpUndefinedFieldInspection */
        static::assertNull($document->foo);
    }

    public function testConstruct()
    {
        $html = '<div>foo</div>';
        $document = new HtmlDomParser($html);

        $element = new SimpleHtmlDom($document->getDocument()->documentElement);

        static::assertSame($html, $element->outertext);
    }

    public function testWebComponent()
    {
        $html = '<button is="shopping-cart">Add to cart</button>';
        $dom = HtmlDomParser::str_get_html($html);

        static::assertSame($html, $dom->outertext);
    }

    public function testWindows1252()
    {
        $file = __DIR__ . '/fixtures/windows-1252-example.html';
        $document = new HtmlDomParser();

        $document->loadHtmlFile($file);
        static::assertNotNull(\count($document('li')));

        $document->load_file($file);
        static::assertNotNull(\count($document('li')));

        $document = HtmlDomParser::file_get_html($file);
        static::assertNotNull(\count($document('li')));

        // ---

        // this only works with "UTF8"-helpers
        if (\class_exists('\voku\helper\UTF8')) {
            static::assertSame(['ÅÄÖ', 'åäö'], $document->find('li')->text());
        }
    }

    public function testLoadHtmlFile()
    {
        $file = __DIR__ . '/fixtures/test_page.html';
        $document = new HtmlDomParser();

        $document->loadHtmlFile($file);
        static::assertNotNull(\count($document('div')));

        $document->load_file($file);
        static::assertNotNull(\count($document('div')));

        $document = HtmlDomParser::file_get_html($file);
        static::assertNotNull(\count($document('div')));
    }

    public function testLoadHtml()
    {
        $html = $this->loadFixture('test_page.html');
        $document = new HtmlDomParser();

        $document->loadHtml($html);
        static::assertNotNull(\count($document('div')));

        $document->load($html);
        static::assertNotNull(\count($document('div')));

        $document = HtmlDomParser::str_get_html($html);
        static::assertNotNull(\count($document('div')));
    }

    public function testGetDocument()
    {
        $document = new HtmlDomParser();
        static::assertInstanceOf(\DOMDocument::class, $document->getDocument());
    }

    /**
     * @dataProvider findTests
     *
     * @param $html
     * @param $selector
     * @param $count
     */
    public function testFind($html, $selector, $count)
    {
        $document = new HtmlDomParser($html);
        $elements = $document->find($selector);

        static::assertInstanceOf(voku\helper\SimpleHtmlDomNode::class, $elements);
        static::assertCount($count, $elements);

        foreach ($elements as $element) {
            static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $element);
        }

        if ($count !== 0) {
            $element = $document->find($selector, -1);
            static::assertInstanceOf(voku\helper\SimpleHtmlDom::class, $element);
        }
    }

    /**
     * @return array
     */
    public function findTests()
    {
        $html = $this->loadFixture('test_page.html');

        return [
            [$html, '.fake h2', 0],
            [$html, 'article', 16],
            [$html, '.radio', 3],
            [$html, 'input.radio', 3],
            [$html, 'ul li', 35],
            [$html, 'fieldset#forms__checkbox li, fieldset#forms__radio li', 6],
            [$html, 'input[id]', 23],
            [$html, 'input[id=in]', 1],
            [$html, '#in', 1],
            [$html, '*[id]', 52],
            [$html, 'text', 640],
            [$html, 'comment', 3],
        ];
    }

    public function testHtml()
    {
        $html = $this->loadFixture('test_page.html');
        $document = new HtmlDomParser($html);

        $htmlTmp = $document->html();
        static::assertInternalType('string', $htmlTmp);

        $xmlTmp = $document->xml();
        static::assertInternalType('string', $xmlTmp);

        static::assertInternalType('string', $document->outertext);
        static::assertTrue(\strlen($document) > 0);

        $html = '<div>foo</div>';
        $document = new HtmlDomParser($html);

        static::assertSame($html, $document->html());
        static::assertSame($html, $document->outertext);
        static::assertSame($html, (string) $document);
    }

    public function testInnerHtml()
    {
        $html = '<div><div>foo</div></div>';
        $document = new HtmlDomParser($html);

        static::assertSame('<div>foo</div>', $document->innerHtml());
        static::assertSame('<div>foo</div>', $document->innerText());
        static::assertSame('<div>foo</div>', $document->innertext);
    }

    public function testText()
    {
        $html = '<div>foo</div>';
        $document = new HtmlDomParser($html);

        static::assertSame('foo', $document->text());
        static::assertSame('foo', $document->plaintext);
    }

    public function testSave()
    {
        $html = $this->loadFixture('test_page.html');
        $document = new HtmlDomParser($html);

        static::assertInternalType('string', $document->save());
    }

    public function testClear()
    {
        $document = new HtmlDomParser();

        static::assertTrue($document->clear());
    }

    public function testStrGetHtml()
    {
        $str = <<<HTML
中

<form name="form1" method="post" action="">
    <input type="checkbox" name="checkbox1" value="checkbox1" checked>abc-1<br>
    <input type="checkbox" name="checkbox2" value="checkbox2">öäü-2<br>
    <input type="checkbox" name="checkbox3" value="checkbox3" checked>中文空白-3<br>
</form>
HTML;

        $html = HtmlDomParser::str_get_html($str);
        $checkboxArray = [];
        foreach ($html->find('input[type=checkbox]') as $checkbox) {
            if ($checkbox->checked) {
                $checkboxArray[(string) $checkbox->name] = 'checked';
            } else {
                $checkboxArray[(string) $checkbox->name] = 'not checked';
            }
        }

        static::assertCount(3, $checkboxArray);
        static::assertSame('checked', $checkboxArray['checkbox1']);
        static::assertSame('not checked', $checkboxArray['checkbox2']);
        static::assertSame('checked', $checkboxArray['checkbox3']);
    }

    public function testOutertext()
    {
        $str = <<<HTML
<form name="form1" method="post" action=""><input type="checkbox" name="checkbox1" value="checkbox1" checked>中文空白</form>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        foreach ($html->findMulti('input') as $e) {
            $e->outertext = '[INPUT]';
        }

        static::assertSame('<form name="form1" method="post" action="">' . "\n" . '[INPUT]中文空白</form>', (string) $html);
    }

    public function testInnertextWithHtmlHeadTag()
    {
        $str = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="hello">Hello</div><div id="world">World</div></body></html>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        $html->find('head', 0)->innerText = '<meta http-equiv="Content-Type" content="text/html; charset=utf-7">';

        static::assertSame(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd"><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-7"></head><body><div id="hello">Hello</div><div id="world">World</div></body></html>',
            \str_replace(
                [
                    "\r\n",
                    "\r",
                    "\n",
                ],
                '',
                (string) $html
            )
        );
    }

    public function testInnertextWithHtml()
    {
        $str = <<<HTML
<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="hello">Hello</div><div id="world">World</div></body></html>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        $html->find('div', 1)->class = 'bar';
        $html->find('div[id=hello]', 0)->innertext = '<foo>bar</foo>';

        static::assertSame(
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="hello"><foo>bar</foo></div><div id="world" class="bar">World</div></body></html>',
            \str_replace(
                [
                    "\r\n",
                    "\r",
                    "\n",
                ],
                '',
                (string) $html
            )
        );
    }

    public function testInnertext()
    {
        $str = <<<HTML
<div id="hello">Hello</div><div id="world">World</div>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        $html->find('div', 1)->class = 'bar';
        $html->find('div[id=hello]', 0)->innertext = 'foo';

        static::assertSame('<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>', (string) $html);
    }

    public function testMail2()
    {
        $filename = __DIR__ . '/fixtures/test_mail.html';
        $filenameExpected = __DIR__ . '/fixtures/test_mail_expected.html';

        $html = HtmlDomParser::file_get_html($filename);
        $htmlExpected = \str_replace(["\r\n", "\r", "\n"], "\n", \file_get_contents($filenameExpected));

        // object to sting
        static::assertSame(
            $htmlExpected,
            \str_replace(["\r\n", "\r", "\n"], "\n", (string) $html)
        );

        $preHeaderContentArray = $html->findMulti('.preheaderContent');

        static::assertSame('padding-top:10px; padding-right:20px; padding-bottom:10px; padding-left:20px;', $preHeaderContentArray[0]->style);
        static::assertSame('top', $preHeaderContentArray[0]->valign);
    }

    public function testMail()
    {
        $str = <<<HTML
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title></title>
</head>
<body bgcolor="#FF9900" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<center>
  <style type="text/css">
    body {
      background: #f2f2f2;
      margin: 0;
      padding: 0;
    }

    td, p, span {
      font-family: verdana, arial, sans-serif;
      font-size: 14px;
      line-height: 16px;
      color: #666;
    }

    a {
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
      <td bgcolor="#FF9900">
        <img src="/images/nl/transparent.gif" alt="" width="5" height="3" border="0"></td>
    </tr>
    </tbody>
  </table>
  <table width="620" border="0" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
      <td>
        <!-- HEADER -->
        <table width="620" border="0" cellspacing="0" cellpadding="0">
          <tbody>
          <tr>
            <td bgcolor="#ffffff">
              <table width="620" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="43" border="0">
                  </td>
                  <td width="298" align="left" valign="middle">
                    <font style="font-family:verdana,arial,sans-serif; font-size:12px; color:#666666;" face="verdana,arial,helvetica,sans-serif" size="2" color="#666666"></font>
                  </td>
                  <td width="298" align="right" valign="middle">
                    <font style="font-family:verdana,arial,helvetica,sans-serif; font-size:18px; color:#FF9900;" face="verdana,arial,helvetica,sans-serif" size="3" color="#FF9900">test</font></td>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="43" border="0">
                  </td>
                </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td>
              <a href="test" target="_blank"><img src="/images/nl/default_header_visual2.jpg" width="620" alt="test" border="0"></a>
            </td>
          </tr>
          <tr>
            <td bgcolor="#FF9900">
              <table width="620" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="5" border="0"></td>
                  <td width="300" align="left">
                    <font style="font-family:verdana,arial,sans-serif; font-size:14px; line-height:16px; color:#ffffff;" face="verdana,arial,helvetica,sans-serif" size="2" color="#ffffff">


                      <b>this is a test öäü ... foobar ... <span class="utf8">דיעס איז אַ פּרובירן!</span>span></b>
test3Html.html                      <foo id="foo">bar</foo>
                      <test_>lall</test_>
                      <br/><br/>
                      <br/><br/>

                      Lorem ipsum dolor sit amet, consectetur adipisicing elit. At commodi doloribus, esse inventore ipsam itaque laboriosam molestias nesciunt nihil reiciendis rem rerum? Aliquam aperiam doloremque ea harum laborum nam neque nostrum perferendis quas reiciendis. Ab accusamus, alias facilis labore minima molestiae nihil omnis quae quidem, reiciendis sint sit velit voluptatem!

                      <br/><br/>
                      <a href="test" style="font-family:\'Century Gothic\',verdana,sans-serif; font-size:22px; line-height:24px; color:#ffffff;" target="_blank"><img src="/images/nl/button_entdecken_de.jpg" border="0"></a>
                      <br/><br/>
                      Ihr Team
                    </font></td>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="5" border="0"></td>

                </tr>
                <tr>
                  <td colspan="3">
                    <img src="/images/nl/transparent.gif" alt="" width="5" height="30" border="0"></td>
                </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" valign="top">
              <img src="/images/nl/teaser_shadow.jpg" alt="" width="620" height="16" border="0"></td>
          </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr>
      <td>
        <!-- FOOTER -->
        <table width="620" border="0" cellspacing="0" cellpadding="0">
          <tbody>
          <tr>
            <td><img src="/images/nl/transparent.gif" alt="" width="5" height="25" border="0"></td>
          </tr>
          <tr>
            <td align="center">
              <font style="font-family:\'Century Gothic\',verdana,sans-serif; font-size:11px; line-height:14px; color:#cc0000;" face="\'Century Gothic\',verdana,sans-serif" size="1" color="#cc0000">
                <a href="test" target="_blank" style="color:#666666;"><font style="font-family:\'Century Gothic\',verdana,sans-serif; font-size:11px; line-height:14px; color:#666666;" face="\'Century Gothic\',verdana,sans-serif" size="1" color="#666666">IMPRESSUM &amp; RECHTLICHES</font></a>
              </font></td>
          </tr>
          <tr>
            <td><img src="/images/nl/transparent.gif" alt="" width="5" height="10" border="0"></td>
          </tr>
          <tr>
            <td align="center" valign="top">
              <img src="/images/nl/footer_shadow.jpg" alt="" width="620" height="14" border="0"></td>
          </tr>
          <tr>
            <td><img src="/images/i/nl/transparent.gif" alt="" width="5" height="10" border="0"></td>
          </tr>
          <tr>
            <td>
              <table width="620" border="0" cellspacing="0" cellpadding="0">
                <tbody>
                <tr>
                  <td width="358" align="right" valign="middle">
                    <font style="font-family:\'Century Gothic\',verdana,sans-serif; font-size:11px; line-height:14px; color:#666666;" face="\'Century Gothic\',verdana,sans-serif" size="1" color="#666666">© 2015 Test AG &amp; Co. KGaA</font>
                  </td>
                  <td width="12">
                    <img src="/images/nl/transparent.gif" alt="" width="12" height="5" border="0"></td>
                  <td width="250" align="left" valign="middle">
                    <a href="test" target="_blank"><img src="/nl/footer_logo.jpg" alt="test" width="60" height="34" border="0"></a>
                  </td>
                </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td><img src="/images/nl/transparent.gif" alt="○●◎ earth 中文空白" width="5" height="20" border="0"></td>
          </tr>
          </tbody>
        </table>
      </td>
    </tr>
    </tbody>
  </table>
</center>

</body>
</html>
HTML;

        $htmlTmp = HtmlDomParser::str_get_html($str);
        static::assertInstanceOf(voku\helper\HtmlDomParser::class, $htmlTmp);

        // replace all images with "foobar"
        $tmpArray = [];
        foreach ($htmlTmp->findMulti('img') as $e) {
            if ($e->src !== '') {
                $tmpArray[] = $e->src;

                $e->src = 'foobar';
            }
        }

        $testString = false;
        $tmpCounter = 0;
        foreach ($htmlTmp->findMulti('table tr td img') as $e) {
            if ($e->alt === '○●◎ earth 中文空白') {
                $testString = $e->alt;

                break;
            }
            $tmpCounter++;
        }
        static::assertSame(15, $tmpCounter);
        static::assertSame('○●◎ earth 中文空白', $testString);

        // get the content from the css-selector

        $testStringUtf8_v1 = $htmlTmp->find('html .utf8');
        static::assertSame('דיעס איז אַ פּרובירן!', $testStringUtf8_v1[0]->innertext);
        static::assertSame('<span class="utf8">דיעס איז אַ פּרובירן!</span>', $testStringUtf8_v1[0]->html(true));

        $testStringUtf8_v2 = $htmlTmp->find('span.utf8');
        static::assertSame('דיעס איז אַ פּרובירן!', $testStringUtf8_v2[0]->innertext);
        static::assertSame('<span class="utf8">דיעס איז אַ פּרובירן!</span>', $testStringUtf8_v2[0]->html(true));

        $testStringUtf8_v3 = $htmlTmp->find('.utf8');
        static::assertSame('דיעס איז אַ פּרובירן!', $testStringUtf8_v3[0]->innertext);
        static::assertSame('<span class="utf8">דיעס איז אַ פּרובירן!</span>', $testStringUtf8_v3[0]->html(true));

        $testStringUtf8_v4 = $htmlTmp->find('foo');
        static::assertSame('bar', $testStringUtf8_v4[0]->innertext);
        static::assertSame('<foo id="foo">bar</foo>', $testStringUtf8_v4[0]->html(true));

        $testStringUtf8_v5 = $htmlTmp->find('#foo');
        static::assertSame('bar', $testStringUtf8_v5[0]->innertext);
        static::assertSame('<foo id="foo">bar</foo>', $testStringUtf8_v5[0]->outertext);

        $testStringUtf8_v6 = $htmlTmp->find('test_');
        static::assertSame('lall', $testStringUtf8_v6[0]->innertext);
        static::assertSame('<test_>lall</test_>', $testStringUtf8_v6[0]->outertext);

        $testStringUtf8_v7 = $htmlTmp->getElementById('foo');
        static::assertSame('bar', $testStringUtf8_v7->innertext);

        $testStringUtf8_v8 = $htmlTmp->getElementByTagName('foo');
        static::assertSame('bar', $testStringUtf8_v8->innertext);

        $testStringUtf8_v9 = $htmlTmp->getElementsByTagName('img', 15);
        static::assertSame('○●◎ earth 中文空白', $testStringUtf8_v9->alt);
        static::assertSame('', $testStringUtf8_v9->innertext);
        static::assertSame('<img src="foobar" alt="○●◎ earth 中文空白" width="5" height="20" border="0">', $testStringUtf8_v9->html(true));

        // test toString
        $htmlTmp = (string) $htmlTmp;
        static::assertCount(16, $tmpArray);
        static::assertContains('<img src="foobar" alt="" width="5" height="3" border="0">', $htmlTmp);
        static::assertContains('© 2015 Test', $htmlTmp);
    }

    public function testSetAttr()
    {
        $html = '<html><script type="application/ld+json"></script><p></p><div id="p1" class="post">foo</div><div class="post" id="p2">bar</div></html>';
        $expected = '<html><script type="application/ld+json"></script><p></p><div class="post" id="p1">foo</div><div class="post" id="p2">bar</div></html>';

        $document = new HtmlDomParser($html);

        foreach ($document->find('div') as $e) {
            $attrs = [];
            foreach ($e->getAllAttributes() as $attrKey => $attrValue) {
                $attrs[$attrKey] = $attrValue;
                $e->{$attrKey} = null;
            }

            \ksort($attrs);

            foreach ($attrs as $attrKey => $attrValue) {
                $e->{$attrKey} = $attrValue;
            }
        }

        static::assertSame($expected, $document->html());
    }

    public function testEditLinks()
    {
        $texts = [
            '<a href="http://foobar.de" class="  more  "  >Mehr</a><a href="http://foobar.de" class="  more  "  >Mehr</a>'                                                                                                                                                                                                                                                                              => '<a href="http://foobar.de" class="  more  " data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de\');">Mehr</a><a href="http://foobar.de" class="  more  " data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de\');">Mehr</a>',
            ' <p><a href="http://foobar.de" class="  more  "  >Mehr</a></p>'                                                                                                                                                                                                                                                                                                                            => '<p><a href="http://foobar.de" class="  more  " data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de\');">Mehr</a></p>',
            '<a <a href="http://foobar.de">foo</a><div></div>'                                                                                                                                                                                                                                                                                                                                          => '<a href="http://foobar.de" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de\');">foo</a><div></div>',
            ' <p></p>'                                                                                                                                                                                                                                                                                                                                                                                  => '<p></p>',
            ' <p>'                                                                                                                                                                                                                                                                                                                                                                                      => '<p></p>',
            'p>'                                                                                                                                                                                                                                                                                                                                                                                        => 'p>',
            'p'                                                                                                                                                                                                                                                                                                                                                                                         => 'p',
            'Google+ && Twitter || Lînux'                                                                                                                                                                                                                                                                                                                                                               => 'Google+ && Twitter || Lînux',
            '<p>Google+ && Twitter || Lînux</p>'                                                                                                                                                                                                                                                                                                                                                        => '<p>Google+ && Twitter || Lînux</p>',
            '<p>Google+ && Twitter ||&nbsp;Lînux</p>'                                                                                                                                                                                                                                                                                                                                                   => '<p>Google+ && Twitter ||&nbsp;Lînux</p>',
            '<a href="http://foobar.de[[foo]]&{{foobar}}&lall=1">foo</a>'                                                                                                                                                                                                                                                                                                                               => '<a href="http://foobar.de[[foo]]&{{foobar}}&lall=1" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de[[foo]]&{{foobar}}&lall=1\');">foo</a>',
            '<div><a href="http://foobar.de[[foo]]&{{foobar}}&lall=1">foo</a>'                                                                                                                                                                                                                                                                                                                          => '<div><a href="http://foobar.de[[foo]]&{{foobar}}&lall=1" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=foobar.de[[foo]]&{{foobar}}&lall=1\');">foo</a></div>',
            ''                                                                                                                                                                                                                                                                                                                                                                                          => '',
            '<a href=""><span>lalll=###test###&bar=%5B%5Bfoobar%5D%5D&test=[[foobar]]&foo={{lall}}</span><img src="http://foobar?lalll=###test###&bar=%5B%5Bfoobar%5D%5D&test=[[foobar]]&foo={{lall}}" style="max-width:600px;" alt="Ihr Unternehmen in den wichtigsten Online-Verzeichnissen" class="headerImage" mc:label="header_image" mc:edit="header_image" mc:allowdesigner mc:allowtext /></a>' => '<a href="" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=\');"><span>lalll=###test###&bar=%5B%5Bfoobar%5D%5D&test=[[foobar]]&foo={{lall}}</span><img src="http://foobar?lalll=###test###&bar=%5B%5Bfoobar%5D%5D&test=[[foobar]]&foo={{lall}}" style="max-width:600px;" alt="Ihr Unternehmen in den wichtigsten Online-Verzeichnissen" class="headerImage" mc:label="header_image" mc:edit="header_image" mc:allowdesigner mc:allowtext></a>',
            'this is a test <a href="http://menadwork.com/test/?foo=1">test1</a> lall <a href="http://menadwork.com/test/?foo=1&lall=2">test2</a> ... <a href="http://menadwork.com">test3</a>'                                                                                                                                                                                                         => 'this is a test <a href="http://menadwork.com/test/?foo=1" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=menadwork.com\');">test1</a> lall <a href="http://menadwork.com/test/?foo=1&lall=2" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=menadwork.com\');">test2</a> ... <a href="http://menadwork.com" data-url-parse="done" onClick="$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=menadwork.com\');">test3</a>',
        ];

        foreach ($texts as $text => $expected) {
            $dom = HtmlDomParser::str_get_html($text);

            foreach ($dom->find('a') as $item) {
                $href = $item->getAttribute('href');
                $dataUrlParse = $item->getAttribute('data-url-parse');

                if ($dataUrlParse) {
                    continue;
                }

                $parseLink = \parse_url($href);
                $domain = ($parseLink['host'] ?? '');

                $item->setAttribute('data-url-parse', 'done');
                $item->setAttribute('onClick', '$.get(\'/incext.php?brandcontact=1&click=1&page_id=1&brand=foobar&domain=' . \urlencode($domain) . '\');');
            }

            static::assertSame($expected, $dom->html(true), 'tested: ' . $text);
        }
    }

    public function testWithUTF8()
    {
        $str = '<p>イリノイ州シカゴにて</p>';

        $html = HtmlDomParser::str_get_html($str);

        $html->find('p', 1)->class = 'bar';

        static::assertSame(
            '<p>イリノイ州シカゴにて</p>',
            $html->html()
        );

        static::assertSame(
            'イリノイ州シカゴにて',
            $html->text()
        );

        // ---

        $str = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF8"><title>jusqu’à 51% de rabais!</title></head><body></body></html>';

        $html = HtmlDomParser::str_get_html($str);

        $title = $html->find('title', 0);

        static::assertSame(
            'jusqu’à 51% de rabais!',
            $title->innerHtml
        );

        static::assertSame(
            'jusqu’à 51% de rabais!',
            $title->innerHtml()
        );

        static::assertSame(
            'jusqu’à 51% de rabais!',
            $title->innerText
        );

        static::assertSame(
            'jusqu’à 51% de rabais!',
            $title->innerText()
        );
    }

    public function testWithExtraXmlOptions()
    {
        $str = <<<HTML
<div id="hello">Hello</div><div id="world">World</div><strong></strong>
HTML;

        $html = HtmlDomParser::str_get_html($str, \LIBXML_NOERROR);

        $html->find('div', 1)->class = 'bar';
        $html->find('div[id=hello]', 0)->innertext = 'foo';
        $html->findOne('div[id=hello]')->innertext = 'foo';

        static::assertSame(
            '<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>' . "\n" . '<strong></strong>',
            $html->html()
        );

        // -------------

        $html->find('div[id=fail]', 0)->innertext = 'foobar';

        static::assertSame(
            '<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>' . "\n" . '<strong></strong>',
            (string) $html
        );
    }

    public function testEditInnerText()
    {
        $str = <<<HTML
<div id="hello">Hello</div><div id="world">World</div>
HTML;

        $html = HtmlDomParser::str_get_html($str);

        $html->find('div', 1)->class = 'bar';
        $html->find('div[id=hello]', 0)->innertext = 'foo';

        static::assertSame('<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>', (string) $html);

        // -------------

        $html->find('div[id=fail]', 0)->innertext = 'foobar';

        static::assertSame('<div id="hello">foo</div>' . "\n" . '<div id="world" class="bar">World</div>', (string) $html);
    }

    public function testLoad()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        $div = $dom->find('div', 0);
        static::assertSame(
            '<div class="all"><p>Hey bro, <a href="google.com">click here</a><br> :)</p></div>',
            $div->outertext
        );
    }

    public function testNotLoaded()
    {
        $dom = new HtmlDomParser();
        $div = $dom->find('div', 0);

        static::assertSame([], $div->plaintext);
    }

    public function testIncorrectAccess()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        $div = $dom->find('div', 0);
        static::assertSame('', $div->foo);
    }

    public function testLoadSelfclosingAttr()
    {
        $dom = new HtmlDomParser();
        $dom->load("<div class='all'><br  foo  bar  />baz</div>");
        $br = $dom->find('br', 0);
        static::assertSame('<br foo bar>', $br->outerHtml);
    }

    public function testLoadSelfclosingAttrToString()
    {
        $dom = new HtmlDomParser();
        $dom->load("<div class='all'><br  foo  bar  />baz</div>");
        $br = $dom->find('br', 0);
        static::assertSame('<br foo bar>', (string) $br);
    }

    public function testBrokenHtmlAtTheBeginOfTheInput()
    {
        $dom = new HtmlDomParser();
        $dom->useKeepBrokenHtml(true);
        /* @noinspection JSUnresolvedVariable */
        /* @noinspection UnterminatedStatementJS */
        /* @noinspection BadExpressionStatementJS */
        /* @noinspection JSUndeclaredVariable */
        $html = '</script><script async src="cdnjs"></script>';
        $dom->load($html);
        static::assertSame('</script><script async src="cdnjs"></script>', $dom->innerHtml);
    }

    public function testBrokenHtmlInTheMiddleOfTheInput()
    {
        $dom = new HtmlDomParser();
        $dom->useKeepBrokenHtml(true);
        /* @noinspection JSUnresolvedVariable */
        /* @noinspection UnterminatedStatementJS */
        /* @noinspection BadExpressionStatementJS */
        /* @noinspection JSUndeclaredVariable */
        $html = '<script async src="cdnjs"></script></borken foo="lall"><p>some text ...</p>';
        $dom->load($html);
        static::assertSame('<script async src="cdnjs"></script></borken foo="lall"><p>some text ...</p>', $dom->innerHtml);
    }

    public function testLoadNoOpeningTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><font color="red"><strong>PR Manager</strong></font></b><div class="content">content</div></div>');
        static::assertSame('content', $dom->find('.content', 0)->text);
    }

    public function testLoadNoClosingTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a></div>');
        $root = $dom->find('div', 0);
        static::assertSame('<div class="all"><p>Hey bro, <a href="google.com">click here</a></p></div>', $root->outerHtml);
    }

    public function testLoadAttributeOnSelfClosing()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a></div><br class="both" />');
        $br = $dom->find('br', 0);
        static::assertSame('both', $br->getAttribute('class'));
    }

    public function testLoadClosingTagOnSelfClosing()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><br><p>Hey bro, <a href="google.com">click here</a></br></div>');
        static::assertSame('<br><p>Hey bro, <a href="google.com">click here</a></p>', $dom->find('div', 0)->innerHtml);
    }

    public function testScriptWithoutScriptTag()
    {
        $test = 'window.jQuery || document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')';
        $dom = new HtmlDomParser();
        $dom->load($test);
        static::assertSame(
            'window.jQuery || document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')',
            $dom->html()
        );
    }

    public function testScriptInHeadScript()
    {
        $dom = new HtmlDomParser();
        $dom->load(
            '
              <!DOCTYPE html>
              <html>
              <head>
                  <meta name="robots" content="noindex, follow">
                  <style>
                      /** quick fix because bootstrap <pre> has a background-color. */
                      pre code { background-color: inherit; }
                  </style>
              </head>
              <body class="blog">
              <header>
                  <nav>
                  </nav>
              </header>
              <script>window.jQuery || document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')</script>
              </body>
              </html>
              '
        );
        static::assertSame(
            '<script>window.jQuery || document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')</script>',
            $dom->findOne('script')->html()
        );

        // ---

        $script = $dom->findOne('script');
        $script->outerHtml = '<script>window.jQuery||document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')</script>';

        static::assertSame(
            '<script>window.jQuery||document.write(\'<script src="http://lall/jquery/jquery.min.js"><\/script>\')</script>',
            $dom->findOne('script')->html()
        );
    }

    public function testLoadNoValueAttribute()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="content"><div class="grid-container" ui-view>Main content here</div></div>');
        static::assertSame('<div class="content"><div class="grid-container" ui-view>Main content here</div></div>', $dom->innerHtml);
    }

    public function testLoadNoValueAttributeBefore()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="content"><div ui-view class="grid-container">Main content here</div></div>');
        static::assertSame('<div class="content"><div ui-view class="grid-container">Main content here</div></div>', $dom->innerHtml);
    }

    public function testSimpleHtmlViaSimpleXmlLoadString()
    {
        $html = (new HtmlDomParser())->load('<span>&lt;</span>');

        $expected = '<span>&lt;</span>';

        static::assertSame($expected, $html->xml());
        static::assertSame($expected, $html->html(false));
        static::assertSame($expected, $html->html(true));
    }

    public function testLoadUpperCase()
    {
        $dom = new HtmlDomParser();
        $dom->load('<DIV CLASS="ALL"><BR><P>hEY BRO, <A HREF="GOOGLE.COM">click here</A></BR></DIV>');
        static::assertSame('<br><p>hEY BRO, <a href="GOOGLE.COM">click here</a></p>', $dom->find('div', 0)->innerHtml);
    }

    public function testLoadWithFile()
    {
        $dom = new HtmlDomParser();
        $dom->load_file(__DIR__ . '/fixtures/small.html');
        static::assertSame('VonBurgermeister', $dom->find('.post-user font', 0)->text);
    }

    public function testLoadFromFile()
    {
        $dom = new HtmlDomParser();
        $dom->load_file(__DIR__ . '/fixtures/small.html');
        static::assertSame('VonBurgermeister', $dom->find('.post-user font', 0)->text);
    }

    public function testLoadFromFileFind()
    {
        $dom = new HtmlDomParser();
        $dom->load_file(__DIR__ . '/fixtures/small.html');
        static::assertSame('VonBurgermeister', $dom->find('.post-row div .post-user font', 0)->text);
    }

    public function testLoadUtf8()
    {
        $dom = new HtmlDomParser();
        $dom->load('<p>Dzień</p>');
        static::assertSame('Dzień', $dom->find('p', 0)->text);
    }

    public function testLoadFileBigTwice()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtmlFile(__DIR__ . '/fixtures/big.html');
        $post = $dom->find('.post-row', 0);
        static::assertSame('<p>Журчанье воды<br>' . "\n" . 'Черно-белые тени<br>' . "\n" . 'Вновь на фонтане</p>', $post->find('.post-message', 0)->innerHtml);
    }

    public function testToStringMagic()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        static::assertSame('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br> :)</p></div>', (string) $dom);
    }

    public function testGetMagic()
    {
        $dom = new HtmlDomParser();

        $html = '<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>';
        $expected = '<p>Hey bro, <a href="google.com">click here</a><br> :)</p>';

        $dom->load($html);
        static::assertSame($expected, $dom->innerHtml);

        // ---

        $dom = new HtmlDomParser();

        $html = '<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>';
        $expected = '<div class="all"><p>Hey bro, <a href="google.com">click here</a><br> :)</p></div>';

        $dom->load($html);
        static::assertSame($expected, $dom->html());
    }

    public function testGetElementById()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com" id="78">click here</a></div><br />');
        static::assertSame('<a href="google.com" id="78">click here</a>', $dom->getElementById('78')->outerHtml);
    }

    public function testGetElementsByTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com" id="78">click here</a></div><br />');
        $elm = $dom->getElementsByTagName('p');
        static::assertSame(
            '<p>Hey bro, <a href="google.com" id="78">click here</a></p>',
            $elm[0]->outerHtml
        );
    }

    public function testGetElementsByClass()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com" id="78">click here</a></div><br />');
        $elm = $dom->find('.all');
        static::assertSame(
            '<p>Hey bro, <a href="google.com" id="78">click here</a></p>',
            $elm[0]->innerHtml
        );
    }

    public function testUtf8AndBrokenHtmlEncoding()
    {
        $dom = new HtmlDomParser();
        $dom->load('hi سلام<div>の家庭に、9 ☆<><');
        static::assertSame(
            'hi سلام<div>の家庭に、9 ☆</div>',
            $dom->innerHtml
        );

        // ---

        $dom = new HtmlDomParser();
        $dom->load('hi</b>سلام<div>の家庭に、9 ☆<><');
        static::assertSame(
            'hiسلام<div>の家庭に、9 ☆</div>',
            $dom->innerHtml
        );

        // ---

        $dom = new HtmlDomParser();
        $dom->load('hi</b><p>سلام<div>の家庭に、9 ☆<><');
        static::assertSame(
            'hi<p>سلام' . "\n" . '<div>の家庭に、9 ☆</div>',
            $dom->innerHtml
        );
    }

    public function testEnforceEncoding()
    {
        $dom = new HtmlDomParser();
        $dom->load('tests/files/horrible.html');

        static::assertNotSame('<input type="submit" tabindex="0" name="submit" value="Информации" />', $dom->find('table input', 1)->outerHtml);
    }

    public function testReplaceToPreserveHtmlEntities()
    {
        $tests = [
            // non url && non dom special chars -> no changes
            '' => '',
            // non url && non dom special chars -> no changes
            ' ' => ' ',
            // non url && non dom special chars -> no changes
            'abc' => 'abc',
            // non url && non dom special chars -> no changes
            'öäü' => 'öäü',
            // non url && non dom special chars -> no changes
            '`?/=()=$"?#![{`' => '`?/=()=$"?#![{`',
            // non url && non dom special chars -> no changes
            '{{foo}}' => '{{foo}}',
            // dom special chars -> changes
            '`?/=()=$&,|,+,%"?#![{@`' => '`?/=()=$____SIMPLE_HTML_DOM__VOKU__AMP____,____SIMPLE_HTML_DOM__VOKU__PIPE____,____SIMPLE_HTML_DOM__VOKU__PLUS____,____SIMPLE_HTML_DOM__VOKU__PERCENT____"?#![{____SIMPLE_HTML_DOM__VOKU__AT____`',
            // non url && non dom special chars -> no changes
            'www.domain.de/foo.php?foobar=1&email=lars%40moelleken.org&guid=test1233312&{{foo}}' => 'www.domain.de/foo.php?foobar=1____SIMPLE_HTML_DOM__VOKU__AMP____email=lars____SIMPLE_HTML_DOM__VOKU__PERCENT____40moelleken.org____SIMPLE_HTML_DOM__VOKU__AMP____guid=test1233312____SIMPLE_HTML_DOM__VOKU__AMP____{{foo}}',
            // url -> changes
            '[https://www.domain.de/foo.php?foobar=1&email=lars%40moelleken.org&guid=test1233312&{{foo}}#bar]' => '____SIMPLE_HTML_DOM__VOKU__SQUARE_BRACKET_LEFT____https://www.domain.de/foo.php?foobar=1____SIMPLE_HTML_DOM__VOKU__AMP____email=lars____SIMPLE_HTML_DOM__VOKU__PERCENT____40moelleken.org____SIMPLE_HTML_DOM__VOKU__AMP____guid=test1233312____SIMPLE_HTML_DOM__VOKU__AMP________SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT________SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT____foo____SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT________SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT____#bar____SIMPLE_HTML_DOM__VOKU__SQUARE_BRACKET_RIGHT____',
            // url -> changes
            'https://www.domain.de/foo.php?foobar=1&email=lars%40moelleken.org&guid=test1233312&{{foo}}#foo' => 'https://www.domain.de/foo.php?foobar=1____SIMPLE_HTML_DOM__VOKU__AMP____email=lars____SIMPLE_HTML_DOM__VOKU__PERCENT____40moelleken.org____SIMPLE_HTML_DOM__VOKU__AMP____guid=test1233312____SIMPLE_HTML_DOM__VOKU__AMP________SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT________SIMPLE_HTML_DOM__VOKU__BRACKET_LEFT____foo____SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT________SIMPLE_HTML_DOM__VOKU__BRACKET_RIGHT____#foo',
        ];

        foreach ($tests as $test => $expected) {
            $result = HtmlDomParser::replaceToPreserveHtmlEntities($test);
            static::assertSame($expected, $result);

            $result = HtmlDomParser::putReplacedBackToPreserveHtmlEntities($result);
            static::assertSame($test, $result);
        }
    }

    public function testUseXPath()
    {
        $dom = new HtmlDomParser();
        $dom->loadHtml(
            '
            <html>
              <head></head>
              <body>
                <p>.....</p>
                <script>
                Some code ... 
                document.write("<script src=\'some script\'><\/script>") 
                Some code ... 
                </script>
                <p>....</p>
              </body>
            </html>'
        );
        $elm = $dom->find('*');
        static::assertSame('.....', $elm[3]->innerHtml);

        $elm = $dom->find('//*');
        static::assertSame('.....', $elm[3]->innerHtml);
    }

    public function testScriptCleanerScriptTag()
    {
        $dom = new HtmlDomParser();
        $dom->load(
            '
            <p>.....</p>
            <script>
            Some code ... 
            document.write("<script src=\'some script\'><\/script>") 
            Some code ... 
            </script>
            <p>....</p>'
        );
        $elm = $dom->getElementsByTagName('p');
        static::assertSame('....', $elm[1]->innerHtml);
    }

    public function testSpecialScriptTag()
    {
        // init
        $html = '
        <!doctype html>
        <html lang="fr">
        <head>
            <title>Test</title>
        </head>
        <body>
            A Body
        
            <script id="elements-image-1" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script id="elements-image-2" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script class="foobar" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            <script class="foobar" type=\'text/html\'>
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script class="foobar" type=text/html>
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
        </body>
        </html>
        ';

        $expected = '
        <!DOCTYPE html>' . "\n" . '<html lang="fr">
        <head>
            <title>Test</title>
        </head>
        <body>
            A Body
        
            <script id="elements-image-1" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script id="elements-image-2" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script class="foobar" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            <script class="foobar" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
            
            <script class="foobar" type="text/html">
                <div class="place badge-carte">Place du Village<br>250m - 2mn à pied</div>
                <div class="telecabine badge-carte">Télécabine du Chamois<br>250m - 2mn à pied</div>
                <div class="situation badge-carte"><img src="https://domain.tld/assets/frontOffice/kneiss/template-assets/assets/dist/img/08ecd8a.png" alt=""></div>
            </script>
        </body>
        </html>
        ';

        $dom = new HtmlDomParser();

        $html = \str_replace(["\r\n", "\r", "\n"], "\n", (string) $dom->load($html));
        $expected = \str_replace(["\r\n", "\r", "\n"], "\n", $expected);

        static::assertSame(\trim($expected), \trim($html));
    }

    public function testHtmlEmbeddedInJavaScript()
    {
        $html = '
        <!doctype html>
        <html lang="fr">
        <head>
            <title>Test</title>
        </head>
        <body>
            A Body
        
            <script id="elements-image-1">
              var strJS = "<strong>foobar<\/strong>";
            </script>
        </body>
        </html>
        ';

        $expected = '
        <!DOCTYPE html>' . "\n" . '<html lang="fr">
        <head>
            <title>Test</title>
        </head>
        <body>
            A Body
        
            <script id="elements-image-1">
              var strJS = "<strong>foobar<\/strong>";
            </script>
        </body>
        </html>';

        $dom = new HtmlDomParser();

        $html = \str_replace(["\r\n", "\r", "\n"], "\n", (string) $dom->load($html));
        $expected = \str_replace(["\r\n", "\r", "\n"], "\n", $expected);

        static::assertSame(\trim($expected), \trim($html));

    }

    public function testBeforeClosingTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="stream-container "  > <div class="stream-item js-new-items-bar-container"> </div> <div class="stream">');
        static::assertSame('<div class="stream-container "> <div class="stream-item js-new-items-bar-container"> </div> <div class="stream"></div>' . "\n" . '</div>', (string) $dom);
    }

    public function testCodeTag()
    {
        $dom = new HtmlDomParser();
        $dom->load('<strong>hello</strong><code class="language-php">$foo = "bar";</code>');
        static::assertSame('<strong>hello</strong><code class="language-php">$foo = "bar";</code>', (string) $dom);
    }

    public function testDeleteNodeOuterHtml()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        $a = $dom->find('a');
        $a[0]->outerHtml = '';
        unset($a);
        static::assertSame('<div class="all"><p>Hey bro, <br> :)</p></div>', (string) $dom);
    }

    public function testDeleteNodeInnerHtml()
    {
        $dom = new HtmlDomParser();
        $dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
        $a = $dom->find('div.all');
        $a[0]->innerHtml = '';
        unset($a);
        static::assertSame('<div class="all"></div>', (string) $dom);
    }

    public function testScriptInCommentHtml()
    {
        // --- via load()

        $dom = new HtmlDomParser();
        $dom->load(
            '
      <script class="script_1" type="text/javascript">someCode</script>
      <!-- <script class="script_2" type="text/javascript">someCode</script> -->
    '
        );
        $script = $dom->find('script');
        static::assertSame('<script class="script_1" type="text/javascript">someCode</script>', (string) $script);

        // --- via "str_get_html()"

        $dom = HtmlDomParser::str_get_html(
            '
      <script class="script_1" type="text/javascript">someCode</script>
      <!-- <script class="script_2" type="text/javascript">someCode</script> -->
    '
        );
        $script = $dom->find('script');
        static::assertSame('<script class="script_1" type="text/javascript">someCode</script>', (string) $script);
    }

    public function testHtmlAndJavaScriptMix()
    {
        $htmlAndJs = '<p>Text 1</p><script>$(".second-column-mobile-inner").wrapAll("<div class=\'collapse\' id=\'second-column\'></div>");</script><p>Text 2</p>';

        $dom = HtmlDomParser::str_get_html($htmlAndJs);
        $script = $dom->find('script');
        static::assertSame('<script>$(".second-column-mobile-inner").wrapAll("<div class=\'collapse\' id=\'second-column\'><\/div>");</script>', (string) $script);
    }

    public function testSpecialCharsAndPlaintext()
    {
        $file = __DIR__ . '/fixtures/test_page_plaintext.html';
        $dom = new HtmlDomParser();
        $dom->loadHtmlFile($file);

        $review_content = $dom->find('.review-content p');
        static::assertInstanceOf(SimpleHtmlDomNodeInterface::class, $review_content);

        $allReviews = '';
        foreach ($review_content as $review) {
            $allReviews .= $review->plaintext . "\n";
        }
        static::assertTrue(\strlen($allReviews) > 0);
        static::assertContains('It&#39;s obvious having', $allReviews);
        static::assertContains('2006 Volvo into Dave&#39;s due', $allReviews);
    }
}
