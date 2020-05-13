<?php


/**
 * Class Commons
 */

class Commons
{
    static function dump($msg){
        echo "<pre>";
        print_r($msg);
        echo "<pre>";
    }

    static function array_random($arr, $num = 1) {
        shuffle($arr);

        $r = array();
        for ($i = 0; $i < $num; $i++) {
            $r[] = $arr[$i];
        }
        return $num == 1 ? $r[0] : $r;
    }

    /**
     * Creates or retrieves the user session (available in $_SESSION[])
     *
     * This session is between this PHP layer and the web client (the remote
     * POL web services also sets a cookie when the user logs in, we store it in
     * this session).
     */
    static function getUserSession()
    {
        // Disable (unsecure) passing of session ids in URLS if cookies are disabled by the client.
        // Visitors should be warned that cookies are required. Unfortunately it's hard to detect
        // whether cookies are enabled and session_start() will just return an empty $_SESSION
        // which we will reinitalize, loosing any previous state, so a static warning message
        // should probably be displayed in the site Home page(s).
        ini_set("session.use_only_cookies", "1"); /////

        //session_save_path("../tmp"); // use this dir to persist session data

        session_start(); // create or retrieve session - $_SESSION will be empty if session just created
                         // OR cookies disabled by user.
    }

    /**
     * Helper fct to generate the JS to display a message in the status area.
     */
    static function setStatusMsg($msg, $color='black', $blink=false)
    {
        printf("<script type=\"text/javascript\">setStatusMsg('%s', '%s', '%s'); </script>",
               $msg, $color, ($blink ? 'true':'false'));
    }

    /**
     * Helper fct to generate the JS to clear the status area.
     */
    static function clearStatusMsg()
    {
        printf("<script type=\"text/javascript\">clearStatusMsg(); </script>");
    }


    /**
     * Returns the name of the current page (including common.lib.php).
     * This is the name of the PHP file without the suffix, eg 'planning', 'departs'...
     */
    static function getCurrentPageName()
    {
      return basename($_SERVER['PHP_SELF'], ".php");
    }

    //echo getCurrentPageName() . '<br/>';

    /**
     * Gets a http request parameter.
     *
     * If a GET or POST parameter with the given name exists, it is taken.
     * Otherwise use the default value provided.
     *
     * @param string $idParam The parameter to get
     * @param string $defaultValue Default value if none found
     * @return string The parameter value
     */
    static function getRequestParameter($idParam, $defaultValue=null)
    {
        // Determine value
        if (isset($_REQUEST[$idParam]))
            $value = Commons::getValueFromField($_REQUEST[$idParam]);
        else
            $value = $defaultValue;

        return $value;
    }

    /*******************************************************************
     *                  URL helpers
     *******************************************************************/
    /**
     * Rebuilds a HTTP URL from a parsed array produced by parse_url().
     * == Only host, path, query & fragment info is taken into account
          (no port#, user or password) ==
     */
    static function unparseUrl($parsedUrl)
    {
        $host = !empty($parsedUrl['host']) ? "{$parsedUrl['host']}" : "";
        $path = !empty($parsedUrl['path']) ? "{$parsedUrl['path']}" : "";
        $query = !empty($parsedUrl['query']) ? "?{$parsedUrl['query']}" : "";
        $fragment = !empty($parsedUrl['fragment']) ? "#{$parsedUrl['fragment']}" : "";
        return sprintf('http://%s%s%s%s', $host, $path, $query, $fragment);
    }

    /**
     *  Parses the given Url query and returns the args {argName =>argVal,...}]
     *  (inverse of unparseUrlQuery)
     */
    static function parseUrlQuery($query, $separator='&')
    {
        $args = array();
        $query = trim($query);
        if (!empty($query))
        {
            $argExprs = explode($separator, $query);
            foreach ($argExprs as $argExpr)
            {
                list($name, $value) = explode('=', $argExpr);
                $args[$name] = $value;
            }
        }

        //debugPrintf("args=%s\n", $args);
        return $args;
    }

