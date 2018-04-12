<?php
//namespace FPDM;

require('FilterASCII85.php');
require('FilterASCIIHex.php');
require('FilterFlate.php');
require('FilterLZW.php');
require('FilterStandard.php');

//use filters\FilterASCIIHex;
//use filters\FilterASCII85;
//use filters\FilterFlate;
//use filters\FilterLZW;
//use filters\FilterStandard;

define('FPDM_CACHE', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR); //cache directory for fdf temporary files needed by pdftk.

class FPDM
{
    const FPDM_VERSION = 2.6;
    const FPDM_RELEASE = "Snowstream forever dream(20101225)";
    const FPDM_INVALID = 0;
    const FPDM_STATIC = 1;
    const FPDM_COMMON = 2;
    const FPDM_VERBOSE = 3;
    const FPDM_PASSWORD_MAX_LEN = 15;
    const FPDM_REGEXPS = array(
        "/Type" => "/\/Type\s+\/(\w+)$/",
        "/Subtype" => "/^\/Subtype\s+\/(\w+)$/"
    );
    
    protected $pdf_source;      //string: full pathname to the input pdf , a form file
    protected $fdf_source;      //string: full pathname to the input fdf , a form data file
    protected $pdf_output;      //string: full pathname to the resulting filled pdf

    protected $pdf_entries;     //array: Holds the content of the pdf file as array
    protected $fdf_content;     //string: holds the content of the fdf file
    protected $fdf_parse_needed;//boolean: false will use $fields data else extract data from fdf content
    protected $value_entries;   //array: a map of values to faliclitate access and changes

    protected $positions;      //array, stores what object id is at a given position n ($positions[n]=<obj_id>)

    protected $offsets;      //array of offsets for objects, index is the object's id, starting at 1
    protected $pointer;      //integer, Current line position in the pdf file during the parsing

    protected $shifts;          //array, Shifts of objects in the order positions they appear in the pdf, starting at 0.
    protected $shift;          //integer, Global shift file size due to object values size changes

    protected $streams;         //Holds streams configuration found during parsing
    protected $streams_filter;  //Regexp to decode filter streams

    protected $safe_mode;       //boolean, if set, ignore previous offsets do no calculations for the new xref table, seek pos directly in file
    protected $check_mode;      //boolean, Use this to track offset calculations errors in corrupteds pdfs files for sample
    protected $halt_mode;      //if true, stops when offset error is encountered

    protected $info;              //array, holds the info properties
    protected $fields;          //array that holds fields-Data parsed from FDF

    protected $verbose;         //boolean ,  a debug flag to decide whether or not to show internal process
    protected $verbose_level;   //integer default is 1 and if greater than 3, shows internal parsing as well

    protected $support;          //string set to 'native' for fpdm or 'pdftk' for pdf toolkit
    protected $flatten_mode;      //if true, flatten field data as text and remove form fields (NOT YET SUPPORTED BY FPDM)
    protected $compress_mode;   //boolean , pdftk feature only to compress streams
    protected $uncompress_mode; //boolean pdftk feature only to uncompress streams
    protected $security;        //Array holding securtity settings
    //(password owner nad user,  encrypt (set to 40 or 128 or 0), allow <permissions>] see pdfk help

    protected $needAppearancesTrue;    //boolean, indicates if /NeedAppearances is already set to true
    protected $isUTF8;                //boolean (true for UTF-8, false for ISO-8859-1)

    /**
     * Constructor
     *
     * @example Common use:
     * @param string $pdf_source Source-Filename
     * @param string $fdf_source Source-Filename
     * @param boolean $verbose , optional false per default
     */
    public function __construct()
    {
        //==============

        $args = func_get_args();
        $num_args = func_num_args();

        $FDF_FILE = ($num_args >= self::FPDM_COMMON);
        $VERBOSE_FLAG = ($num_args >= self::FPDM_VERBOSE);

        $verbose = false;

        //We are not joking here, let's have a polymorphic constructor!
        switch ($num_args) {
            case self::FPDM_INVALID:
                $this->Error("Invalid instantiation of FPDM, requires at least one param");
                break;
            case self::FPDM_STATIC:
                if ($args[0] == '[_STATIC_]') break; //static use, caller is anonymous function defined in _set_field_value
            //else this is the pdf_source then, fdf content is loaded using Load() function
            default:
            case self::FPDM_VERBOSE: //Use the verbose value provided
                if ($VERBOSE_FLAG) $verbose = $args[2];
            case self::FPDM_COMMON: //Common use
                $this->pdf_source = $args[0];//Blank pdf form

                if ($FDF_FILE) {
                    $this->fdf_source = $args[1];//Holds the data of the fields to fill the form
                    $this->fdf_parse_needed = true;
                }

                //calculation and map
                $this->offsets = array();
                $this->pointer = 0;
                $this->shift = 0;
                $this->shifts = array();
                $this->n = 0;

                //Stream filters
                $filters = $this->getFilters("|");
                $this->streams_filter = "/(\/($filters))+/";
                //$this->dumpContent($this->streams_filter);

                $this->info = array();

                //Debug modes
                $this->verbose = $verbose;
                $this->verbose_level = ($verbose && is_int($verbose)) ? $verbose : 1;
                $this->safe_mode = false;
                $this->check_mode = false; //script will takes much more time if you do so
                $this->halt_mode = true;

                $this->support = 'native'; //may ne overriden
                $this->security = array('password' => array('owner' => null, 'user' => null), 'encrypt' => 0, 'allow' => array());

                //echo "<br>filesize:".filesize($this->pdf_source);
                $this->load_file('PDF');

                if ($FDF_FILE) $this->load_file('FDF');

        }
    }

    /**
     *Loads a form data to be merged
     *
     * @note this overrides fdf input source if it was previously defined
     * @param string|array $fdf_data a FDF file content or $pdf_data an array containing the values for the fields to change
     **/
    public function Load($data, $isUTF8 = false)
    {
        //------------------------
        $this->isUTF8 = $isUTF8;
        $this->load_file('FDF', $data);
    }

    /**
     *Loads a file according to its type
     *
     * @param string type 'PDF' or 'FDF'
     * @param String|array content the data content of FDF files only or directly the fields values as array
     **/
    protected function load_file($type, $content = NULL)
    {
        //------------------------------------
        switch ($type) {
            case "PDF" :
                if ($content)
                    $this->Error("load_file do not accept PDF content, only FDF content sorry");
                else
                    $this->pdf_entries = $this->getEntries($this->pdf_source, 'PDF');
                break;
            case "FDF" :
                if (!is_null($content)) {
                    if (is_array($content)) {
                        $this->fields = $content;
                        $this->fdf_parse_needed = false;
                        //$this->dumpEntries($content,"PDF fields content");
                    } else if (is_string($content)) { //String
                        $this->fdf_content = $content; //TODO: check content
                        $this->fdf_parse_needed = true;
                    } else
                        $this->Error('Invalid content type for this FDF file!');
                } else {
                    $this->fdf_content = $this->getContent($this->fdf_source, 'FDF');
                    $this->fdf_parse_needed = true;
                }
                break;
            default:
                $this->Error("Invalid file type $type");
        }
    }

    /**
     *Set a mode and play with your power debug toys
     *
     * @note for big boys only coz it may hurt
     * @param string $mode a choice between 'safe','check','verbose','halt' or 'verbose_level'
     * @param string|int $value an integer for verbose_level
     **/
    public function set_modes($mode, $value)
    {
        //-------------------------------
        switch ($mode) {
            case 'safe':
                $this->safe_mode = $value;
                break;
            case 'check':
                $this->check_mode = $value;
                break;
            case 'flatten':
                $this->flatten_mode = $value;
                break;
            case 'compress_mode':
                $this->compress_mode = $value;
                if ($value) $this->uncompress_mode = false;
                break;
            case 'uncompress_mode':
                $this->uncompress_mode = $value;
                if ($value) $this->compress_mode = false;
                break;
            case 'verbose':
                $this->verbose = $value;
                break;
            case 'halt':
                $this->halt_mode = $value;
                break;
            case 'verbose_level':
                $this->verbose_level = $value;
                break;
            default:
                $this->Error("set_modes error, Invalid mode '<i>$mode</i>'");
        }
    }

    /**
     *Retrieves informations of the pdf
     *
     * @note To track PDF versions and so on...
     * @param Boolean output
     **/
    public function Info($asArray = false)
    {
        //----------------------
        $info = $this->info;
        $info["Reader"] = ($this->support == "native") ? 'FPDF-Merge ' . self::FPDM_VERSION : $this->support;
        $info["Fields"] = $this->fields;
        $info["Modes"] = array(
            'safe' => ($this->safe_mode) ? 'Yes' : 'No',
            'check' => ($this->check_mode) ? 'Yes' : 'No',
            'flatten' => ($this->flatten_mode) ? 'Yes' : 'No',
            'compress_mode' => ($this->compress_mode) ? 'Yes' : 'No',
            'uncompress_mode' => ($this->uncompress_mode) ? 'Yes' : 'No',
            'verbose' => $this->verbose,
            'verbose_level' => $this->verbose_level,
            'halt' => $this->halt_mode
        );
        if ($asArray) {
            return $info;
        } else {
            $this->dumpEntries($info, "Welcome on FPDMerge flight to " . self::FPDM_RELEASE . ", here is the pdf temperature:");
        }
    }

