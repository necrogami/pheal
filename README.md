# Pheal

Copyright (C) 2010 by Peter Petermann
All rights reserved.

Pheal is a port of EAAL to PHP
This is a modification of Peterman's version that is autoloaded with Zend_Loader_Autoloader

## WARNING
Pheal is not a stable release yet,
stuff might or might not work as expected

## LICENSE
Pheal is licensed under a MIT style license, see LICENSE.txt
for further information

## FEATURES
- does not need to change when EVE API changes

## REQUIREMENTS
- PHP 5.2 (might run on earlier versions, untested)


## INSTALLATION
1. `git clone git://github.com/necrogami/pheal.git` into your library folder
2. Add Pheal_ to you namespaces in Zend Autoloader.
3. Use Pheal

## USAGE

### Initialize the API Object
    $pheal = new Pheal_Pheal("myUserid", "myAPI key"[, "scope for request"]);
the scope is the one used for the API requests, ex. account/char/corp/eve/map/server see API Reference the scope can be changed during runtime and defaults to account

for public API's you can leave userID/apiKey empty.
    $pheal = new Pheal_Pheal();
    $pheal->scope = 'map';
or
    $pheal = new Pheal_Pheal(null, null, 'map');

### Request Information
    $result = $pheal>ApiPage();
this will return an Object of type Pheal_Result which then can be used to read the api result
If you want to access the raw http/xml result for whatever reason, you can just ask the xml 
attribute afterwords.
    $rawxml = $pheal->xml;

### Example 1, getting a list of characters on the account:
    $pheal = new Pheal_Pheal("myUserid", "myAPI key"[, "scope for request"]);

    $result = $pheal->Characters();
    foreach($result->characters as $character)
      echo $character->name;

### Example 2, getting the id for a given character name
    $pheal = new Pheal_Pheal("myUserid", "myAPI key"[, "scope for request"]);

    $pheal->scope = "eve";
    $result = $pheal->CharacterID(array("names" => "Peter Powers"));
    echo $result->characters[0]->characterID;

### Using the cache
Pheal comes with a simple file cache, to make use of this cache:
`Pheal_Config::getInstance()->cache = new Pheal_File_Cache("/path/to/cache/directory/");`
does the magic. if you don't give a path it defaults to $HOME/.pheal/cache

### Example 3, doing a cached request
    Pheal_Config::getInstance()->cache = new Pheal_File_Cache();
    $pheal = new Pheal_Pheal("myUserid", "myAPI key"[, "scope for request"]);

    $pheal->scope = "eve";
    $result = $pheal->CharacterID(array("names" => "Peter Powers"));
    echo $result->characters[0]->characterID;

now the request will first check if the xml is already in the cache, if it is still valid, and if so use the cached, only if the cache until of the saved file has expired, it will request again.

### Exceptions
Pheal throws an Exception of type Pheal_API_Exception (derived from Pheal_Exception)
whenever the EVE API returns an error, this exception has an attribute called "code"
which is the EVE APIs error code, and also contains the EVE API message as message.

    Pheal_Config::getInstance()->cache = new Pheal_File_Cache();
    $pheal = new Pheal_Pheal("myUserid", "myAPI key"[, "scope for request"]);
    try {
        $pheal->Killlog(array("characterID" => 12345));
    } catch(Pheal_Exception $e) {
        echo 'error: ' . $e->code . ' meesage: ' . $e->getMessage();
    }

### Archiving
If you wanna archive your api requests for future use, backups or possible feature 
additions you can add an archive handler that saves your api responses in a similar
way like the cache handler is doing it. Only non-error API responses are beeing cached.
The files are grouped by date and include the gmt timestamp.

Make sure that you've a cron job running that moves old archive folders into zip/tar/7z 
archives. Otherwise you end up with million xml files in your filesystem.

    Pheal_Config::getInstance()->cache = new Pheal_File_Cache();
    Pheal_Config::getInstance()->archive = new Pheal_Archive_Cache();
    $pheal = new Pheal(null, null, 'map');
    try {
        $pheal->Sovereignty();
    } catch(Pheal_Exception $e) {
        echo 'error: ' . $e->code . ' meesage: ' . $e->getMessage();
    }

### Logging
Pheal allows you to log all api calls that are requested from CCP's API Server. This
is useful for debugging and performance tracking (response times) of the API server.

The responseTime is being tracked. The API Key will be truncated to for better 
security. This can be turned of via the module options array. Pheal will use 2 log files.
One 'pheal_access.log' for successful calls and a 'pheal_error.log' for failed requests.

    Pheal_Config::getInstance()->log = new Pheal_File_Log();
    $pheal = new Pheal_Pheal(null, null, 'map');
    try {
        $pheal->Sovereignty();
    } catch(Pheal_Exception $e) {
        echo 'error: ' . $e->code . ' meesage: ' . $e->getMessage();
    }

### HTTP request options
There're 2 methods available for requesting the API information. Due to the some 
php or web hosting restrictions file_get_contents() isn't available for remote 
requests. You can choose between 'curl' and 'file'. Additionally you can set the 
http method (GET or POST) and set your custom useragent string so CCP can recognize
you while you're killing their API servers.

    Pheal_Config::getInstance()->http_method = 'curl';
    Pheal_Config::getInstance()->http_post = false;
    Pheal_Config::getInstance()->http_user_agent = 'my mighty api tool';
    Pheal_Config::getInstance()->http_interface_ip' = '1.2.3.4';
    Pheal_Config::getInstance()->http_timeout = 5;
    
## TODO
- more documentation
- more error handling

## LINKS
- [Necrogami](http://github.com/necrogami/pheal)
- [Github](http://github.com/ppetermann/pheal)
- [devedge](http://devedge.eu/project/pheal/)

## CONTACT
- Peter Petermann <ppetermann80@googlemail.com>
- Necrogami <djnecrogami@gmail.com> - For the Zend Version

## Contributors
- Daniel Hoffend (Wollari)

## ACKNOWLEDGEMENTS
- Pheal is based on [EAAL](http://github.com/ppetermann/eaal)
- Pheal is written in [PHP](http://php.net)
- Pheal is build for use of the [EVE Online](http://eveonline.com) API

