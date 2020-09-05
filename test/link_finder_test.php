<?php
use PHPUnit\Framework\TestCase;

class LinkFinderTest extends TestCase{

	function testBasicUsage(){
		$lfinder = new LinkFinder();

		// a basic example
		$src = 'Lorem www.ipsum.com. dolor@sit.net. Thank you';
		$this->assertEquals(
			'Lorem <a href="http://www.ipsum.com">www.ipsum.com</a>. <a href="mailto:dolor@sit.net">dolor@sit.net</a>. Thank you',
			$lfinder->process($src)
		);

		//
		$src = 'Image: <img src="http://example.com/logo.gif" />, Url: www.ipsum.com';
		$this->assertEquals(
			'Image: <img src="http://example.com/logo.gif" />, Url: <a href="http://www.ipsum.com">www.ipsum.com</a>',
			$lfinder->process($src,array("escape_html_entities" => false))
		);

		// auto escaping of HTML entities
		$src = 'Lorem www.ipsum.com <http://www.ipsum.com/>.
			Dolor: dolor@sit.new <dolor@sit.net>. Thank you';
		$this->assertEquals(
			'Lorem <a href="http://www.ipsum.com">www.ipsum.com</a> &lt;<a href="http://www.ipsum.com/">http://www.ipsum.com/</a>&gt;.
			Dolor: <a href="mailto:dolor@sit.new">dolor@sit.new</a> &lt;<a href="mailto:dolor@sit.net">dolor@sit.net</a>&gt;. Thank you',
			$lfinder->process($src)
		);

		// disabling auto escaping may produce invalid markup
		$src = 'Lorem www.ipsum.com <http://www.ipsum.com/>.
			Dolor: dolor@sit.new <dolor@sit.net>. Thank you';
		$this->assertEquals(
			'Lorem <a href="http://www.ipsum.com">www.ipsum.com</a> <<a href="http://www.ipsum.com/">http://www.ipsum.com/</a>>.
			Dolor: <a href="mailto:dolor@sit.new">dolor@sit.new</a> <<a href="mailto:dolor@sit.net">dolor@sit.net</a>>. Thank you',
			$lfinder->process($src,array("escape_html_entities" => false))
		);

		// a git repository must not be interpreted as an email
		$src = 'Source is located at git@github.com:yarri/LinkFinder.git';
		$this->assertEquals('Source is located at git@github.com:yarri/LinkFinder.git',$lfinder->process($src));

		// an example from the README.md
		$src = 'Find more at www.ourstore.com <http://www.ourstore.com/>';
		$this->assertEquals(
			'Find more at <a href="http://www.ourstore.com">www.ourstore.com</a> &lt;<a href="http://www.ourstore.com/">http://www.ourstore.com/</a>&gt;',
			$lfinder->process($src)
		);

		// source text contains a real link
		$src = 'Find more at www.ourstore.com or click <a href="http://www.ourstore.com/contact">here</a> to contact us.';
		$result = 'Find more at <a href="http://www.ourstore.com">www.ourstore.com</a> or click <a href="http://www.ourstore.com/contact">here</a> to contact us.';
		$this->assertEquals($result,$lfinder->process($src,array("escape_html_entities" => false)));
		$this->assertEquals($result,$lfinder->processHtml($src));

		// in source there is already a correct HTML link
		$src = '<p>Contact as on <a href="http://www.earth.net/">www.earth.net</a></p>';
		$result = '<p>Contact as on <a href="http://www.earth.net/">www.earth.net</a></p>';
		$this->assertEquals($result,$lfinder->process($src,array("escape_html_entities" => false)));
		$this->assertEquals($result,$lfinder->processHtml($src));

		// a tag immediately after an URL
		$src = '<p>Contact as on www.earth.net<br />
or we@earth.net</p>';
		$this->assertEquals('<p>Contact as on <a href="http://www.earth.net">www.earth.net</a><br />
or <a href="mailto:we@earth.net">we@earth.net</a></p>',$lfinder->process($src,array("escape_html_entities" => false)));

		$tr_table = array(
			'url: www.domain.com, www.ourstore.com' => 'url: <a href="http://www.domain.com">www.domain.com</a>, <a href="http://www.ourstore.com">www.ourstore.com</a>',
			'url: www.domain.com; www.ourstore.com' => 'url: <a href="http://www.domain.com">www.domain.com</a>; <a href="http://www.ourstore.com">www.ourstore.com</a>',
			'just visit www.ourstore.com...' => 'just visit <a href="http://www.ourstore.com">www.ourstore.com</a>...',
		);
		foreach($tr_table as $src => $expected){
			$this->assertEquals($expected,$lfinder->process($src),"source: $src");
		}

		// URLs in quotes
		// Steam sends strange formatted text in email address verification messages
		$src = 'Sometimes in emails in text/plain parts not well formatted text occurs: <a href="http://www.click.me/now/">click here</a>';
		$this->assertEquals('Sometimes in emails in text/plain parts not well formatted text occurs: &lt;a href=&quot;<a href="http://www.click.me/now/">http://www.click.me/now/</a>&quot;&gt;click here&lt;/a&gt;',$lfinder->process($src));
		//
		$src = "Sometimes in emails in text/plain parts not well formatted text occurs: <a href='http://www.click.me/now/'>click here</a>";
		$this->assertEquals('Sometimes in emails in text/plain parts not well formatted text occurs: &lt;a href=\'<a href="http://www.click.me/now/">http://www.click.me/now/</a>\'&gt;click here&lt;/a&gt;',$lfinder->process($src));
		//
		$src = 'Link: "http://www.example.org/"';
		$this->assertEquals('Link: "<a href="http://www.example.org/">http://www.example.org/</a>"',$lfinder->process($src,array("escape_html_entities" => false)));

		// URL with username and password
		$src = 'Development preview is at http://preview:project123@project.preview.example.org/';
		$this->assertEquals('Development preview is at <a href="http://preview:project123@project.preview.example.org/">http://preview:project123@project.preview.example.org/</a>',$lfinder->process($src));

 		// invalid utf-8 char
		$invalid_char = chr(200);
		$src = "Lorem$invalid_char www.ipsum.com. dolor@sit.net. Thank you";
		$this->assertEquals(
			"Lorem$invalid_char www.ipsum.com. dolor@sit.net. Thank you",
			$lfinder->process($src)
		);
		//
		$invalid_char = chr(200);
		$src = "Lorem$invalid_char <www.ipsum.com>. dolor@sit.net. Thank you";
		$this->assertEquals(
			"Lorem$invalid_char &lt;www.ipsum.com&gt;. dolor@sit.net. Thank you",
			$lfinder->process($src)
		);

	}

	function testOptions(){
		$src = '<em>Lorem</em> www.ipsum.com. dolor@sit.net. Thank you';
		$lfinder = new LinkFinder(array(
			"attrs" => array(
				"class" => "link",
				"target" => "_blank",
			),
			"mailto_attrs" => array(
				"class" => "email",
			),
			"escape_html_entities" => false,
		));

		$this->assertEquals(
			'<em>Lorem</em> <a class="link" href="http://www.ipsum.com" target="_blank">www.ipsum.com</a>. <a class="email" href="mailto:dolor@sit.net">dolor@sit.net</a>. Thank you',
			$lfinder->process($src)
		);

		$this->assertEquals(
			'<em>Lorem</em> <a class="external-link" href="http://www.ipsum.com">www.ipsum.com</a>. <a class="email" href="mailto:dolor@sit.net">dolor@sit.net</a>. Thank you',
			$lfinder->process($src,array("attrs" => array("class" => "external-link")))
		);

		$this->assertEquals(
			'&lt;em&gt;Lorem&lt;/em&gt; <a class="article-link" href="http://www.ipsum.com">www.ipsum.com</a>. <a class="article-email" href="mailto:dolor@sit.net">dolor@sit.net</a>. Thank you',
			$lfinder->process($src,array("attrs" => array("class" => "article-link"), "mailto_attrs" => array("class" =>  "article-email"), "escape_html_entities" => true))
		);

		$this->assertEquals(
			'<em>Lorem</em> <a class="link" href="http://www.ipsum.com" target="_blank">www.ipsum.com</a>. <a class="email" href="mailto:dolor@sit.net">dolor@sit.net</a>. Thank you',
			$lfinder->process($src)
		);
	}

	function test_avoid_headlines(){
		$src = '<h1>WWW.PROJECT.COM</h1><p>Welcome at www.project.com!</p>';
		$lfinder = new LinkFinder();

		// the default value is to avoid headlines
		$this->assertEquals('<h1>WWW.PROJECT.COM</h1><p>Welcome at <a href="http://www.project.com">www.project.com</a>!</p>',$lfinder->processHtml($src));

		//
		$this->assertEquals('<h1>WWW.PROJECT.COM</h1><p>Welcome at <a href="http://www.project.com">www.project.com</a>!</p>',$lfinder->processHtml($src,array("avoid_headlines" => true)));
		$this->assertEquals('<h1><a href="http://WWW.PROJECT.COM">WWW.PROJECT.COM</a></h1><p>Welcome at <a href="http://www.project.com">www.project.com</a>!</p>',$lfinder->processHtml($src,array("avoid_headlines" => false)));

		// setting default value into the constructor
		$lfinder = new LinkFinder(array("avoid_headlines" => false));
		$this->assertEquals('<h1><a href="http://WWW.PROJECT.COM">WWW.PROJECT.COM</a></h1><p>Welcome at <a href="http://www.project.com">www.project.com</a>!</p>',$lfinder->processHtml($src));
		$this->assertEquals('<h1>WWW.PROJECT.COM</h1><p>Welcome at <a href="http://www.project.com">www.project.com</a>!</p>',$lfinder->processHtml($src,array("avoid_headlines" => true)));

		// avoid_headlines has no effect when processing a plain text
		$lfinder = new LinkFinder();
		$this->assertEquals('&lt;h1&gt;<a href="http://WWW.PROJECT.COM">WWW.PROJECT.COM</a>&lt;/h1&gt;&lt;p&gt;Welcome at <a href="http://www.project.com">www.project.com</a>!&lt;/p&gt;',$lfinder->process($src));
		$this->assertEquals('&lt;h1&gt;<a href="http://WWW.PROJECT.COM">WWW.PROJECT.COM</a>&lt;/h1&gt;&lt;p&gt;Welcome at <a href="http://www.project.com">www.project.com</a>!&lt;/p&gt;',$lfinder->process($src,array("avoid_headlines" => true)));
		$this->assertEquals('&lt;h1&gt;<a href="http://WWW.PROJECT.COM">WWW.PROJECT.COM</a>&lt;/h1&gt;&lt;p&gt;Welcome at <a href="http://www.project.com">www.project.com</a>!&lt;/p&gt;',$lfinder->process($src,array("avoid_headlines" => false)));
	}

	function testLegacyUsage(){
		$src = '<em>Lorem</em> www.ipsum.com. dolor@sit.net. Thank you';

		$lfinder = new LinkFinder(array(
			"open_links_in_new_windows" => true,
			"escape_html_entities" => false,

			"link_template" => '<a href="%href%"%class%%target%>%url%</a>',
			"mailto_template" => '<a href="mailto:%mailto%"%class%>%address%</a>',

			"link_class" => "link",
			"mailto_class" => "email",
		));
		$this->assertEquals(
			'<em>Lorem</em> <a href="http://www.ipsum.com" class="link" target="_blank">www.ipsum.com</a>. <a href="mailto:dolor@sit.net" class="email">dolor@sit.net</a>. Thank you',
			$lfinder->process($src)
		);

		$lfinder->setToOpenLinkInNewWindow(false);
		$lfinder->setLinkClass("external-link");

		$this->assertEquals(
			'<em>Lorem</em> <a href="http://www.ipsum.com" class="external-link">www.ipsum.com</a>. <a href="mailto:dolor@sit.net" class="email">dolor@sit.net</a>. Thank you',
			$lfinder->process($src)
		);

		$this->assertEquals(
			'&lt;em&gt;Lorem&lt;/em&gt; <a href="http://www.ipsum.com" class="article-link">www.ipsum.com</a>. <a href="mailto:dolor@sit.net" class="article-email">dolor@sit.net</a>. Thank you',
			$lfinder->process($src,array("link_class" => "article-link", "mailto_class" => "article-email", "escape_html_entities" => true))
		);

		$this->assertEquals(
			'<em>Lorem</em> <a href="http://www.ipsum.com" class="external-link">www.ipsum.com</a>. <a href="mailto:dolor@sit.net" class="email">dolor@sit.net</a>. Thank you',
			$lfinder->process($src)
		);

		$lfinder = new LinkFinder(array(
			"open_links_in_new_windows" => true,
			"escape_html_entities" => false,

			"attrs" => array("class" => "link"),
			"mailto_attrs" => array("class" => "email"),

			"link_template" => '<a href="%href%"%class%%target%>%url%</a>',
			"mailto_template" => '<a href="mailto:%mailto%"%class%>%address%</a>',
		));
		$this->assertEquals('<em>Lorem</em> <a href="http://www.ipsum.com" class="link" target="_blank">www.ipsum.com</a>. <a href="mailto:dolor@sit.net" class="email">dolor@sit.net</a>. Thank you',$lfinder->process($src));
	}

	function testLinksInBrackets(){
		$lfinder = new LinkFinder();
		$this->assertEquals('Example (<a href="http://example.com/">http://example.com/</a>)',$lfinder->process('Example (http://example.com/)'));
		$this->assertEquals('Square Brackets [<a href="http://example.com/">http://example.com/</a>]',$lfinder->process('Square Brackets [http://example.com/]'));
		$this->assertEquals('Square Brackets [<a href="http://example.com/">http://example.com/</a>]. Nice!',$lfinder->process('Square Brackets [http://example.com/]. Nice!'));
		$this->assertEquals('Braces {<a href="http://example.com/">http://example.com/</a>}',$lfinder->process('Braces {http://example.com/}'));
	}

	function testLinks(){
		$links = array(
			"http://www.ipsum.com/" => "http://www.ipsum.com/",
			"http://www.ipsum.com:81/" => "http://www.ipsum.com:81/",
			//
			"https://www.example.com/article.pl?id=123" => "https://www.example.com/article.pl?id=123",
			"https://www.example.com:81/article.pl?id=123" => "https://www.example.com:81/article.pl?id=123",
			//
			"www.ipsum.com" => "http://www.ipsum.com",
			"www.ipsum.com:81" => "http://www.ipsum.com:81",
			//
			"www.example.com/article.pl?id=123" => "http://www.example.com/article.pl?id=123",
			"www.example.com/article.pl?id=123&format=raw" => "http://www.example.com/article.pl?id=123&format=raw",
			"www.example.com/article.pl?id=123;format=raw" => "http://www.example.com/article.pl?id=123;format=raw",
			"www.www.example.intl" => "http://www.www.example.intl",

			"ftp://example.com/public/" => "ftp://example.com/public/",
			"ftp://example.com:1122/public/" => "ftp://example.com:1122/public/",

			"example.com" => "http://example.com",
			"subdomain.example.com" => "http://subdomain.example.com",

			"example.com/" => "http://example.com/",
			"example.com/page.html" => "http://example.com/page.html",

			"example.com:81" => "http://example.com:81",
			"example.com:81/" => "http://example.com:81/",
			"example.com:81/page.html" => "http://example.com:81/page.html",

			"subdomain.example.com" => "http://subdomain.example.com",

			"http://domain.com/var=[ID]" => "http://domain.com/var=[ID]",

			//"http://grooveshark.com/#!/album/AirMech/8457898" => "http://grooveshark.com/#!/album/AirMech/8457898", // TODO:
		);

		$templates = array(
			"%s",
			"Lorem %s Ipsum",
			"Lorem %s, Ipsum",
			"Lorem %s. Ipsum",
			"Lorem %s",
			"%s, Lorem",
			"%s,Lorem",
			"%s. Lorem",
			"Lorem: %s",
			"Lorem:%s",
			"Lorem %s!",
			"Brackets (%s)",
			"Brackets (%s), Nice!",
			"Brackets (%s); Nice!",
			"Brackets (%s). Nice!",
			"Angled Brackets <%s>",
			"Angled Brackets <%s>, Nice!",
			"Angled Brackets <%s>; Nice!",
			"Angled Brackets <%s>. Nice!",
			"Square Brackets [%s]",
			"Square Brackets [%s], Nice!",
			"Braces {%s}",
			"Braces {%s}, Nice!",
			"Braces {%s}; Nice!",
			"Braces {%s}. Nice!",
		);

		$lfinder = new LinkFinder();

		foreach($links as $link_src => $expected){
			$expected = str_replace('&','&amp;',$expected); // "www.example.com/article.pl?id=123&format=raw" => "www.example.com/article.pl?id=123&amp;format=raw"
			foreach($templates as $template){
				// LinkFinder::process()
				$_src = sprintf($template,$link_src);
				$out = $lfinder->process($_src);
				$this->assertEquals(true,!!preg_match('/<a href="([^"]+)">/',$out,$matches),"$_src is containing a link");
				$this->assertEquals($expected,$matches[1],"$_src is containing $expected");

				// LinkFinder::processHtml()
				$template = htmlspecialchars($template); // $template must be a valid HTML snippet
				$_src = sprintf($template,htmlspecialchars($link_src));
				$out = $lfinder->processHtml($_src);
				$this->assertEquals(true,!!preg_match('/<a href="([^"]+)">/',$out,$matches),"$_src is containing a link");
				$this->assertEquals($expected,$matches[1],"$_src is containing $expected");
			}
		}
	}

	function testNotLinks(){
		$not_links = array(
			"i like indian food.how about you.",
			"tlds are .com, .net, .org, etc.",
			"pattern is *.com",
			"pattern is -.com",
			"somehing like.xx",
			"DůmLátek.cz",
			"/var/www/app.com/index.html",
			"/var/www/www.app.com/index.html",
			'.example.com',
			'.www.example.com'
		);

		$lfinder = new LinkFinder();

		foreach($not_links as $str){
			$out = $lfinder->process($str);
			$this->assertEquals($str,$out,"\"$out\" should not contain a link");
		}
	}
}