    /**
     *  Returns the URL query string corresponding to the parsed array $args.
     * (inverse of parseUrlQuery)
     */
    static function unparseUrlQuery($args, $separator='&')
    {
        $argExprs = array();
        foreach ($args as $name => $value)
            $argExprs[] = $name . '=' . $value;     // uuencode ??
        return implode($separator, $argExprs);
    }

    /**
     *   Removes the given query arg from the given url query string if it exists.
     *   (see also removeArgFromUrl)
     */
    static function removeArgFromUrlQuery($urlQuery, $argName, $separator='&')
    {
        $args = Commons::parseUrlQuery($urlQuery, $separator);
        if (array_key_exists($argName, $args))
            unset($args[$argName]);
        return Commons::unparseUrlQuery($args);
    }

    /**
     *   Removes the given query arg from the given (absolute) url if it exists.
     *   $url can be a string or a parsed URL as an array returned by parse_url().
     *   (see also removeArgFromUrlQuery)
     */

    static function removeArgFromUrl($url, $argName, $separator='&')
    {
        $urlParsed = is_array($url) ? $url : parse_url($url);
        $urlParsed['query'] = Commons::removeArgFromUrlQuery($urlParsed['query'], $argName, $separator);
        return Commons::unparseUrl($urlParsed);
    }

    /**
     *   Adds the given query arg to the given (absolute) url if it exists.
     *   If the arg already exists, just change its value.
     *   $url can be a string or a parsed URL as an array returned by parse_url().
     */

    static function addArgToUrl($url, $argName, $value, $separator='&')
    {
        $urlParsed = is_array($url) ? $url : parse_url($url);
        if (!isset($urlParsed['query']))
            $urlParsed['query'] = '';

        $args = Commons::parseUrlQuery($urlParsed['query'], $separator);
        if (array_key_exists($argName, $args))
            unset($args[$argName]);
        $args[$argName] = $value;
        $urlParsed['query'] = Commons::unparseUrlQuery($args);
        return Commons::unparseUrl($urlParsed);
    }


    static function getAddressCoordinates($address, &$latitude, &$longitude)
    {

        $latitude = $longitude = INVALID_COORD;

        $params = urlencode("key=".GOOGLE_MAP_KEY."&address={$address}&sensor=true");
        // Google chokes on encoded & and =, so let's restore them!
        $url = "https://maps.googleapis.com/maps/api/geocode/json?{$params}";
        $url = str_replace(array('%26', '%3D'), array('&', '='), $url);
        //debugPrintf("url=%s\n", $url); ////

        /* Use cURL to query Google since file_get_contents is often disabled by some hosts (e.g. HostPC) */
        $out = file_get_contents($url);
        
        $content = json_decode($out);

        if ( count($content->results[0]) <= 0 )  // not found or cx problem
            return 0;
        
        $latitude = $content->results[0]->geometry->location->lat;
        $longitude = $content->results[0]->geometry->location->lng;
        
        return 1;
    }


    /*******************************************************************
     *                  Error & Trace management
     *******************************************************************/
    /**
     * Call this static function BEFORE using any other error fct !!
     */
    static function _initErrorMgt()
    {
        global $_errorCodeToName;

        error_reporting(ERROR_REPORTING_LEVEL);
        assert_options(ASSERT_ACTIVE, ASSERTS_ON);
        assert_options(ASSERT_CALLBACK, '_assertHandler'); // custom handler
        assert_options(ASSERT_BAIL, false);            // don't terminate exec
        assert_options(ASSERT_WARNING, false);         // don't issue warning on fail

        if (LOG_ERRORS)
        {
            $_logDir = sprintf('%s/log', ABS_ROOT_PATH);
            if (!file_exists($_logDir))
                mkdir($_logDir);
            define('LOG_FILE_NAME', sprintf('%s/log/cartels_%s.log', ABS_ROOT_PATH,
                                            strftime("%Y-%m-%d")));
        }

        $oldHandler = set_error_handler('_errorHandler');

        define('E_USER', E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);

        $_errorCodeToName = array (
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_NOTICE => 'NOTICE',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER INFO',
        );

        return $oldHandler;
    }