    /**
     *Changes the support
     *
     * @internal fixes xref table offsets
     * @note special playskool toy for Christmas dedicated to my impatient fanclub (Grant, Kris, nejck,...)
     * @param String support Allow to use external support that has more advanced features (ie 'pdftk')
     **/
    public function Plays($cool)
    {
        //----------------------
        if ($cool == 'pdftk')  //Use a coolest support as ..
            $this->support = 'pdftk';//..Per DeFinition This is Kool!
        else
            $this->support = 'native';
    }

    /**
     *Fixes a corrupted PDF file
     *
     * @internal fixes xref table offsets
     * @note Real work is not made here but by Merge that should be launched after to complete the work
     **/
    public function Fix()
    {
        //---------------
        if (!$this->fields) $this->fields = array(); //Default: No field data
        $this->set_modes('check', true); //Compare xref table offsets with objects offsets in the pdf file
        $this->set_modes('halt', false); //Do no stop on errors so fix is applied during merge process
    }

    //######## pdftk's output configuration #######

    /**
     *Decides to use  the  compress filter to restore compression.
     * @note  This is only useful when you want to repack PDF that was previously edited in a text  editor like vim or emacs.
     **/
    public function Compress()
    {
        //-------------------
        $this->set_modes('compress', true);
        $this->support = "pdftk";
    }

    /**
     *Decides to remove PDF page stream compression by applying    the  uncompress  filter.
     * @note  This is only useful when you want to edit PDF code in a text  editor like vim or emacs.
     **/
    public function Uncompress()
    {
        //---------------------
        $this->set_modes('uncompress', true);
        $this->support = "pdftk";
    }

    /**
     *Activates the flatten output to remove form from pdf file keeping field datas.
     **/
    public function Flatten()
    {
        //-----------------
        $this->set_modes('flatten', true);
        $this->support = "pdftk";
    }

    /***
     *Defines a password type
     * @param String type , 'owner' or  'user'
     **/
    public function Password($type, $code)
    {
        //------------------------------
        switch ($type) {
            case 'owner':
            case 'user':
                $this->security["password"]["$type"] = $code;
                break;
            default:
                $this->Error("Unsupported password type ($type), specify 'owner' or 'user' instead.");
        }
        $this->support = "pdftk";
    }


    /**
     *Defines the encrytion to the given bits
     * @param integer $bits 0, 40 or 128
     **/
    public function Encrypt($bits)
    {
        //-----------------------
        switch ($bits) {
            case 0:
            case 40:
            case 128:
                $this->security["encrypt"] = $bits;
                break;
            default:
                $this->Error("Unsupported encrypt value of $bits, only 0, 40 and 128 are supported");
        }
        $this->support = "pdftk";
    }

    /**
     *Allow permissions
     *
     * @param Array permmissions If no arg is given, show help.
     *   Permissions  are applied to the output PDF only if an encryption
     *  strength is specified or an owner or user password is given.  If
     *  permissions  are    not  specified,  they default to 'none,' which
     *  means all of the following features are disabled.
     *
     *  The permissions section may include one or more of the following
     *  features:
     *
     *  Printing
     *    Top Quality Printing
     *
     * DegradedPrinting
     *    Lower Quality Printing
     *
     *  ModifyContents
     *     Also allows Assembly
     *
     *  Assembly
     *
     *  CopyContents
     *     Also allows ScreenReaders
     *
     *  ScreenReaders
     *
     *  ModifyAnnotations
     *     Also allows FillIn
     *
     *  FillIn
     *
     *  AllFeatures
     *     Allows  the  user    to  perform  all of the above, and top
     *     quality printing.
     **/
    public function Allow($permissions = null)
    {
        //--------------------------
        $perms_help = array(
            'Printing' => 'Top Quality Printing',
            'DegradedPrinting' => 'Lower Quality Printing',
            'ModifyContents' => 'Also allows Assembly',
            'Assembly' => '',
            'CopyContents' => 'Also allows ScreenReaders',
            'ScreenReaders' => '',
            'ModifyAnnotations' => 'Also allows FillIn',
            'FillIn' => '',
            'AllFeatures' => "All above"
        );
        if (is_null($permissions)) {
            echo '<br>Info Allow permissions:<br>';
            print_r($perms_help);
        } else {
            if (is_string($permissions)) $permissions = array($permissions);
            $perms = array_keys($perms_help);
            $this->security["allow"] = array_intersect($permissions, $perms);
            $this->support = "pdftk";
        }
    }

    //#############################

    /**
     *Merge FDF file with a PDF file
     *
     * @note files has been provided during the instantiation of this class
     * @internal flatten mode is not yet supported
     * @param Boolean flatten Optional, false by default, if true will use pdftk (requires a shell) to flatten the pdf form
     **/
    public function Merge($flatten = false)
    {
        //------------------------------

        if ($flatten) $this->Flatten();


        if ($this->support == "native") {

            if ($this->fdf_parse_needed) {
                $fields = $this->parseFDFContent();
            } else {
                $fields = $this->fields;
            }

            $count_fields = count($fields);

            if ($this->verbose && ($count_fields == 0))
                $this->dumpContent("The FDF content has either no field data or parsing may failed", "FDF parser: ");

            $fields_value_definition_lines = array();

            $count_entries = $this->parsePDFEntries($fields_value_definition_lines);


            if ($count_entries) {

                $this->value_entries = $fields_value_definition_lines;
                if ($this->verbose) {
                    $this->dumpContent("$count_entries Field entry values found for $count_fields field values to fill", "Merge info: ");
                }
                //==== Alterate work is made here: change values ============
                if ($count_fields) {
                    foreach ($fields as $name => $value) {
                        $this->set_field_value("current", $name, $value);
//							$value=''; //Strategy applies only to current value, clear others
//							$this->set_field_value("default",$name,$value);
//							$this->set_field_value("tooltip",$name,$value);
                    }
                }
                //===========================================================

                //===== Cross refs/size fixes (offsets calculations for objects have been previously be done in set_field_value) =======

                //Update cross reference table to match object size changes
                $this->fix_xref_table();

                //update the pointer to the cross reference table
                $this->fix_xref_start();

            } else
                $this->Error("PDF file is empty!");

        } //else pdftk's job is done in Output, not here.
    }

    /**
     *Warns verbose/output conflicts
     *
     * @param string $dest a output destination
     **/
    public function Close($dest)
    {
        //----------------
        $this->Error("Output: Verbose mode should be desactivated, it is incompatible with this output mode $dest");
    }

    /**
     *Get current pdf content (without any offset fixes)
     *
     * @param String pdf_file, if given , use the content as buffer (note file will be deleted after!)
     * @return string buffer the pdf content
     **/
    protected function get_buffer($pdf_file = '')
    {
        //---------------------
        if ($pdf_file == '') {
            $buffer = implode("\n", $this->pdf_entries);
        } else {
            $buffer = $this->getContent($pdf_file, 'PDF');
            //@unlink($pdf_file);
        }
        return $buffer;
    }


