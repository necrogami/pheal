<?php
/*
 MIT License
 Copyright (c) 2010 Peter Petermann, Daniel Hoffend

 Permission is hereby granted, free of charge, to any person
 obtaining a copy of this software and associated documentation
 files (the "Software"), to deal in the Software without
 restriction, including without limitation the rights to use,
 copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the
 Software is furnished to do so, subject to the following
 conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 OTHER DEALINGS IN THE SOFTWARE.
*/
/**
 * Simple filearchive for the xml
 */
class Pheal_File_Archive implements Pheal_Archive_Interface
{
    /**
     * path where to store the xml
     * @var string
     */
    protected $basepath;

    /**
     * various options for the filecache
     * valid keys are: delimiter, umask, umask_directory
     * @var array
     */
    protected $options = array(
        'delimiter' => ':',
        'umask' => 0666,
        'umask_directory' => 0777
    );

    /**
     * construct PhealFileCache,
     * @param string $basepath optional string on where to store files, defaults to the current/users/home/.pheal/cache/
     * @param array $options optional config array, valid keys are: delimiter, umask, umask_directory
     */
    public function __construct($basepath = false, $options = array())
    {
        if(!$basepath)
            $basepath = getenv('HOME'). "/.pheal/archive/";
        $this->basepath = $basepath;

        // Windows systems don't allow : as part of the filename
        $this->options['delimiter'] = (strtoupper (substr(PHP_OS, 0,3)) == 'WIN') ? "#" : ":";

        // add options
        if(is_array($options) && count($options))
            $this->options = array_merge($this->options, $options);
    }

    /**
     * create a filename to use
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return string
     */
    protected function filename($userid, $apikey, $scope, $name, $args)
    {
        $argstr = "";
        foreach($args as $key => $val)
        {
            if(strlen($val) < 1)
                unset($args[$key]);
            elseif($key != 'userid' && $key != 'apikey')
                $argstr .= $key . $this->options['delimiter'] . $val . $this->options['delimiter'];
        }
        $argstr = substr($argstr, 0, -1);
        $filename = "Request_" . gmdate('Ymd-His') . ($argstr ? "_" . $argstr : "") . ".xml";
        $filepath = $this->basepath . gmdate("Y-m-d") . "/" . ($userid ? "$userid/$apikey/$scope/$name/" : "public/public/$scope/$name/");
        if(!file_exists($filepath)) {
            $oldUmask = umask(0);
            mkdir($filepath, $this->options['umask_directory'], true);
            umask($oldUmask);
        }
        return $filepath . $filename;
    }

    /**
     * Save XML from cache
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @param string $xml
     */
    public function save($userid,$apikey,$scope,$name,$args,$xml) 
    {
        $filename= $this->filename($userid, $apikey, $scope, $name, $args);
        file_put_contents($filename, $xml);
        chmod($filename, $this->options['umask']);
    }
}