    /**
     * Outputs a message only if $cartels_config->debug is true.
     * @param $msg: The message to display; if it is an object or array, it is formatted using print_r.
     * @param $lf: nb of "<br />\n" to print after the message.
     */
    static function debugPrint($msg, $lf=0)
    {
        if (DEBUG)
        {
            if (is_null($msg))
                $msg = '&lt;NULL&gt;';
            else if (!is_scalar($msg))
                $msg = print_r($msg, true);
            printf("<span style=\"background-color:#EFFFF3\">%s</span>%s", nl2br($msg), str_repeat("<br />\n", $lf));
        }
    }

    /**
     * Outputs a formatted message only if $cartels_config->debug is true.
     * usage: debugPrintf(format, args...)  use only %s !!!!
     * Non scalar args (objects and arrays) are formatted to **strings** using print_r.
     */
    static function debugPrintf()
    {
        if (DEBUG)
        {
            $format = func_get_arg(0);
            $args = array();
            for ($i=1; $i < func_num_args(); $i++)
            {
                $arg = func_get_arg($i);
                if (is_null($arg))
                    $arg = '&lt;NULL&gt;';
                elseif (is_bool($arg))
                    $arg = ($arg ? 'true' : 'false');
                elseif (!is_scalar($arg))
                    $arg = print_r($arg, true);
                $args[] = $arg;
            }
            vprintf($format, $args);
        }
    }

    /**
     * Logs a (user) information message.
     */
    static function info($msg)
    {
        Commons::_storeCallerInfo();
        user_error($msg, E_USER_NOTICE);
    }

    /**
     * Logs a (user) warning message.
     */
    static function warning($msg)
    {
        _storeCallerInfo();
        user_error($msg, E_USER_WARNING);
    }

    /**
     * Raises a (user) error, but does not exit.
     */
    static function error($msg)
    {
        Commons::_storeCallerInfo();
        user_error($msg, E_USER_ERROR);
    }

    /**
     * Raises a (user) fatal error and exits inconditionally
     */
    static function fatal($msg)
    {
        Commons::_storeCallerInfo();
        user_error('Fatal: ' . $msg, E_USER_ERROR);
        if (DISPLAY_ERRORS)
            Commons::backtrace(); //
        exit(1);
    }

    /*
     * Retrieves caller's caller info and stores it in globals.
     */
    static function _storeCallerInfo()
    {
        global $_file, $_line;
        $trace = Commons::debug_backtrace();
        $caller = $trace[1]; // caller's caller
        $_file = $caller['file'];
        $_line = $caller['line'];
    }


    /**
     * Custom error handler for the site.
     * NB: log times are server times.
     */
    static function _errorHandler($errLevel, $msg, $file, $line)
    {
        //echo '_errorHandler CALLED!'; ///////////
        global $page, $_errorCodeToName;
        global $_file, $_line;

        // For user "errors" (assuming raised via the info, warning, error & fatal error static functions)
        // the file & line have been calculated and stored in globals :
        if ($errLevel & E_USER)
        {
            $file = $_file;
            $line = $_line;
        }

        $severity = $_errorCodeToName[$errLevel];

        if (LOG_ERRORS)
        {
            $logMsg = sprintf("%s - %s: %s, in %s, line %d (client=%s, page=%s)\n",
                              strftime("%Y-%m-%d %H:%M:%S"), $severity, $msg, $file, $line,
                              getClientIP(true), $page->url);
            error_log($logMsg, 3, LOG_FILE_NAME);
        }

        if (DISPLAY_ERRORS)
        {
            $color = ($errLevel != E_USER_NOTICE ? 'red' : 'green');
            $screenMsg = sprintf("<b>%s</b>: <span style=\"color:%s; \">%s</span>" .
                           "<span style=\"font-size:smaller;\">, in %s on line %d</span><br />\n",
                           ucwords(strtolower($severity)), $color, $msg, $file, $line);
            echo $screenMsg;
        }

        // Considers severe PHP (ie not USER) errors as fatal except if we are live:
        if ($errLevel == E_ERROR  && LIVE)
        {
            echo backtrace(); //
            exit(1);
        }
    }

