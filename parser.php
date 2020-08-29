<?PHP
  // namespace Chirp;

  // Original PHP code by Chirp Internet: www.chirp.com.au
  // Please acknowledge use of this code by including this header.
  
  class RSSParser
  {
    // keeps track of current and preceding elements
    var $tags = [];

    // array containing all feed data
    var $output = [];

    // return value for display functions
    var $retval = "";

    var $errorlevel = 0;

    // constructor for new object
    public function __construct($file)
    {
      $errorlevel = error_reporting();
      error_reporting($errorlevel & ~E_NOTICE);

      // instantiate xml-parser and assign event handlers
      $xml_parser = xml_parser_create("");
      xml_set_object($xml_parser, $this);
      xml_set_element_handler($xml_parser, "startElement", "endElement");
      xml_set_character_data_handler($xml_parser, "parseData");

      $curl_opts = [
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_COOKIEFILE => "/tmp/newspapers.tmp"
      ];

      // open file for reading and send data to xml-parser
      $data = preg_match("/^http/", $file) ? http_get_contents($file, $curl_opts) : file_get_contents($file);
      xml_parse($xml_parser, $data) or die(
        sprintf(get_class() . ": Error <b>%s</b> at line <b>%d</b><br>",
        xml_error_string(xml_get_error_code($xml_parser)),
        xml_get_current_line_number($xml_parser))
      );

      // dismiss xml parser
      xml_parser_free($xml_parser);

      error_reporting($errorlevel);
    }

    private function startElement($parser, $tagname, $attrs = [])
    {
      // RSS 2.0 - ENCLOSURE
      if($tagname == "ENCLOSURE" && $attrs) {
        $this->startElement($parser, "ENCLOSURE");
        foreach($attrs as $attr => $attrval) {
          $this->startElement($parser, $attr);
          $this->parseData($parser, $attrval);
          $this->endElement($parser, $attr);
        }
        $this->endElement($parser, "ENCLOSURE");
      }

      // Yahoo! Media RSS - images
      if($tagname == "MEDIA:CONTENT" && $attrs['URL'] && $attrs['MEDIUM'] == 'image') {
        $this->startElement($parser, "IMAGE");
        $this->parseData($parser, $attrs['URL']);
        $this->endElement($parser, "IMAGE");
      }

      // check if this element can contain others - list may be edited
      if(preg_match("/^(RDF|RSS|CHANNEL|IMAGE|ITEM)/", $tagname)) {
        if($this->tags) {
          $depth = count($this->tags);
          if(is_array($tmp = end($this->tags))) {
            list($parent, $num) = each($tmp);
            if($parent) {
              $this->tags[$depth-1][$parent][$tagname]++;
            }
          }
        }
        array_push($this->tags, [$tagname => []]);
      } else {
        if(!preg_match("/^(A|B|I)$/", $tagname)) {
          // add tag to tags array
          array_push($this->tags, $tagname);
        }
      }
    }

    private function endElement($parser, $tagname)
    {
      if(!preg_match("/^(A|B|I)$/", $tagname)) {
        // remove tag from tags array
        array_pop($this->tags);
      }
    }

    private function parseData($parser, $data)
    {
      // return if data contains no text
      if(!trim($data)) return;

      $evalcode = "\$this->output";
      foreach($this->tags as $tag) {
        if(is_array($tag)) {
          list($tagname, $indexes) = each($tag);
          $evalcode .= "[\"$tagname\"]";
          if(${$tagname}) {
            $evalcode .= "[" . (${$tagname} - 1) . "]";
          }
          if($indexes) {
            extract($indexes);
          }
        } else {
          if(preg_match("/^([A-Z]+):([A-Z]+)$/", $tag, $matches)) {
            $evalcode .= "[\"$matches[1]\"][\"$matches[2]\"]";
          } else {
            $evalcode .= "[\"$tag\"]";
          }
        }
      }
      eval("$evalcode = $evalcode . '" . addslashes($data) . "';");
    }

    // display a single channel as HTML
    private function display_channel($data, $limit)
    {
      extract($data);
      if(isset($IMAGE) && $IMAGE) {
        // display channel image(s)
        foreach($IMAGE as $image) {
          $this->display_image($image);
        }
      }
      if($TITLE) {
        // display channel information
        $this->retval .= "<h1>";
        if($LINK) {
          $this->retval .= "<a href=\"$LINK\" target=\"_blank\">";
        }
        $this->retval .= stripslashes($TITLE);
        if($LINK) {
          $this->retval .= "</a>";
        }
        $this->retval .= "</h1>\n";
        if(isset($DESCRIPTION) && $DESCRIPTION) {
          $this->retval .= "<p>$DESCRIPTION</p>\n\n";
        }
        $tmp = [];
        if(isset($PUBDATE) && $PUBDATE) {
          $tmp[] = "<small>Published: $PUBDATE</small>";
        }
        if(isset($COPYRIGHT) && $COPYRIGHT) {
          $tmp[] = "<small>Copyright: $COPYRIGHT</small>";
        }
        if($tmp) {
          $this->retval .= "<p>" . implode("<br>\n", $tmp) . "</p>\n\n";
        }
        unset($tmp);
        $this->retval .= "<div class=\"divider\"><!-- --></div>\n\n";
      }
      if(isset($ITEM) && $ITEM) {
        // display channel item(s)
        foreach($ITEM as $item) {
          $this->display_item($item, "CHANNEL");
          if(is_int($limit) && --$limit <= 0) break;
        }
      }
    }

    // display a single image as HTML
    private function display_image($data, $parent = "")
    {
      extract($data);
      if(!$URL) return;

      $this->retval .= "<p>";
      if($LINK) {
        $this->retval .= "<a href=\"$LINK\" target=\"_blank\">";
      }
      $this->retval .= "<img src=\"$URL\"";
      if(isset($WIDTH, $HEIGHT) && $WIDTH && $HEIGHT) {
        $this->retval .= " width=\"$WIDTH\" height=\"$HEIGHT\"";
      }
      $this->retval .= " border=\"0\" alt=\"$TITLE\">";
      if($LINK) {
        $this->retval .= "</a>";
      }
      $this->retval .= "</p>\n\n";
    }

    // display a single item as HTML
    private function display_item($data, $parent)
    {
      extract($data);
      if(!$TITLE) return;

      $this->retval .=  "<p><b>";
      if($LINK) {
        $this->retval .=  "<a href=\"$LINK\" target=\"_blank\">";
      }
      $this->retval .= stripslashes($TITLE);
      if($LINK) {
        $this->retval .= "</a>";
      }
      $this->retval .=  "</b>";
      if(!isset($PUBDATE) && isset($DC['DATE']) && $DC['DATE']) {
        $PUBDATE = $DC['DATE'];
      }
      if(isset($PUBDATE) && $PUBDATE) {
        $this->retval .= " <small>($PUBDATE)</small>";
      }
      $this->retval .=  "</p>\n";

      // use feed-formatted HTML if provided
      if(isset($CONTENT['ENCODED']) && $CONTENT['ENCODED']) {
        $this->retval .= "<p>" . stripslashes($CONTENT['ENCODED']) . "</p>\n";
      } elseif(isset($DESCRIPTION) && $DESCRIPTION) {
        if(isset($IMAGE) && $IMAGE) {
          foreach($IMAGE as $IMG) {
            $this->retval .= "<img src=\"$IMG\" alt=\"\">\n";
          }
        }
        $this->retval .=  "<p>" . stripslashes($DESCRIPTION) . "</p>\n\n";
      }

      // RSS 2.0 - ENCLOSURE
      if(isset($ENCLOSURE) && $ENCLOSURE) {
        $this->retval .= "<p><small><b>Media:</b> <a href=\"{$ENCLOSURE['URL']}\">";
        $this->retval .= $ENCLOSURE['TYPE'];
        $this->retval .= "</a> ({$ENCLOSURE['LENGTH']} bytes)</small></p>\n\n";
      }

      if(isset($COMMENTS) && $COMMENTS) {
        $this->retval .= "<p style=\"text-align: right;\"><small>";
        $this->retval .= "<a href=\"$COMMENTS\">Comments</a>";
        $this->retval .= "</small></p>\n\n";
      }
    }

    private function fixEncoding(&$input, $key, $output_encoding)
    {
      if(!function_exists('mb_detect_encoding')) return $input;

      $encoding = mb_detect_encoding($input);
      switch($encoding)
      {
        case 'ASCII':
        case $output_encoding:
          break;

        case '':
          $input = mb_convert_encoding($input, $output_encoding);
          break;

        default:
          $input = mb_convert_encoding($input, $output_encoding, $encoding);

      }
    }

    // display entire feed as HTML
    public function getOutput($limit = FALSE, $output_encoding = 'UTF-8')
    {
      $this->retval = "";
      $start_tag = key($this->output);

      switch($start_tag)
      {
        case "RSS":
          // new format - channel contains all
          foreach($this->output[$start_tag]['CHANNEL'] as $channel) {
            $this->display_channel($channel, $limit);
          }
          break;

        case "RDF:RDF":
          // old format - channel and items are separate
          if(isset($this->output[$start_tag]['IMAGE'])) {
            foreach($this->output[$start_tag]['IMAGE'] as $image) {
              $this->display_image($image);
            }
          }
          foreach($this->output[$start_tag]['CHANNEL'] as $channel) {
            $this->display_channel($channel, $limit);
          }
          foreach($this->output[$start_tag]['ITEM'] as $item) {
            $this->display_item($item, $start_tag);
          }
          break;

        case "HTML":
          die("Error: cannot parse HTML document as RSS");

        default:
          die("Error: unrecognized start tag '$start_tag' in getOutput()");

      }

      if($this->retval && is_array($this->retval)) {
        array_walk_recursive($this->retval, [$this, 'fixEncoding'], $output_encoding);
      }
      return $this->retval;
    }

    // return raw data as array
    public function getRawOutput($output_encoding = 'UTF-8')
    {
      array_walk_recursive($this->output, [$this, 'fixEncoding'], $output_encoding);
      return $this->output;
    }
  }
 
    

  class AtomParser
  {
    // keeps track of current and preceding elements
    var $tags = array();

    // array containing all feed data
    var $output = array();

    // return value for display functions
    var $retval = "";

    var $errorlevel = 0;

    var $encoding = array();

    // constructor for new object
    function __construct($file)
    {
      $errorlevel = error_reporting();
      error_reporting($errorlevel & ~E_NOTICE);

      // instantiate xml-parser and assign event handlers
      $xml_parser = xml_parser_create("");
      xml_set_object($xml_parser, $this);
      xml_set_element_handler($xml_parser, "startElement", "endElement");
      xml_set_character_data_handler($xml_parser, "parseData");

      // open file for reading and send data to xml-parser
      $data = preg_match("/^http/", $file) ? http_get_contents($file) : file_get_contents($file);
      xml_parse($xml_parser, $data) or die(
        sprintf("myAtomParser: Error <b>%s</b> at line <b>%d</b><br>",
        xml_error_string(xml_get_error_code($xml_parser)),
        xml_get_current_line_number($xml_parser))
      );

      // dismiss xml parser
      xml_parser_free($xml_parser);

      error_reporting($errorlevel);
    }

    function startElement($parser, $tagname, $attrs=array())
    {
      if($this->encoding) {
        // content is encoded - so keep elements intact
        $tmpdata = "<$tagname";
        if($attrs) foreach($attrs as $key => $val) $tmpdata .= " $key=\"$val\"";
        $tmpdata .= ">";
        $this->parseData($parser, $tmpdata);
      } else {
        if($attrs['HREF'] && $attrs['REL'] && $attrs['REL'] == 'alternate') {
          $this->startElement($parser, 'LINK', array());
          $this->parseData($parser, $attrs['HREF']);
          $this->endElement($parser, 'LINK');
        }
        if($attrs['TYPE']) $this->encoding[$tagname] = $attrs['TYPE'];

        // check if this element can contain others - list may be edited
        if(preg_match("/^(FEED|ENTRY)$/", $tagname)) {
          if($this->tags) {
            $depth = count($this->tags);
            $tmp = end($this->tags);
            list($parent, $num) = each($tmp);
            if($parent) $this->tags[$depth-1][$parent][$tagname]++;
          }
          array_push($this->tags, array($tagname => array()));
        } else {
          // add tag to tags array
          array_push($this->tags, $tagname);
        }
      }
    }

    function endElement($parser, $tagname)
    {
      // remove tag from tags array
      if($this->encoding) {
        if(isset($this->encoding[$tagname])) {
          unset($this->encoding[$tagname]);
          array_pop($this->tags);
        } else {
          if(!preg_match("/(BR|IMG)/", $tagname)) $this->parseData($parser, "</$tagname>");
        }
      } else {
        array_pop($this->tags);
      }
    }

    function parseData($parser, $data)
    {
      // return if data contains no text
      if(!trim($data)) return;

      $evalcode = "\$this->output";
      foreach($this->tags as $tag) {
        if(is_array($tag)) {
          list($tagname, $indexes) = each($tag);
          $evalcode .= "[\"$tagname\"]";
          if(${$tagname}) $evalcode .= "[" . (${$tagname} - 1) . "]";
          if($indexes) extract($indexes);
        } else {
          if(preg_match("/^([A-Z]+):([A-Z]+)$/", $tag, $matches)) {
            $evalcode .= "[\"$matches[1]\"][\"$matches[2]\"]";
          } else {
            $evalcode .= "[\"$tag\"]";
          }
        }
      }

      if(isset($this->encoding['CONTENT']) && $this->encoding['CONTENT'] == "text/plain") {
        $data = "<pre>$data</pre>";
      }

      eval("$evalcode .= '" . addslashes($data) . "';");
    }

    // display a single feed as HTML
    function display_feed($data, $limit)
    {
      extract($data);
      if($TITLE) {
        // display feed information
        $this->retval .= "<h1>";
        if(isset($LINK) && $LINK) {
          $this->retval .= "<a href=\"$LINK\" target=\"_blank\">";
        }
        $this->retval .= stripslashes($TITLE);
        if(isset($LINK) && $LINK) {
          $this->retval .= "</a>";
        }
        $this->retval .= "</h1>\n";
        if(isset($TAGLINE) && $TAGLINE) {
          $this->retval .= "<P>" . stripslashes($TAGLINE) . "</P>\n\n";
        }
        $this->retval .= "<div class=\"divider\"><!-- --></div>\n\n";
      }
      if($ENTRY) {
        // display feed entry(s)
        foreach($ENTRY as $item) {
          $this->display_entry($item, "FEED");
          if(is_int($limit) && --$limit <= 0) break;
        }
      }
    }

    // display a single entry as HTML
    function display_entry($data, $parent)
    {
      extract($data);
      if(!$TITLE) return;

      $this->retval .=  "<p><b>";
      if($LINK) $this->retval .=  "<a href=\"$LINK\" target=\"_blank\">";
      $this->retval .= stripslashes($TITLE);
      if($LINK) $this->retval .= "</a>";
      $this->retval .=  "</b>";
      if(isset($ISSUED) && $ISSUED) {
        $this->retval .= " <small>($ISSUED)</small>";
      }
      $this->retval .=  "</p>\n";

      if(isset($AUTHOR) && $AUTHOR) {
        $this->retval .=  "<P><b>Author:</b> " . stripslashes($AUTHOR['NAME']) . "</P>\n\n";
      }
      if($CONTENT) {
        $this->retval .=  "<P>" . stripslashes($CONTENT) . "</P>\n\n";
      } elseif($SUMMARY) {
        $this->retval .=  "<P>" . stripslashes($SUMMARY) . "</P>\n\n";
      }
    }

    function fixEncoding(&$input, $key, $output_encoding)
    {
      if(!function_exists('mb_detect_encoding')) return $input;

      $encoding = mb_detect_encoding($input);
      switch($encoding)
      {
        case 'ASCII':
        case $output_encoding:
          break;
        case '':
          $input = mb_convert_encoding($input, $output_encoding);
          break;
        default:
          $input = mb_convert_encoding($input, $output_encoding, $encoding);
      }
    }

    // display entire feed as HTML
    function getOutput($limit=false, $output_encoding='UTF-8')
    {
      $this->retval = "";
      $start_tag = key($this->output);

      switch($start_tag)
      {
        case "FEED":
          foreach($this->output as $feed) $this->display_feed($feed, $limit);
          break;
        default:
          die("Error: unrecognized start tag '$start_tag' in getOutput()");
      }

      if($this->retval && is_array($this->retval)) {
        array_walk_recursive($this->retval, 'myAtomParser::fixEncoding', $output_encoding);
      }
      return $this->retval;
    }

    // return raw data as array
    function getRawOutput($output_encoding='UTF-8')
    {
      array_walk_recursive($this->output, 'myAtomParser::fixEncoding', $output_encoding);
      return $this->output;
    }
  }
?>