    /**
     *Output PDF to some destination
     *
     * @note reproduces the fpdf's behavior
     * @param string name the filename
     * @string dest the destination
     *    by default it's a file ('F')
     *   if 'D'  , download
     *    and 'I' , Send to standard output
     *
     **/
    public function Output($name = '', $dest = '')
    {
        //-----------------------------------

        $pdf_file = '';

        if ($this->support == "pdftk") {
            //As PDFTK can only merge FDF files not data directly,
            require_once("lib/url.php"); //we will need a url support because relative urls for pdf inside fdf files are not supported by PDFTK...
            require_once("export/fdf/fdf.php"); //...conjointly with my patched/bridged forge_fdf that provides fdf file generation support from array data.
            require_once("export/pdf/pdftk.php");//Of course don't forget to bridge to PDFTK!

            $tmp_file = false;
            $pdf_file = resolve_path(fix_path(dirname(__FILE__) . '/' . $this->pdf_source));      //string: full pathname to the input pdf , a form file

            if ($this->fdf_source) { //FDF file provided
                $fdf_file = resolve_path(fix_path(dirname(__FILE__) . '/' . $this->fdf_source));
            } else {

                $pdf_url = getUrlfromDir($pdf_file); //Normaly http scheme not local file

                if ($this->fdf_parse_needed) { //fdf source was provided
                    $pdf_data = $this->parseFDFContent();
                } else { //fields data was provided as an array, we have to generate the fdf file
                    $pdf_data = $this->fields;
                }

                $fdf_file = fix_path(FPDM_CACHE) . "fields" . rnunid() . ".fdf";
                $tmp_file = true;
                $ret = output_fdf($pdf_url, $pdf_data, $fdf_file);
                if (!$ret["success"])
                    $this->Error("Output failed as something goes wrong (Pdf was $pdf_url) <br> during internal FDF generation of file $fdf_file, <br>Reason is given by {$ret['return']}");
            }

            //Serializes security options (not deeply tested)
            $security = '';
            if (!is_null($this->security["password"]["owner"])) $security .= ' owner_pw "' . substr($this->security["password"]["owner"], 0, self::FPDM_PASSWORD_MAX_LEN) . '"';
            if (!is_null($this->security["password"]["user"])) $security .= ' user_pw "' . substr($this->security["password"]["user"], 0, self::FPDM_PASSWORD_MAX_LEN) . '"';
            if ($this->security["encrypt"] != 0) $security .= ' encrypt_' . $this->security["encrypt"] . 'bit';
            if (count($this->security["allow"]) > 0) {
                $permissions = $this->security["allow"];
                $security .= ' allow ';
                foreach ($permissions as $permission)
                    $security .= ' ' . $permission;
            }

            //Serialize output modes
            $output_modes = '';
            if ($this->flatten_mode) $output_modes .= ' flatten';
            if ($this->compress_mode) $output_modes .= ' compress';
            if ($this->uncompress_mode) $output_modes .= ' uncompress';


            $ret = pdftk($pdf_file, $fdf_file, array("security" => $security, "output_modes" => $output_modes));

            if ($tmp_file) @unlink($fdf_file); //Clear cache

            if ($ret["success"]) {
                $pdf_file = $ret["return"];
            } else
                $this->Error($ret["return"]);
        }

        $this->buffer = $this->get_buffer($pdf_file);


        $dest = strtoupper($dest);
        if ($dest == '') {
            if ($name == '') {
                $name = 'doc.pdf';
                $dest = 'I';
            } else
                $dest = 'F';
        }

        //Abort to avoid to polluate output
        if ($this->verbose && (($dest == 'I') || ($dest == 'D'))) {
            $this->Close($dest);
        }

        switch ($dest) {
            case 'I':
                //Send to standard output
                if (ob_get_length())
                    $this->Error('Some data has already been output, can\'t send PDF file');
                if (php_sapi_name() != 'cli') {
                    //We send to a browser
                    header('Content-Type: application/pdf');
                    if (headers_sent())
                        $this->Error('Some data has already been output, can\'t send PDF file');
                    header('Content-Length: ' . strlen($this->buffer));
                    header('Content-Disposition: inline; filename="' . $name . '"');
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                    ini_set('zlib.output_compression', '0');
                }
                echo $this->buffer;
                break;
            case 'D':
                //Download file
                if (ob_get_length())
                    $this->Error('Some data has already been output, can\'t send PDF file');
                header('Content-Type: application/x-download');
                if (headers_sent())
                    $this->Error('Some data has already been output, can\'t send PDF file');
                header('Content-Length: ' . strlen($this->buffer));
                header('Content-Disposition: attachment; filename="' . $name . '"');

                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); // HTTP/1.1
                header("Cache-Control: post-check=0, pre-check=0", false);
                //header("Pragma: "); // HTTP/1.0

                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public,no-cache');
                ini_set('zlib.output_compression', '0');
                echo $this->buffer;
                break;
            case 'F':
                //Save to local file
                if ($this->verbose) $this->dumpContent("Write file $name", "Output");
                $f = fopen($name, 'wb');
                if (!$f)
                    $this->Error('Unable to create output file: ' . $name . ' (currently opened under Acrobat Reader?)');

                fwrite($f, $this->buffer, strlen($this->buffer));
                fclose($f);
                break;
            case 'S':
                //Return as a string
                return $this->buffer;
            default:
                $this->Error('Incorrect output destination: ' . $dest);
        }
        return '';
    }


    /**
     *Decodes and returns the binary form of a field hexified value
     *
     * @note static method due to callback..
     * @param string value the hexified string
     * @return string call the binary string
     **/
    public function pdf_decode_field_value($value)
    {
        //----------------------------------------
        $call = $this->static_method_call('_hex2bin', $value);
        return $call;
    }

    /**
     *Encodes and returns the headecimal form of a field binary value
     *
     * @note static method due to callback..
     * @param string value the binary string
     * @return string call the hexified string
     **/
    public function pdf_encode_field_value($value)
    {
        //---------------------------------------
        $value = $this->static_method_call('_bin2hex', $value);
        return $value;
    }


    /**
     *Universal Php4/5 static call helper
     *
     * @param String $method a name of a method belonging to this class
     * @return mixed the return value of the called method
     **/
    public function static_method_call($method)
    {
        //---------------------------------------------

        $params_call = func_get_args();
        array_shift($params_call);
        //var_dump($params_call);

        return call_user_func_array(array($this, $method), $params_call);
    }

    /**
     *Changes a field value that can be in hex <> or binary form ()
     *
     * @param $matches the regexp matches of the line that contains the value to change
     * @param String $value the new value for the field property
     **/
    protected function replace_value($matches, $value)
    {
        //----------------------------------------------

        array_shift($matches);

        if (($value != '') && ($matches[1] == "<")) //Value must be hexified..
            $value = $this->pdf_encode_field_value($value);

        $matches[2] = $value;
        $value_type_code = $matches[0]; //Should be V, DV or TU
        $matches[0] = "/" . $value_type_code . " ";

        $value = implode("", $matches);
        //echo(htmlentities($value));
        return $value;
    }

    /**
     *Core to change the value of a field property, inline.
     *
     * @param int $line the lien where the field property value is defined in the pdf file
     * @param string $value the new value to set
     * @return int $shift the size change of the field property value
     **/
    protected function _set_field_value($line, $value)
    {
        //----------------------------------------

        $verbose_set = ($this->verbose && ($this->verbose_level > 1));
        //get the line content
        $CurLine = $this->pdf_entries[$line];

        $OldLen = strlen($CurLine);

        //My PHP4/5 static call hack, only to make the callback $this->replace_value($matches,"$value") possible!
        $callback_code = '$THIS=new FPDM("[_STATIC_]");return $THIS->replace_value($matches,"' . $value . '");';

        $field_regexp = '/^\/(\w+)\s?(\<|\()([^\)\>]*)(\)|\>)/';

        if (preg_match($field_regexp, $CurLine)) {
            //modify it according to the new value $value
            $CurLine = preg_replace_callback(
                $field_regexp,
                create_function('$matches', $callback_code),
                $CurLine
            );
        } else {
            if ($verbose_set) echo("<br>WARNING:" . htmlentities("Can not access to the value: $CurLine using regexp $field_regexp"));
        }


        $NewLen = strlen($CurLine);
        $Shift = $NewLen - $OldLen;
        $this->shift = $this->shift + $Shift;

        //Saves
        $this->pdf_entries[$line] = $CurLine;

        return $Shift;
    }

    protected function _encode_value($str)
    {
        if ($this->isUTF8)
            $str = "\xFE\xFF" . iconv('UTF-8', 'UTF-16BE', $str);
        return $this->_bin2hex($str);
    }

    protected function _set_field_value2($line, $value, $append)
    {
        $CurLine = $this->pdf_entries[$line];
        $OldLen = strlen($CurLine);

        if ($append) {
            $CurLine .= ' /V <' . $this->_encode_value($value) . '>';
        } else {
            if (preg_match('#/V\s?[<(]([^>)]*)[>)]#', $CurLine, $a, PREG_OFFSET_CAPTURE)) {
                $len = strlen($a[1][0]);
                $pos1 = $a[1][1];
                $pos2 = $pos1 + $len;
                $CurLine = substr($CurLine, 0, $pos1 - 1) . '<' . $this->_encode_value($value) . '>' . substr($CurLine, $pos2 + 1);
            } else
                $this->Error('/V not found');
        }

        $NewLen = strlen($CurLine);
        $Shift = $NewLen - $OldLen;
        $this->shift = $this->shift + $Shift;
        $this->pdf_entries[$line] = $CurLine;
        return $Shift;
    }


    /**
     *Changes the value of a field property, inline.
     *
     * @param string $type supported values for type are 'default' , 'current' or 'tooltip'
     * @param string $name name of the field annotation to change the value
     * @param string $value the new value to set
     **/
    protected function set_field_value($type, $name, $value)
    {
        //------------------------------------
        $verbose_set = ($this->verbose && ($this->verbose_level > 1));

        //Get the line(s) of the misc field values
        if (isset($this->value_entries["$name"])) {

            $object_id = $this->value_entries["$name"]["infos"]["object"];

            if ($type == "tooltip") {

                $offset_shift = $this->set_field_tooltip($name, $value);

            } else {//if(isset($this->value_entries["$name"]["values"]["$type"])) {
//				echo $this->value_entries["$name"]["values"]["$type"];
                /*					$field_value_line=$this->value_entries["$name"]["values"]["$type"];
                                    $field_value_maxlen=$this->value_entries["$name"]["constraints"]["maxlen"];

                                    if($field_value_maxlen) //Truncates the size if needed
                                        $value=substr($value, 0, $field_value_maxlen);

                                    if($verbose_set) echo "<br>Change $type value of the field $name at line $field_value_line to '<i>$value</i>'";
                                    $offset_shift=$this->_set_field_value($field_value_line,$value);*/
                if (isset($this->value_entries[$name]["values"]["current"]))
                    $offset_shift = $this->_set_field_value2($this->value_entries[$name]["values"]["current"], $value, false);
                else
                    $offset_shift = $this->_set_field_value2($this->value_entries[$name]["infos"]["name_line"], $value, true);
            }
//				}else
//					$this->Error("set_field_value failed as invalid valuetype $type for object $object_id");


            //offset size shift will affect the next objects offsets taking into accound the order they appear in the file--
            $this->apply_offset_shift_from_object($object_id, $offset_shift);

        } else
            $this->Error("field $name not found");

    }


    /**
     *Changes the tooltip value of a field property, inline.
     *
     * @param string $name name of the field annotation to change the value
     * @param string $value the new value to set
     * @return int offset_shift the size variation
     **/
    protected function set_field_tooltip($name, $value)
    {
        //------------------------------------
        $offset_shift = 0;
        $verbose_set = ($this->verbose && ($this->verbose_level > 1));

        //Get the line(s) of the misc field values
        if (isset($this->value_entries["$name"])) {
            $field_tooltip_line = $this->value_entries["$name"]["infos"]["tooltip"];
            if ($field_tooltip_line) {
                if ($verbose_set) echo "<br>Change tooltip of the field $name at line $field_tooltip_line to value [$value]";
                $offset_shift = $this->_set_field_value($field_tooltip_line, $value);
            } else {
                if ($verbose_set) echo "<br>Change toolpip value aborted, the field $name has no tooltip definition.";
            }
        } else
            $this->Error("set_field_tooltip failed as the field $name does not exist");
        return $offset_shift;
    }

    /**
     *Dumps the line entries
     *
     * @note for debug purposes
     * @param array entries the content to dump
     * @param string tag an optional tag to highlight
     * @param boolean halt decides to stop or not this script
     **/
    public function dumpEntries($entries, $tag = "", $halt = false)
    {
        //------------------------------------------------------------
        if ($tag) echo "<br><h4>$tag</h4><hr>";
        if ($entries) {
            echo "<pre>";
            echo htmlentities(print_r($entries, true));
            echo "</pre>";
        }
        if ($halt) exit();
    }


    /**
     *Dumps the string content
     *
     * @note for debug purposes
     * @param string content the content to dump
     * @param string tag an optional tag to highlight
     * @param boolean halt decides to stop or not this script
     **/
    public function dumpContent($content, $tag = "", $halt = false)
    {
        //--------------------------------------------------
        if ($tag) echo "<h4>$tag</h4>";
        if ($content) {
            echo "<pre>";
            echo htmlentities($content);
            echo "</pre>";
        }
        if ($halt) exit();
    }

    /**
     *Retrieves the content of a file as a string
     *
     * @param string $filename the filename of the file
     * @param string $filetype the type of file as info
     * @return string $content
     **/
    public function getContent($filename, $filetype)
    {
        //----------------------------------------
        //$content = file_get_contents($filename);
        $handle = fopen($filename, 'rb');
        $content = fread($handle, filesize($filename));
        fclose($handle);

        if (!$content)
            $this->Error(sprintf('Cannot open ' . $filetype . ' file %s !', $filename));

        if ($filetype == 'PDF') {
            $start = substr($content, 0, 2048);
            if (strpos($start, '/ObjStm') !== false)
                $this->Error('Object streams are not supported');
            if (strpos($start, '/Linearized') !== false)
                $this->Error('Fast Web View mode is not supported');
            $end = substr($content, -512);
            if (strpos($end, '/Prev') !== false)
                $this->Error('Incremental updates are not supported');
            $this->needAppearancesTrue = (strpos($content, '/NeedAppearances true') !== false);
        }

        /*  if($this->verbose) {
              $this->dumpContent($content,"$filetype file content read");
          }*/
        return $content;
    }

    /**
     *Retrieves the content of a file as an array of lines entries
     *
     * @param string $filename the filename of the file
     * @param string $filetype the type of file as info
     * @return array $entries
     **/
    public function getEntries($filename, $filetype)
    {
        //----------------------------------------
        $content = $this->getContent($filename, $filetype);
        $entries = explode("\n", $content);

        /* if($this->verbose) {
             $this->dumpEntries($entries,"$filetype file entries");
         }*/
        return $entries;
    }


    /**
     *Retrieves a binary string from its hexadecimal representation
     *
     * @note Function was written because PHP has a bin2hex, but not a hex2bin!
     * @internal note pack(�C�,hexdec(substr($data,$i,2))) DOES NOT WORK
     * @param string $hexString the hexified string
     * @return string $bin a binary string
     **/
    protected function _hex2bin($hexString)
    {
        //echo "<br>_hex2bin($hexString)";
        $BinStr = '';

        $hexLength = strlen($hexString);
        // only hex numbers is allowed
        if ($hexLength % 2 != 0 || preg_match("/[^\da-fA-F]/", $hexString)) return FALSE;


        //Loop through the input and convert it
        for ($i = 0; $i < $hexLength; $i += 2)
            $BinStr .= '%' . substr($hexString, $i, 2);


        // Raw url-decode and return the result
        return rawurldecode($BinStr);//chr(hexdec())
    }


    /**
     *Encodes a binary string to its hexadecimal representation
     *
     * @internal  dechex(ord($str{$i})); is buggy because for hex value of 0-15 heading 0 is missing! Using sprintf() to get it right.
     * @param string $str a binary string
     * @return string $hex the hexified string
     **/
    protected function _bin2hex($str)
    {
        //----------------------
	$hex = bin2hex($str);
	/*$hex = "";
        $i = 0;
        do {
            $hex .= sprintf("%02X", ord($str[$i]));
            $i++;
        } while ($i < strlen($str));*/
        return $hex;
    }


    /**
     * Extracts the map object for the xref table
     * @note PDF lines should have been previouly been parsed to make this work
     * @return array a map that holds the xrefstart infos and values
     */
    protected function get_xref_table()
    {
        //------------------------
        return $this->value_entries['$_XREF_$'];
    }

    /**
     * Extracts the offset of the xref table
     * @note PDF lines should have been previouly been parsed to make this work
     * @return int the xrefstart value
     */
    protected function get_xref_start()
    {
        //------------------------
        return $this->value_entries['$_XREF_$']["infos"]["start"]["pointer"];
    }


    /**
     * Extracts the line where the offset of the xref table is stored
     * @note PDF lines should have been previouly been parsed to make this work
     * @return int the wished line number
     */
    protected function get_xref_start_line()
    {
        //-------------------------------
        return $this->value_entries['$_XREF_$']["infos"]["start"]["line"];
    }

    /**
     * Calculates the offset of the xref table
     *
     * @return int the wished xrefstart offset value
     */
    protected function get_xref_start_value()
    {
        //-------------------------------
        $size_shift = $this->shift;
        $xref_start = $this->get_xref_start();
        return $xref_start + $size_shift;
    }


    /**
     * Read the offset of the xref table directly from file content
     *
     * @note content has been previously been defined in $this->buffer
     * @param int $object_id an object id, a integer value starting from 1
     * @return int the wished xrefstart offset value
     */
    protected function read_xref_start_value()
    {
        //------------------------------
        $buffer = $this->get_buffer();
        $chunks = preg_split('/\bxref\b/', $buffer, -1, PREG_SPLIT_OFFSET_CAPTURE);
        return intval($chunks[1][1]) - 4; //-4 , relative to end of xref
    }


    /**
     * Calculates the new offset/xref for this object id by applying the offset_shift due to value changes
     *
     * @note uses internally precalculated $offsets,$positions and $shifts
     * @param int $object_id an object id, a integer value starting from 1
     * @return int the wished offset
     */
    protected function get_offset_object_value($object_id)
    {
        //--------------------------------------------

        //Static is to keep forever...
        static $offsets = null;
        static $positions = null;
        static $shifts = null;

        if (is_null($offsets)) { //...variables content set once. This is the beauty of php :)

            //!NOTE: xref table is ordered by object id (position's object is not defined linearly in the pdf !)
            $positions = $this->_get_positions_ordered();
            //Makes it 0 indexed as object id starts from 1 and positions starts from 0
            $offsets = $this->_get_offsets_starting_from_zero();
            //Shifts are already 0 indexed, don't change.
            $shifts = $this->shifts;
        }

        $p = isset($positions[$object_id]) ? $positions[$object_id] : "";
        $offset = isset($offsets[$p]) ? $offsets[$p] : "";
        $shift = isset($shifts[$p]) ? $shifts[$p] : ""; //size shift of the object due to value changes
        return $offset + $shift;
    }


    /**
     * Reads the offset of the xref table directly from file content
     *
     * @note content has been previously been defined in $this->buffer
     * @param int $object_id an object id, a integer value starting from 1
     * @return int the wished offset
     */
    protected function read_offset_object_value($object_id)
    {
        //------------------------------
        $buffer = $this->buffer;
        $previous_object_footer = '';//'endobj' or comment;
        $object_header = $previous_object_footer . '\n' . $object_id . ' 0 obj';
        $chars = preg_split('/' . $object_header . '/', $buffer, -1, PREG_SPLIT_OFFSET_CAPTURE);
        $offset = intval($chars[1][1]) - strlen($object_header) + strlen($previous_object_footer) + 2;
        return $offset;
    }


    /**
     * Fix the offset of the xref table
     *
     */
    protected function fix_xref_start()
    {
        //-------------------------

        $pdf_entries =& $this->pdf_entries;
        $verbose_fix = ($this->verbose && ($this->verbose_level > 1));
        $calculate_xrefstart_value = ((!$this->safe_mode) || $this->check_mode);
        $extract_xrefstart_value_from_file = ($this->safe_mode || $this->check_mode);

        if ($calculate_xrefstart_value) {
            $xref_start_value_calculated = $this->get_xref_start_value(); //get computed value from old one
            if (!$this->safe_mode) $xref_start_value = $xref_start_value_calculated;
        }

        if ($extract_xrefstart_value_from_file) {
            $xref_start_value_safe = $this->read_xref_start_value();//read direct from new file content
            if ($this->safe_mode) $xref_start_value = $xref_start_value_safe;
        }

        if ($this->check_mode) { //Compared calculated value with position value read direct from file
            if ($xref_start_value_calculated != $xref_start_value_safe) {
                if ($verbose_fix) echo "<br>xrefstart's value must be $xref_start_value_safe calculated is $xref_start_value_calculated.Don't worry, FPDFM-merge will fix it for you.<br>";
                $xref_start_value = $xref_start_value_safe; //Overrides with the good value
                if ($this->halt_mode)
                    $this->Error("Halt on error mode enabled, aborting. Use \$pdf->set_modes('halt',false); to disable this mode and go further fixing corrupted pdf.");
            } else {
                if ($verbose_fix) echo "<br>xrefstart's value for the file is correct and vaults <b>$xref_start_value</b>";
            }
        }

        //updates xrefstart's value
        $xref_start_line = $this->get_xref_start_line();
        $pdf_entries[$xref_start_line] = "$xref_start_value";
    }

    /**
     * Get the offsets table 0 indexed
     *
     * @return array $offsets
     */
    protected function _get_offsets_starting_from_zero()
    {
        //-------------------------------------------
        $offsets = $this->offsets;
        return array_values($offsets);
    }

    /**
     * Sorts the position array by key
     *
     * @return array $positions the ordered positions
     */
    protected function _get_positions_ordered()
    {
        //--------------------------------
        $positions = $this->positions;
        ksort($positions);
        return $positions;
    }

    /**
     * Fix the xref table by rebuilding its offsets entries
     *
     */
    protected function fix_xref_table()
    {
        //------------------------

        $xref_table = $this->get_xref_table();
        $xLen = $xref_table["infos"]["count"];
        $pdf_entries =& $this->pdf_entries;

        //Do some checks
        $offsets = $this->offsets;
        //$offsets=array_values($offsets);
        $oLen = count($offsets);


        if ($xLen == $oLen) { //...to rectify xref entries

            //jump over len and header, this is the first entry with n
            $first_xref_entry_line = $xref_table["infos"]["line"] + 3;

            //echo "xREF:{$pdf_entries[$first_xref_entry_line]}";

            //!NOTE: xref table is ordered by object id (position's object is not defined linearly in the pdf !)
            //$positions=$this->positions;
            //ksort($positions);
            $verbose_fix = ($this->verbose && ($this->verbose > 1));
            $calculate_offset_value = ((!$this->safe_mode) || $this->check_mode);
            $extract_offset_value_from_file = ($this->safe_mode || $this->check_mode);

            //Get new file content (ie with values changed)
            $this->buffer = $this->get_buffer();

            for ($i = 0; $i < $xLen; $i++) {

                $obj_id = $i + 1;

                //Try two way to retrieve xref offset value of an object of the given id

                if ($calculate_offset_value) {
                    $offset_value_calculated = $this->get_offset_object_value($obj_id);;
                    if (!$this->safe_mode) $offset_value = $offset_value_calculated;
                }

                if ($extract_offset_value_from_file) {
                    $offset_value_read = $this->read_offset_object_value($obj_id);
                    if ($this->safe_mode) $offset_value = $offset_value_read;
                }

                if ($this->check_mode) {
                    if ($offset_value_calculated != $offset_value_read) {
                        if ($verbose_fix) echo "<br>Offset for object $obj_id read is <b>$offset_value_read</b>, calculated $offset_value_calculated";
                        $offset_value = $offset_value_read; //overrides to fix bad values
                        if ($this->halt_mode) $this->Error("<br>Offset for object $obj_id read is <b>$offset_value_read</b>, calculated $offset_value_calculated");
                    } else {
                        if ($verbose_fix) echo "<br>Offset for object $obj_id is correct and vaults <b>$offset_value</b>";
                    }
                }
                $pdf_entries[$first_xref_entry_line + $i] = sprintf('%010d 00000 n ', $offset_value);
            }

        } else {
            //Congratulations you won the corrupted Error Prize
            $this->Error("Number of objects ($oLen) differs with number of xrefs ($xLen), something , pdf xref table is corrupted :(");
        }


    }


    /**
     * Applies a shift offset $shift from the object whose id is given as param
     *
     * @note offset shift will affect the next objects taking into accound the order they appear in the file
     * @param int object_id the id whose size shift has changed
     * @param int offset_shift the shift value to use
     */
    public function apply_offset_shift_from_object($object_id, $offset_shift)
    {
        //---------------------------------------------------------
        //get the position of object
        $object_pos = $this->positions[$object_id];
        //get the next object position
        $next_object_pos = $object_pos + 1;
        //Applies offset change to next following objects
        $this->_apply_offset_shift($next_object_pos, $offset_shift);
    }

    /**
     * Applies a shift offset $shift starting at the index $from to the shifts array
     *
     * @param int from  the index to start apply the shift
     * @param int shift the shift value to use
     */
    protected function _apply_offset_shift($from, $shift)
    {
        //------------------------------------------
        $offsets =& $this->shifts;
        $params = array($from, $shift);

        foreach ($offsets as $key => $value) {
            if ($key >= $from) {
                $offset = $offsets[$key] + $shift;
                $offsets[$key] = $offset;
            }
        }

    }

    /**
     * Decodes a PDF value according to the encoding
     *
     * @param string $encoding the encoding to use for decoding the value, only 'hex' is supported
     * @param string value a value to decode
     * @return string the value decoded
     */
    public function decodeValue($encoding, $value)
    {
        //----------------------------------------------
        //echo "Decoding $encoding value($value)";
        if ($encoding == "hex")
            $value = $this->pdf_decode_field_value($value);
        return $value;
    }

    /**
     *Retrieve the list of supported filters
     *
     * @param String $sep a separator to merge filter names, default is '|'
     * @return String the suported filters
     **/
    public function getFilters($sep = "|")
    {
        return implode($sep, array(
            'ASCII85Decode',
            'ASCIIHexDecode',
            'FlateDecode',
            'LZWDecode',
            'Standard'
        ));
    }


    /**
     *Get a filter by name
     *
     * @param name a string matching one of the supported default filters (marked with +)        *
     *Without parameters:
     *+    ASCIIHexDecode : Decodes data encoded in an ASCII hexadecimal representation, reproducing the original binary data.
     *+    ASCII85Decode  : Decodes data encoded in an ASCII base-85 representation, reproducing the original binary data.
     *    RunLengthDecode : Decompresses data encoded using a byte-oriented run-length encoding algorithm, reproducing the original text or binary data (typically monochrome image data, or any data that contains frequent long runs of a single byte value).
     *    JPXDecode : (PDF 1.5) Decompresses data encoded using the wavelet-based JPEG2000 standard, reproducing the original image data.
     *With parameter(s):
     *+  LZWDecode      : Decompresses data encoded using the LZW (Lempel-Ziv-Welch) adaptive compression method, reproducing the original text or binary data.
     *+    FlateDecode (PDF�1.2): Decompresses data encoded using the zlib/deflate compression method, reproducing the original text or binary data.
     *   CCITTFaxDecode : Decompresses data encoded using the CCITT facsimile standard, reproducing the original data (typically monochrome image data at 1 bit per pixel).
     *   JBIG2Decode (PDF�1.4) :Decompresses data encoded using the JBIG2 standard, reproducing the original monochrome (1 bit per pixel) image data (or an approximation of that data).
     *   DCTDecode : Decompresses data encoded using a DCT (discrete cosine transform) technique based on the JPEG standard, reproducing image sample data that approximates the original data.
     *    Crypt (PDF 1.5) :Decrypts data encrypted by a security handler, reproducing the data as it was before encryption.
     * @return the wished filter class to access the stream
     **/
    public function getFilter($name)
    {
        //---------------------

        switch ($name) {
            case "LZWDecode":
                $filter = new FilterLZW();
                break;
            case "ASCIIHexDecode":
                $filter = new FilterASCIIHex();
                break;
            case "ASCII85Decode":
                $filter = new FilterASCII85();
                break;
            case "FlateDecode":
                $filter = new FilterFlate();
                break;
            case "Standard": //Raw
                $filter = new FilterStandard();
                break;
            default:
                $this->Error("getFilter cannot open stream of object because filter '{$name}' is not supported, sorry.");
        }


        return $filter;
    }


    //========= Stream manipulation stuff (alpha, not used by now!) ================

    /**
     * Detect if the stream has a textual content
     *
     * @param string $stream the string content of the stream
     * @return boolean
     */
    public function is_text_stream($stream_content)
    {
        //--------------------------------------
        return preg_match("/(\s*Td\s+[\<\(])([^\>\)]+)([\>\)]\s+Tj)/", $stream_content);
    }

    /**
     * changes the text value of a text stream
     *
     * @param array $stream the stream defintion retrieved during PDF parsing
     * @param string $value the new text value
     */
    public function change_stream_value($stream, $value)
    {
        //--------------------------------------------

        $entries =& $this->pdf_entries;

        $verbose_parsing = ($this->verbose && ($this->verbose_level > 3));

        if ($is_text_stream) {

            $OldLen = $stream["length"]["value"];
            $lMin = $stream["start"];
            $lMax = $stream["end"];

            $stream_content = $this->_set_text_value($stream_content, $value);
            $NewLen = strlen($stream_content);

            for ($l = $lMin; $l <= $lMax; $l++) {

                if ($l == $lMin) {
                    $entries[$lMin] = $stream_content;

                    //Update the length
                    $stream_def_line = $stream["length"]["line"];
                    $stream_def = $entries[$stream_def_line];

                    $stream_def = preg_replace("/\/Length\s*(\d+)/", '/Length ' . $NewLen, $stream_def);

                    $entries[$stream_def_line] = $stream_def;

                    //update the filter type...
                    $stream_def_line = $stream["filters"]["line"];
                    $stream_def = $entries[$stream_def_line];
                    if ($verbose_parsing) {
                        echo "<pre>";
                        echo htmlentities(print_r($stream_def, true));
                        echo "</pre>";
                    }

                    //...to filter Standard
                    $stream_def = preg_replace($this->streams_filter, '/Standard ', $stream_def);

                    $entries[$stream_def_line] = $stream_def;

                    //Update the shift
                    $size_shift = $NewLen - $OldLen;
                    $this->apply_offset_shift_from_object($obj, $size_shift);

                } else if ($lmin != $lMax) {
                    unset($entries[$l]);
                }
            }

            if ($verbose_parsing) {
                var_dump($stream_content);
            }
        }
    }

    /**
     * Overrides value between  Td and TJ, ommiting <>
     *
     * @note core method
     * @param array $stream the stream defintion retrieved during PDF parsing
     * @param string $value the new text value
     */
    protected function _set_text_value($stream, $value)
    {
        //---------------------------------------
        $chunks = preg_split("/(\s*Td\s+[\<\(])([^\>\)]+)([\>\)]\s+Tj)/", $stream, 0, PREG_SPLIT_DELIM_CAPTURE);
        $chunks[2] = $value;
        $stream = implode($chunks, '');
        return $stream;
    }


    //================================

    protected function _extract_pdf_definition_value($name, $line, &$match)
    {
        $value = preg_match(self::FPDM_REGEXPS["$name"], $line, $match);
        if (!$value) { //value is concatained with name: /name/value
            $value = preg_match("/" . preg_quote($name, '/') . "\/(\w+)/", $line, $match);
        }
        return $value;
    }

    protected function extract_pdf_definition_value($name, $line, &$match)
    {
        if (array_key_exists($name, self::FPDM_REGEXPS)) {
            $value = $this->_extract_pdf_definition_value($name, $line, $match);
        } else
            $this->Error("extract_pdf_definition_value() does not support definition '$name'");

        return $value;
    }


    /**
     * Parses the lines entries of a PDF
     *
     * @param array $lines the FDF content as an array of lines
     * @return integer the number of lines the PDF has
     */
    public function parsePDFEntries(&$lines)
    {
        //--------------------------------

        $entries =& $this->pdf_entries;

        $CountLines = count($entries);

        $Counter = 0;
        $obj = 0; //this is an invalid object id, we use it to know if we are into an object
        $type = '';
        $subtype = '';
        $name = '';
        $value = '';
        $default_maxLen = 0; //No limit
        $default_tooltip_line = 0; //Tooltip is optional as it may not be defined
        $xref_table = 0;
        $trailer_table = 0;
        $n = 0; //Position of an object, in the order it is declared in the pdf file
        $stream = array();
        $id_def = false; //true when parsing/decoding trailer ID
        $id_single_line_def = false; //true when the two ID chunks are one the same line
        $id_multi_line_def = false; //true or OpenOffice 3.2
        $creator = '';
        $producer = '';
        $creationDate = '';

        $verbose_parsing = ($this->verbose && ($this->verbose_level > 3));
        $verbose_decoding = ($this->verbose && ($this->verbose_level > 4));

        if ($this->verbose) $this->dumpContent("Starting to parse $CountLines entries", "PDF parse");

        while ($Counter < $CountLines) {

            $CurLine = $entries[$Counter];

            if ($verbose_parsing) $this->dumpContent($CurLine, "====Parsing Line($Counter)");
            if (!$xref_table) {

                //Header of an object?
                if (preg_match("/^(\d+) (\d+) obj/", $CurLine, $match)) {
                    $obj = intval($match[1]);
                    $this->offsets[$obj] = $this->pointer;
                    $this->positions[$obj] = $n;
                    $this->shifts[$n] = 0;
                    $n++;
                    if ($verbose_parsing) $this->dumpContent($CurLine, "====Opening object($obj) at line $Counter");
                    $object = array();
                    $object["values"] = array();
                    $object["constraints"] = array();
                    $object["constraints"]["maxlen"] = $default_maxLen;
                    $object["infos"] = array();
                    $object["infos"]["object"] = intval($obj);
                    $object["infos"]["tooltip"] = $default_tooltip_line;

                } else {

                    //Object has been opened
                    if ($obj) {

                        //Footer of an object?
                        if (preg_match("/endobj/", $CurLine, $match)) {
                            if ($verbose_parsing) $this->dumpContent("", "====Closing object($obj) at line $Counter");

                            //We process fields here, save only Annotations texts that are supported by now
                            if (($type == 'Annot') && ($subtype == "Widget")) {

                                if ($name != '') {
                                    $lines["$name"] = $object;
                                    if ($verbose_parsing) $this->dumpContent("$type $subtype (obj id=$obj) is a text annotation of name '$name', saves it.");
                                }//else
//										$this->Error("$type $subtype (obj id=$obj) is a text annotation without a name, this cannot be.");


                                $values = $object["values"];

                                //Sanity values checks, watchdog.
//									if(!array_key_exists("current",$values)) $this->Error("Cannot find value (/V) for field $name");
//									if(!array_key_exists("default",$values)) $this->Error("Cannot find default value (/DV) for field $name");

                            } else
                                if ($verbose_parsing) $this->dumpContent("Object $type $subtype (obj id=$obj) is not supported");


                            $object = null;
                            $obj = 0;
                            $type = '';
                            $subtype = '';
                            $name = '';
                            $value = '';
                            $maxLen = 0;

                        } else {

                            if (preg_match("/\/Length\s*(\d+)/", $CurLine, $match)) {
                                $stream["length"] = array("line" => $Counter, "value" => $match[1]);
                                $stream["start"] = 0;
                                $stream["end"] = 0;
                                $stream["content"] = '';
                                if ($verbose_parsing) $this->dumpContent($CurLine, "->Stream filter length definition(<font color=\"darkorange\">{$match[1]}</font>) for object($obj) at line $Counter");
                            }

                            //Handles single filter /Filter /filter_type as well as well as filter chains such as /Filter [/filter_type1 /filter_type2 .../filter_typeN]
                            if (preg_match_all($this->streams_filter, $CurLine, $matches)) {

                                //$this->dumpContent($this->streams_filter);
                                /*$stream_filter=$match[1];
                                $stream_filter=trim(preg_replace('/(<<|\/Length\s*\d+|>>)/', '', $stream_filter),' ');
                                $stream_filters=preg_split('/\s*\//',$stream_filter);
                                array_shift($stream_filters);*/
                                $stream_filters = $matches[2];
                                $stream["filters"] = array("line" => $Counter, "type" => $stream_filters);
                                if ($verbose_parsing) {
                                    //var_dump($stream_filters);
                                    $stream_filter = implode(" ", $stream_filters);
                                    $this->dumpContent($CurLine, "->Stream filter type definition(<font color=\"darkorange\">$stream_filter</font>) for object($obj) at line $Counter");
                                }
                            }

                            if (array_key_exists("length", $stream)) { //length is mandatory

                                if (preg_match("/\b(stream|endstream)\b/", $CurLine, $match)) {

                                    if (!array_key_exists("filters", $stream)) {//filter type is optional, if none is given, its standard

                                        $stream["filters"] = array("type" => array("Standard"));
                                        if ($verbose_parsing) {
                                            var_dump($stream);
                                            $this->dumpContent($CurLine, "->No stream filter type definition for object($obj) was found, setting it to '<font color=\"darkorange\">Standard</font>'");
                                        }
                                    }


                                    if ($match[1] == "stream") {
                                        if ($verbose_parsing) $this->dumpContent($CurLine, "->Opening stream for object($obj) at line $Counter");
                                        $stream["start"] = $Counter + 1;
                                    } else {
                                        $stream["end"] = $Counter - 1;

                                        $stream["content"] = implode("\n", array_slice($entries, $stream["start"], $stream["end"] - $stream["start"] + 1));


                                        $filters = $stream["filters"]["type"];
                                        $f = count($filters);
                                        $stream_content = $stream["content"];

                                        //var_dump($filters);

                                        //$filters_type=$filters["type"];

                                        //now process the stream, ie unpack it if needed
                                        //by decoding in the reverse order the streams have been encoded
                                        //This is done by applying decode using the filters in the order given by /Filter.
                                        foreach ($filters as $filter_name) {

                                            $stream_filter = $this->getFilter($filter_name);
                                            $stream_content = $stream_filter->decode($stream_content);
                                            if ($verbose_decoding) {
                                                echo "<br><font color=\"blue\"><u>Stream decoded using filter '<font color=\"darkorange\">$filter_name</font>'</u>:[<pre>";
                                                var_dump($stream_content); //todo : manipulate this content and adjust offsets.
                                                echo "</pre>]</font>";
                                            }
                                        }

                                        if ($verbose_parsing) {
                                            $this->dumpEntries($stream);

                                            echo "<font color=\"blue\">";
                                            if ($this->is_text_stream($stream_content)) {
                                                echo "<u>Stream text unfiltered</u>:[<pre>";
                                            } else {
                                                echo "<u>Stream unfiltered</u>:[<pre>";
                                            }
                                            var_dump($stream_content);
                                            echo "</pre>]</font>";
                                            $this->dumpContent($CurLine, "->Closing stream for object($obj) at line $Counter");
                                        }

                                        $stream = array();
                                    }
                                } else if ($stream["start"] > 0) {
                                    //stream content line that will be processed on endstream...
                                }

                            } else {

                                /*
                                Producer<FEFF004F00700065006E004F00660066006900630065002E006F0072006700200033002E0032>
                                /CreationDate (D:20101225151810+01'00')>>
                                */
                                if (($creator == '') && preg_match("/\/Creator\<([^\>]+)\>/", $CurLine, $values)) {
                                    $creator = $this->decodeValue("hex", $values[1]);
                                    if ($verbose_parsing) echo("Creator read ($creator)");
                                    $this->info["Creator"] = $creator;
                                }

                                if (($producer == '') && preg_match("/\/Producer\<([^\>]+)\>/", $CurLine, $values)) {
                                    $producer = $this->decodeValue("hex", $values[1]);
                                    if ($verbose_parsing) echo("Producer read ($producer)");
                                    $this->info["Producer"] = $producer;
                                }

                                if (($creationDate == '') && preg_match("/\/CreationDate\(([^\)]+)\)/", $CurLine, $values)) {
                                    $creationDate = $values[1];
                                    if ($verbose_parsing) echo("Creation date read ($creationDate)");
                                    $this->info["CreationDate"] = $creationDate;
                                }

                                //=== DEFINITION ====
                                //preg_match("/^\/Type\s+\/(\w+)$/",$CurLine,$match)
                                $match = array();
                                if (($type == '') || ($subtype == '') || ($name == "")) {

                                    if (($type == '') && $this->extract_pdf_definition_value("/Type", $CurLine, $match)) {

                                        if ($match[1] != 'Border') {
                                            $type = $match[1];
                                            if ($verbose_parsing) echo("<br>Object's type is '<i>$type</i>'");
                                        }

                                    }
                                    if (($subtype == '') && $this->extract_pdf_definition_value("/Subtype", $CurLine, $match)) {

                                        $subtype = $match[1];
                                        if ($verbose_parsing) echo("<br>Object's subType is '<i>$subtype</i>'");

                                    }
                                    if (($name == "") && preg_match("/^\/T\s?\((.+)\)\s*$/", $this->_protectContentValues($CurLine), $match)) {

                                        $name = $this->_unprotectContentValues($match[1]);
                                        if ($verbose_parsing) echo("Object's name is '<i>$name</i>'");

                                        $object["infos"]["name"] = $name; //Keep a track
                                        $object["infos"]["name_line"] = $Counter;

                                        //$this->dumpContent(" Name [$name]");
                                    }

                                }// else {

                                //=== CONTENT ====

                                //$this->dumpContent($CurLine);
                                //=== Now, start the serious work , read DV, V Values and eventually TU
                                //note if(preg_match_all("/^\/(V|DV)\s+(\<|\))([^\)\>]+)(\)|\>)/",$CurLine,$matches)) {
                                //do not work as all is encoded on the same line...
                                if (preg_match("/^\/(V|DV|TU)\s+([\<\(])/", $CurLine, $def)) {

                                    //get an human readable format of value type and encoding

                                    if ($def[1] == "TU") {
                                        $valuetype = "info";
                                        $object["infos"]["tooltip"] = $Counter;
                                    } else {
                                        $valuetype = ($def[1] == "DV") ? "default" : "current";
                                        $object["values"]["$valuetype"] = $Counter; //Set a marker to process lately
                                    }

                                    $encoding = ($def[2] == "<") ? "hex" : "plain";

                                    if (preg_match("/^\/(V|DV|TU)\s+(\<|\)|\()([^\)\>]*)(\)|\>\))/", $CurLine, $values)) {
                                        $value = $values[3];
                                        $value = $this->decodeValue($encoding, $value);
                                    } else
                                        $value = '';

                                    if ($verbose_parsing)
                                        $this->dumpContent("$type $subtype (obj id=$obj) has $encoding $valuetype value [$value] at line $Counter");


                                } else if (preg_match("/^\/MaxLen\s+(\d+)/", $CurLine, $values)) {
                                    $maxLen = $values[1];
                                    $object["constraints"]["maxlen"] = intval($maxLen);
                                } else
                                    if ($verbose_parsing) echo("WARNING: definition ignored");

                                if (substr($CurLine, 0, 7) == '/Fields' && !$this->needAppearancesTrue) {
                                    $CurLine = '/NeedAppearances true ' . $CurLine;
                                    $entries[$Counter] = $CurLine;
                                }

                                //TODO: Fetch the XObject..and change Td <> Tj
                                /*										if(preg_match("/^\/AP/",$CurLine,$values)) {
                                                                            //die("stop");
                                                                            $CurLine=''; //clear link to Xobject
                                                                            $entries[$Counter]=$CurLine;
                                                                        }*/

//									}

                            }


                        }

                    }

                    //~~~~~Xref table header? ~~~~~~
                    if (preg_match("/\bxref\b/", $CurLine, $match)) {

                        $xref_table = 1;
                        if ($verbose_parsing) $this->dumpContent("->Starting xref table at line $Counter:[$CurLine]");
                        $lines['$_XREF_$'] = array();
                        $lines['$_XREF_$']["entries"] = array();
                        $lines['$_XREF_$']["infos"] = array();
                        $lines['$_XREF_$']["infos"]["line"] = $Counter;
                        $lines['$_XREF_$']["infos"]["start"] = array();
                        $start_pointer = $this->pointer + strpos($CurLine, "xref"); //HACK for PDFcreator 1.0.0
                        $lines['$_XREF_$']["infos"]["start"]["pointer"] = $start_pointer;
                    }

                }
                $obj_header = false;
            } else {
                //We are inside the xref table
                //$this->dumpContent($CurLine,"");
                $xref_table = $xref_table + 1;
                switch ($xref_table) {
                    case 2:
                        if (preg_match("/^(\d+) (\d+)/", $CurLine, $match)) {
                            $refs_count = intval($match[2]);//xref_table length+1 (includes this line)
                            $lines['$_XREF_$']["infos"]["count"] = $refs_count - 1;
                            if ($verbose_parsing) $this->dumpContent("Xref table length is $refs_count");
                        } else
                            if ($verbose_parsing) $this->dumpContent("WARNING: Xref table length ignored!");
                        break;
                    case 3:
                        //Should be 0000000000 65535 f
                        if ($verbose_parsing) $this->dumpContent("this is Xref table header, should be 0000000000 65535 f ");
                        break;
                    default:
                        //xref entries
                        if ($refs_count > 0) {
                            $xref = $xref_table - 3;

                            if ($refs_count == 1) {//Last one , due to the shift, is the trailer
                                if (!preg_match("/^trailer/", $CurLine)) //if not, Houston we have a problem
                                    $this->Error("xref_table length corrupted?: Trailer not found at expected!");
                                else
                                    $trailer_table = 1;
                            } else {
                                $lines['$_XREF_$']["entries"][$xref] = $CurLine;
                                if ($verbose_parsing) $this->dumpContent("Xref table entry for object $xref found.");
                            }
                            $refs_count--;
                        } else { //We are inside the trailer

                            if ($trailer_table == 1) { //should be <<

                                if (trim($CurLine) != '') { //HACK: PDFCreator Version 1.0.0 has an extra CR after trailer
                                    if (!preg_match("/<</", $CurLine, $match))
                                        $this->Error("trailer_table corrupted?; missing start delimiter << ");
                                    $trailer_table++;
                                }


                            } else if (($trailer_table > 0) && ((!is_null($id_def)) || preg_match("/^\/(Size|Root|Info|ID|DocChecksum)/", $CurLine, $match))) {

                                //Value can be extracted using (\d+|\[[^\]]+\])
                                if (preg_match("/\/Size (\d+)/", $CurLine, $match)) {
                                    //Seems to match with xref entries count..
                                    $size_read = $match[1];
                                    $this->info["size"] = $size_read;
                                    if ($verbose_parsing) $this->dumpContent("Size read ($size_read) for pdf found.");
                                }

                                if (preg_match("/^\/ID\s*\[\s*<([\da-fA-F]+)/", $CurLine, $match)) {
                                    $oid = $match[1];
                                    $id_def = true;
                                    if ($verbose_parsing) $this->dumpContent("ID chunk one ($oid) for pdf found.");

                                    //Determines if the ID definition is one line...
                                    if (preg_match("/\>\s?\</", $CurLine, $match))
                                        $id_single_line_def = true;

                                }

                                if ($id_def) {//we are inside the ID definition
                                    if ($id_single_line_def || $id_multi_line_def) {
                                        //decode the second ID chunk
                                        if (preg_match("/([\da-fA-F]+)>.*$/", $CurLine, $match)) {
                                            $tid = $match[1];
                                            $this->info["ID"] = array($oid, $tid);
                                            if ($verbose_parsing) $this->dumpContent("ID chunk two ($tid) for pdf found.");
                                            $id_def = false;
                                        } else
                                            $this->Error("trailer_table corrupted?; ID chunk two can not be decoded ");
                                    } else
                                        $id_multi_line_def = true;
                                }

                                if (preg_match("/^\/DocChecksum \/([\da-fA-F]+)/", $CurLine, $match)) {
                                    $checksum = $match[1];
                                    $this->info["checksum"] = $checksum;
                                    if ($verbose_parsing) $this->dumpContent("Checksum read ($checksum) for pdf found.");
                                }

                                if (preg_match("/>>/", $CurLine, $match))
                                    $trailer_table = -1;//negative value: expects startxref to follow


                            } else {

                                switch ($trailer_table) {
                                    case -1://startxref
                                        if (!preg_match("/^startxref/", $CurLine, $match))
                                            $this->Error("startxref tag expected, read $CurLine");
                                        break;
                                    case -2://startxref's value
                                        if (preg_match("/^(\d+)/", $CurLine, $match)) {
                                            $lines['$_XREF_$']["infos"]["start"]["value"] = intval($match[1]);
                                            $lines['$_XREF_$']["infos"]["start"]["line"] = $Counter;
                                        } else
                                            $this->Error("startxref value expected, read $CurLine");
                                        break;
                                    default://%%EOF
                                }
                                $trailer_table--;

                            }

                        }
                }

            }

            $this->pointer = $this->pointer + strlen($CurLine) + 1; //+1 due to \n
            $Counter++;
        }

        if ($this->verbose) {

            $refs = (array_key_exists('$_XREF_$', $lines)) ? $lines['$_XREF_$']["infos"]["count"] : 0;
            if ($refs) {
                $this->dumpContent("PDF parse retrieved $refs refs");
            } else {
                $this->dumpContent("PDF parse retrieved no refs, seems the xref table is broken or inacessible, this is bad!");
            }
        }

        return count($lines);
    }

    /**
     * Protect ( ) that may be in value or names
     *
     * @param string $content the FDF content to protect values
     * @return string the content protected
     */
    protected function _protectContentValues($content)
    {
        //-------------------------------------------------
        $content = str_replace("\\(", "$@#", $content);
        $content = str_replace("\\)", "#@$", $content);
        return $content;
    }

    /**
     * Unprotect ( ) that may be in value or names
     *
     * @param string $content the FDF content with protected values
     * @return string the content unprotected
     */
    protected function _unprotectContentValues($content)
    {
        //--------------------------------------------------
        $content = str_replace("$@#", "\\(", $content);
        $content = str_replace("#@$", "\\)", $content);
        $content = stripcslashes($content);
        return $content;
    }

    /**
     * Parses the content of a FDF file and saved extracted field data
     *
     * @return array $fields the data of the fields parsed
     */
    public function parseFDFContent()
    {
        //-------------------------

        $content = $this->fdf_content;
        $content = $this->_protectContentValues($content);//protect ( ) that may be in value or names...

        if ($this->verbose) $this->dumpEntries($content, "FDF parse");

        //..so that this regexp can do its job without annoyances
        if (preg_match_all("/(T|V)\s*\(([^\)]+)\)\s*\/(T|V)\s*\(([^\)]+)\)/", $content, $matches, PREG_PATTERN_ORDER)) {

            $fMax = count($matches[0]);
            $fields = array();
            for ($f = 0; $f < $fMax; $f++) {
                $value = '';
                $name = '';
                if ($matches[1][$f] == "V") {
                    $value = $matches[2][$f];
                    if ($matches[3][$f] == "T")
                        $name = $matches[4][$f];
                    else
                        $this->Error("Field $f ignored , incomplete field declaration, name is expected");
                } else {
                    if ($matches[1][$f] == "T") {
                        $name = $matches[2][$f];
                        if ($matches[3][$f] == "V")
                            $value = $matches[4][$f];
                        else
                            $this->Error("Field $f ignored , incomplete field declaration, value is expected");
                    } else
                        $this->Error("Field $f ignored , Invalid field keys ({$matches[0][$f]})");
                }
                if ($name != '') {
                    if (array_key_exists($name, $fields))
                        $this->Error("Field $f ignored , already defined");
                    else {
                        $name = $this->_unprotectContentValues($name);
                        $value = $this->_unprotectContentValues($value);
                        if ($this->verbose)
                            $this->dumpContent("FDF field [$name] has its value set to \"$value\"");
                        $fields[$name] = $value;
                    }
                } else
                    $this->Error("Field $f ignored , no name");

            }
        } else
            if ($this->verbose) $this->dumpContent($fields, "FDF has no fields", false);

        if ($this->verbose) $this->dumpContent($fields, "FDF parsed", false);

        return $fields;
    }


    /**
     * Close the opened file
     */
    public function closeFile()
    {
        //--------------------
        if (isset($this->f) && is_resource($this->f)) {
            fclose($this->f);
            unset($this->f);
        }
    }

    /**
     * Print Error and die
     *
     * @param string $msg Error-Message
     */
    protected function Error($msg)
    {
        throw new \Exception('FPDF-Merge Error: ' . $msg);
    }

}

?>