    /**
     * Custom assert handler for the site.
     */
    static function _assertHandler($file, $line, $assertion)
    {
        $msg = 'Assertion Failed: ' . $assertion;
        _errorHandler(E_ERROR, $msg, $file, $line);
    }

    /*
     * Formats the call stack.
     */
    static function backtrace()
    {
        $output = "<div style='text-align:left; font-family: Tahoma,monospace; font-size:small;'>\n";
        $output .= "<b>Backtrace (most recent call first):</b><br />\n";
        $output .= "<div style='margin-left: 20px; margin-top: 5px;'>\n";
        $backtrace = Commons::debug_backtrace();

        foreach ($backtrace as $bt)
        {
            $args = '';
            if (is_array($bt['args']))
            {
                foreach ($bt['args'] as $a)
                {
                    if (!empty($args))
                       $args .= ', ';

                    switch (gettype($a))
                    {
                        case 'integer':
                        case 'double':
                            $args .= $a;
                            break;
                        case 'string':
                            $a = substr($a, 0, 64) . ((strlen($a) > 64) ? '...' : '');
                            //$a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
                            $args .= "\"$a\"";
                            break;
                        case 'array':
                            $args .= 'Array('.count($a).')';
                            break;
                        case 'object':
                            $args .= 'Object('.get_class($a).')';
                            break;
                        case 'resource':
                            $args .= 'Resource('.strstr($a, '#').')';
                            break;
                        case 'boolean':
                            $args .= $a ? 'True' : 'False';
                            break;
                        case 'NULL':
                            $args .= 'Null';
                            break;
                        default:
                            $args .= 'Unknown';
                    }
                }
            }

            $theClass = (isset($bt['class']) ? $bt['class'] : '');
            $theType = (isset($bt['type']) ? $bt['type'] : '');
            $file = (isset($bt['file']) ? $bt['file'] : '<i>-- unknown file --</i>');
            $line = (isset($bt['line']) ? ", line {$bt['line']}" : '');
            $output .= "<b>{$file}{$line}</b><br />\n";
            $output .= "<div style='margin-left:35px'>{$theClass}{$theType}{$bt['static function']}($args)</div>\n";
        }
        $output .= "</div></div>\n";
        return $output;
    }

    // Error/trace test:
    // ----------------
    //info('Hello world, how is it going ?');
    //warning('This is a warning');
    //echo backtrace();
    //assert('12/3 > 20');
    //error('This is an error');
    //fatal('This is a fatal error');


    /**
     * Checks if a string ends with a string.
     *
     * @param $s: the string to check
     * @param $end: the string end. Can be an array of possible ends.
     * @param $caseInsensitive: If true, compare is case insensitive.
     * @return: true if check successful.
     */
    static function endswith($s, $end, $caseInsensitive=false)
    {
        $cmpFct = $caseInsensitive ? 'strcasecmp' : 'strcmp';

        if (is_string($end))
            return $cmpFct(substr($s, -strlen($end)), $end) == 0;
        elseif (is_array($end))
        {
            foreach ($end as $v)
            {
                if ($cmpFct(substr($s, -strlen($v)), $v) == 0)
                    return true;
            }
        }
        return false;
    }

    /**
      * Converts a (limited) ISO 8601 date to a Unix timestamp.
      * corrections: if year < 1970 => year=1970
      *              if year > 2037 => year 2037
      * @param string $date Date/time with format "yyyymmdd[Thh:mm:ssss]"
      *        a '-' or a '/' is accepted in the date (yyyy-mm-dd, yyyy/mm/dd)
      * @return timestamp (0 si date=='T00'), ou false si NOK.
      */
    static function isoStrToTime($date)
    {
      if ($date == 'T00')
        return 0;

      $h = $min = $s = 0;
      $pattern = "/^(\\d{4})[\-\/]?(\\d{2})[\-\/]?(\\d{2})(?:T(\\d{2}):(\\d{2}):(\\d{4}))?$/";
      if (preg_match($pattern, $date, $matches) == 0)
        return false;
      $y = min(2037, max(1970, $matches[1]));
      $m = $matches[2];
      $d = $matches[3];
      if (count($matches) == 7)
      {
        $h = $matches[4];
        $min = $matches[5];
        $s = intval($matches[6]) / 100;
      }
      return mktime($h, $min, $s, $m, $d, $y);
    }

