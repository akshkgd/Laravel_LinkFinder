LinkFinder
==========

In a plain text document the LinkFinder searches for URLs and email addresses and makes them clickable, in a HTML document searches for missing links and makes them clickable too.

Usage
-----

    $text = '
     Welcome at www.example.com!
     Contact us on info@example.com.
    ';
    
    $lf = new LinkFinder();
    echo $lf->process($text);
    
    // ... this prints out
    //  Welcome at <a href="http://www.example.com/">www.example.com</a>!
    //  Contact us on <a href="mailto:info@example.com">info@example.com</a>.

Extra attributes for ```<a>``` and ```<a href="mailto:...">``` elements can be specified in options:

    $lf = new LinkFinder([
      "attrs" => ["class" => "external-link", "target" => "_blank", "rel" => "nofollow"],
      "mailto_attrs" => ["class" => "external-email"]
    ]);
    echo $lf->process($text);
    
    // ... this prints out
    //  Welcome at <a class="external-link" href="http://www.example.com/" target="_blank" rel="nofollow">www.example.com</a>!
    //  Contact us on <a class="external-email" href="mailto:info@example.com">info@example.com</a>.


Escaping of HTML entities is enabled by default:

    $text = '
      Find more at
      <http://www.ourstore.com/>
    ';
    echo $lf->process($text);
    // Find more at
    // &lt;<a href="http://www.ourstore.com/">http://www.ourstore.com/</a>&gt;

Creating missing links on URLs or emails in a HTML document:

    $html_document = '
      <p>
        Visit <a href="http://www.codekaro.in/">Ashish Shukla</a> or Codekaro.in
      </p>
    ';
    $lf = new LinkFinder();
    echo $lf->processHtml($html_document);
    // <p>
    //   Visit <a href="http://www.ckrumlov.info/">Cesky Krumlov</a> or <a href="http://Prague.eu">Prague.eu</a>.
    // </p>

Method $lf->processHtml() is actually an alias for $lf->process($html_document,["escape_html_entities" => false]).

In case of processing a HTML text, the LinkFinder doesn't create links in headlines (&lt;h1&gt;, &lt;h2&gt;, ...) by default. It can be overridden by the option avoid_headlines:

    echo $lf->processHtml($html_document,["avoid_headlines" => false]);

    // or

    $lf = new LinkFinder(["avoid_headlines" => false]);
    echo $lf->processHtml($html_document);