    /**
     * Filters a value read from a field (=POSTed) so it is magic-quote free.
     */
    static function getValueFromField($s, $trim=true)
    {
        if ($trim)
            $s = trim($s);
        return Commons::stripMagicQuotes($s);
    }

    /**
     * Encode a string to be safely displayed in an html page.
     */
    static function encodeForPage($s, $nl2br=false, $charset='utf-8', $substPseudoHtmlTags=true)
    {
        //@begin::Code skipped by etch
        //return $s;
        //@end skip

        //$s = htmlentities($s, ENT_QUOTES, $charset);

        if ($substPseudoHtmlTags)
        {
            // Sanitize special markup:
            $s = Commons::fixSpecialMarkup($s);

            // Replace a few pseudo html tags [x] with real ones <x>:
            $bbTags = array('{', '}', '[i]', '[/i]', '[b]', '[/b]', '[br]', '[p]');
            $htmlTags = array('<i>', '</i>', '<i>', '</i>', '<b>', '</b>', '<br />', '<p />');
            $s = str_replace($bbTags, $htmlTags, $s);
        }

        if ($nl2br)
            $s = nl2br($s);
        return $s;
    }

    /**
     * Simplified version of the above encodeForPage(), using defaults.
     */
    static function encodeForPageSimple($s, $substPseudoHtmlTags=false)
    {
        return Commons::encodeForPage($s, false, 'utf-8', $substPseudoHtmlTags);
    }


    /**
     * Fixes special markup in a string.
     *
     * Actually only handle italics markup [i][/i] and {}.
     * Suppress useless markup, normalize to {} notation, auto-close open { on EOS.
     * @param s: The string to process.
     * @return: The string with markup fixed.
     */
    static function fixSpecialMarkup($s) {

        $s = str_replace(array('[i]', '[/i]'), array('{', '}'), $s);
        $out = '';
        $openItalics = false;

        for($i=0; $i<strlen($s); ++$i) {
            $c = $s[$i];
            if ($c == '{') {
                if ($openItalics)   // ignore if already one open {
                    continue;
                else
                    $openItalics = true;
            } elseif ($c == '}') {
                if (!$openItalics)  // ignore if no open {
                    continue;
                else
                    $openItalics = false;
            }
            $out .= $c;
        }
        if ($openItalics) {     // auto-close if necessary
            $out .= '}';
        }
        //echo "{$out}<br />";
        return $out;
    }

    /**
     * Removes magic quotes from string $s if the magic quote conf. option is on.
     *
     * Guarantees that $s will have the backslashes before its ' " and \
     * removed, independently of the state of the magic quote conf. option.
     * This is useful when reading data from $_POST, $_GET, and COOKIE.
     */
    static function stripMagicQuotes($s)
    {
        return (get_magic_quotes_gpc() ? stripslashes($s) : $s);
    }

    /**
     * Same as stripMagicQuotes(), but trim $s first.
     */
    static function stripMQTrim($s)
    {
        return Commons::stripMagicQuotes(trim($s));
    }

    /**
     * Adds magic quotes to strong $ if the magic quote conf. option is off.
     *
     * Inverse of stripMagicQuotes(): guarantees that $s will have its ' " and \
     * backslashed exactly once, e.g. for writing $s to a database.
     */
    static function addMagicQuotes($s)
    {
        return (get_magic_quotes_gpc() ? $s : addslashes($s));
    }

    /**
     * Strips html tags and magic quotes from all items in array $fields (recursively).
     *
     * Useful for preparing POST data to be written to base or displayed
     * in a page [htmlspecialchars() should be called in the latter case].
     */
    static function stripTagsMQFromArray(&$fields, $trim=false)
    {
        foreach($fields as $fieldName => $fieldValue)
        {
            if (is_array($fieldValue))
                Commons::stripTagsMQFromArray($fieldValue, $trim);
            else
            {
                if ($trim)
                    $fieldValue = trim($fieldValue);
                $fields[$fieldName] = Commons::stripMagicQuotes(strip_tags($fieldValue));
            }
        }
    }

    /**
     * Gets formatted html for an error message concerning a bad form field.
     *
     * This static function is for generating suitable html for form field labels when
     * the field content is incorrect, including assigning the class 'invalid-field',
     * and generating code for a tooltip.
     *
     * == TODO: depends on 1 to 2 globals (shame on me!), needs refactoring :
     *
     *   - If the form is not yet submitted, no html is generated and '' is returned.
     *     This is because when a form is initially displayed, the content may be invalid
     *     until the user submits the form. The form is considered submitted if
     *     $alreadySubmitted is true, or, if $alreadySubmitted is NULL, if the global
     *     variable $submitted is true.
     *   - If $cartels_config->errorToolTips is true, code for a tooltip is included,
     */
    static function getBadFieldHtml($errMsg, $submitted=NULL, $alreadySubmitted=NULL)
    {

        if (is_null($alreadySubmitted))
            $alreadySubmitted = $submitted;

        if (!$alreadySubmitted)
            return '';

        $l = strlen($errMsg);
        $toolTip = (ERROR_TOOLTIPS ? sprintf(' onmouseover="ddrivetip(\'%s\', %d)" onmouseout="hideddrivetip()"',
                    addslashes($errMsg), $l*6) : '');
        return ' class="invalid-field"' . $toolTip;
    }

    static function getFilterHtml($sFilter)
    {
        //Preparing Rubrique for filter
        $tRubriques = Apps::getModel('Cartel')->dbGetRubriques();
        foreach($tRubriques as $oRub ){
            $tRubriquesFilterData[$oRub->id] = $oRub->id;
        }

        //Preparing Country for filter
        $tCountry = Country::getCountryList();
        foreach($tCountry as $sKey => $oCou ){
            $tCountryFilterData[$sKey] = $oCou;
        }
		
        //Preparing ACF for filter
        $tACF = Commons::getRegionAcf();
        foreach($tACF as $sKey => $oAcf ){
            $tAcfFilterData[$sKey] = $oAcf;
        }		

        // Departments List
        $tDepartements = Country::getDepartements();
        
        $tInput = array(
            //Cartels
            "id" => HtmlTags::htmlInput($sFilter),
            "titre" => HtmlTags::htmlInput($sFilter),
            "id_rubrique" => HtmlTags::htmlSelect($sFilter, $tRubriquesFilterData),
            "commentaires" => HtmlTags::htmlInput($sFilter),
            "actif" => HtmlTags::htmlSelect($sFilter, array("Non", "Oui")),
            "date_creation" => HtmlTags::htmlDate($sFilter),
            "date_modif" => HtmlTags::htmlDate($sFilter),
            //Cartellisants
            "nom" => HtmlTags::htmlInput($sFilter),
            "prenom" => HtmlTags::htmlInput($sFilter),
            "adresse" => HtmlTags::htmlInput($sFilter),
            "ville" => HtmlTags::htmlInput($sFilter),
            "code_postal" => HtmlTags::htmlInput($sFilter),
            "pays" => HtmlTags::htmlSelect($sFilter, $tCountryFilterData), //select
            "latitude" => HtmlTags::htmlInput($sFilter),
            "longitude" => HtmlTags::htmlInput($sFilter),
            "latitude_floue" => HtmlTags::htmlInput($sFilter),
            "longitude_floue" => HtmlTags::htmlInput($sFilter),
            "telephone" => HtmlTags::htmlInput($sFilter),
            "fax" => HtmlTags::htmlInput($sFilter),
            "email" => HtmlTags::htmlInput($sFilter),
            "page_web" => HtmlTags::htmlInput($sFilter),
            //"associations" => HtmlTags::htmlInput($sFilter),
			"associations" => HtmlTags::htmlSelect($sFilter, $tAcfFilterData), //select
            "plus_un" => HtmlTags::htmlText($sFilter, 1, "Oui"),
            "departement" =>HtmlTags::htmlSelect($sFilter, $tDepartements),
            "region_acf" =>HtmlTags::htmlInput($sFilter)
        );
        
        $sFilterLabel = $sFilter;
        $sFilterLabel = ($sFilter=='id_rubrique') ? "Rubrique": $sFilterLabel ;
		$sFilterLabel = ($sFilter=='associations') ? "Région ACF": $sFilterLabel ;
        $sFilterLabel = ($sFilter=='date_creation') ? "Date de création": $sFilterLabel ;
        $sFilterLabel = ($sFilter=='prenom') ? "Prénom": $sFilterLabel ;
        $sHtml = "<div class='filter_$sFilter'><label><b>" . ucfirst( str_replace("_", " ", $sFilterLabel) ) . " :</b></label>";
        $sHtml .= $tInput[$sFilter] . " <img class='delete_filter filter_delete_".$sFilter."' src='".Router::getSiteUrl()."/themes/images/deleteIcon.png' title='Supprimer le filtre' alt='Supprimer le filtre'/>";

        return "$sHtml </div>";
    }
	
	static function debug_backtrace(){
	}
        
        static function parseName($nom, $prenom){
            $nom = mb_strtoupper($nom, 'UTF-8');
            
            $prenom = self::my_mb_ucfirst($prenom, 'UTF-8');            
            
            //modification avec  -
            $tPrenom = explode('-', $prenom);
            if(count($tPrenom) > 1) 
            {
                foreach ($tPrenom as $_prenom) {
                    $_tPrenom[] =  self::my_mb_ucfirst($_prenom[0], 'UTF-8'). substr($_prenom,1);
                }
                $prenom = implode('-', $_tPrenom); 
            }
            
            //modification avec space
            unset($tPrenom);
            $tPrenom = explode(' ', $prenom);
            if(count($tPrenom) > 1) 
            {            
                foreach ($tPrenom as $_prenom) {
                    $_tPrenom[] =  self::my_mb_ucfirst($_prenom[0], 'UTF-8'). substr($_prenom,1);
                }
                $prenom = implode(' ', $_tPrenom);            
            }
            
            $parsedName = self::encodeForPage( $nom . " " . $prenom );
            return $parsedName;
        }
        
    static function my_mb_ucfirst($string, $e ='utf-8') {
        if (function_exists('mb_strtoupper') && function_exists('mb_substr') && !empty($string)) {
            $string = mb_strtolower($string, $e);
            $upper = mb_strtoupper($string, $e);
            preg_match('#(.)#us', $upper, $matches);
            $string = $matches[1] . mb_substr($string, 1, mb_strlen($string, $e), $e);
        } else {
            $string = ucfirst($string);
        }
        return $string;
    }

    static public function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }


    static function GetInt4d($data, $pos) {
        $value = ord($data[$pos]) | (ord($data[$pos+1])	<< 8) | (ord($data[$pos+2]) << 16) | (ord($data[$pos+3]) << 24);
        if ($value>=4294967294) {
            $value=-2;
        }
        return $value;
    }

// http://uk.php.net/manual/en/function.getdate.php
    static function gmgetdate($ts = null){
        $k = array('seconds','minutes','hours','mday','wday','mon','year','yday','weekday','month',0);
        return(array_comb($k,split(":",gmdate('s:i:G:j:w:n:Y:z:l:F:U',is_null($ts)?time():$ts))));
    }

// Added for PHP4 compatibility
    static function array_comb($array1, $array2) {
        $out = array();
        foreach ($array1 as $key => $value) {
            $out[$value] = $array2[$key];
        }
        return $out;
    }

    static function v($data,$pos) {
        return ord($data[$pos]) | ord($data[$pos+1])<<8;
    }
}

