<?php

/**
 * Install/Upgrade/Backup/Restore MODX
 *
 * This script will help you install, upgrade, backup and restore MODX Revolution. There
 * already are some excellent scripts for this but they don't do all that I want.
 *
 * WARNING: I hope this script is as useful to you as it is to me. However, no guarantee
 * is made or implied. USE THIS SCRIPT AT YOUR OWN RISK!
 *
 *
 * Instructions
 * ------------
 * Place this file in the web root of your site.
 *
 * It is probably a good idea to rename this file to obscure it and make it easy for you to
 * associate it with the site it is used on. Don't leave it accessible on the server when
 * it is not needed.
 *
 * If your site is big and/or on a slow server some actions of this script may take a fair
 * amount of time. You may have to increase max_execution_time (default 10 min).
 *
 * Change the password and set the paths. Always use "Check paths" before you do anything
 * with this script! If the script finds an installation of MODX it will compare the paths
 * of the installation and the paths you configure here. Configured paths that doesn't
 * correspond to the installations paths are displayed in red.
 *
 * Backups are saved to the configured directory. It's advisible to set this outside of the
 * web root. The files in the zip are stored relative to the configured root. The database
 * file is stored in the root of the zip.
 * If you configure FTP and activate saveToRemote the file is also copied to the FTP server
 * with optional removal of the local file after successful copy.
 * By default this file, the core/cache dir, the backup dir and the session and manager_log
 * tables are excluded.
 *
 * The restore function looks in the backup dir for backup files and displays them. If you
 * activate restoreFromRemote it will list files on the FTP server instead.
 * Restoring the site will completely delete the current installation and replace it with
 * the backed up installation.
 *
 * The import function will take a zip file with the same paths as a backup file. Directories
 * outside of the installation must be specified in the includeInBackup config key.
 * This function may seem a bit redundant since the backup/restore function can be used to
 * import entire installations. It can, however, be used to import additional files to the site.
 * The zip file must be stored in the backup dir.
 * You have the option to delete the current installation if you want to make sure that no
 * old files are left on the server. The excludeOnDelete config key is used to exclude
 * directories or files from deletion.
 *
 * The update function checks for the current installation and preselects the distro and tries
 * to check for the latest version on modx.com/download. If you fill in the version manually
 * you should only write the numbering, ie "2.3.1". The script will not let you install an
 * older version than the one installed.
 * You have the option to delete the current installation if you want to make sure that no old
 * files are left on the server. The excludeOnDelete config key is used to specify the excluded
 * directories or files.
 * If the script cannot find the distro file in the backup dir it will download it from modx.com.
 * The download is often quite slow so if you are updating a lot of sites you can save a little
 * time by uploading it to the backup dir manually.
 * The update can be run in cli mode by setting the config key usecli to true.
 *
 * The install function works similarly to the update function but doesn't give the option of
 * deleting anything.
 * If you want to run the install in cli mode you have to set the xmldata config options
 * manually.
 *
 * The delete function will delete all files on the server. The backup dir and other dirs
 * above the web root is excluded (with the exception of the core dir if it is moved above
 * the web root).
 * The database is not touched.
 *
 *
 * Inspiration for this script
 *   - https://github.com/craftsmancoding/modx_utils/blob/master/installmodx.php
 *   - https://github.com/evolution-cms/installer
 *
 *
 * AUTHOR: Pontus Ågren (Pont)
 * VERSION: 2017-08-30
 *
 */


ini_set('max_execution_time', 600);
ini_set('memory_limit', '256M');

if ($_SERVER['HTTP_HOST'] != 'localhost') {

    //-------------------------------------------------------------------------
    //  Configuration for LIVE site
    //-------------------------------------------------------------------------

    // Change to a personal password
    define('PASSWORD', 'change to a personal password');

    $config = array(

        /**
         * You can reuse a defined path by prefixing its name with a #, ex '#base_path/core'.
         * The path must have been defined before you can reuse it.
         *
         * All paths are normalized so you can use '#base_path/..' to move upwards in the path.
         * However be very careful with this as you can wreck havoc on your server if you set
         * a path wrong. ALWAYS use "Check paths" before doing anything!
         */

        // The root of the installation
        'base_path' => __DIR__,

        // System paths
        'core_path' => '#base_path/core',
        'manager_path' => '#base_path/manager',
        'connectors_path' => '#base_path/connectors',
        'assets_path' => '#base_path/assets',

        // Path used when saving backups. It is also used to store downloaded distributions of MODX,
        // and various temporary files used by the script.
        // Should be above the web root.
        'backup_path' => '#base_path/../backup',

        // Directories to include in backup that is not part of MODX, for example media files stored
        // in a directory above the web root. These directories are not deleted on restore.
        'includeInBackup' => array(
            //'#base_path/../media'
        ),

        // Directories to exclude in backup, add more if you need to.
        'excludeInBackup' => array(
            __FILE__,           // This file
            '#backup_path',     // can be removed if not in core path or web root
            '#core_path/cache'  // optional
        ),

        // Directories and files to exclude from deletion when updating or importing a site.
        // Has no effect when restoring a site.
        'excludeOnDelete' => array(
            __FILE__,
            '#core_path/.htaccess',
            '#core_path/components',
            '#core_path/packages',
            '#core_path/config',
            '#assets_path',
            '#base_path/.htaccess',
            '#base_path/config.core.php',
            '#base_path/robots.txt',
            '#base_path/apple-touch-icon-precomposed.png',
            '#manager_path/.htaccess'
        ),

        // Tables to exclude from backup. Add and remove as you see fit. Do not use prefix. It is
        // included automatically from the config file.
        'excludeDB' => array(
            'session',
            'manager_log'
        ),

        // Backup website before updating?
        // If the website is large running backup and update in one go may exceed max_execution_time.
        'backupBeforeUpdate' => false, // boolean

        // Copy backup file to remote server?
        'saveToRemote' => false, // boolean

        // Remove local copy of backup file when saving to remote server?
        // saveToRemote must be set to true for this to work.
        'removeLocalCopy' => false, // boolean

        // Get listing of backup files and download backup file from remote server?
        'restoreFromRemote' => false, // boolean

        // Configuration for FTP server
        'ftp' => array(
            'server' => '',
            'username' => '',
            'password' => '',
            'passive_mode' => false, // boolean
            'remote_path' => ''
        ),

        // Attempt to run upgrade/install in cli mode?
        'usecli' => false, // boolean

        // Data to use when installing via cli. Also used to update config.inc.php and all
        // config.core.php files. This is useful if you want to restore a backup from the live
        // server on your local dev server and vice versa. In this way you can develop a site
        // locally (or a new version of it), back it up and then restore it on the live site without
        // having to do an update. Make sure that only the database keys differ between the local
        // and the live site. The relative paths must be the same.
        'xmldata' => array(
            'database_type' => 'mysql',
            'database_server' => 'database.server.com',
            'database' => 'modx',
            'database_user' => 'db_username',
            'database_password' => 'db_password',
            'database_connection_charset' => 'utf8',
            'database_charset' => 'utf8',
            'database_collation' => 'utf8_general_ci',
            'table_prefix' => 'modx_',
            'https_port' => '443',
            'http_host' => 'mysite.com',
            'cache_disabled' => 0,

            // Set this to 1 if you are using MODX from Git or extracted it from the full MODX
            // package to the server prior to installation.
            'inplace' => 0,

            // Set this to 1 if you have manually extracted the core package from the file
            // core/packages/core.transport.zip.
            // This will reduce the time it takes for the installation process on systems that do
            // not allow the PHP time_limit and Apache script execution time settings to be altered.
            'unpacked' => 0,

            // The language to install MODX for. This will set the default manager language to this. // Use IANA codes.
            'language' => 'en',

            // Information for your administrator account
            'cmsadmin' => 'username',
            'cmspassword' => 'password',
            'cmsadminemail' => 'email@address.com',

            // Path for your MODX core directory
            'core_path' => '#core_path',

            // Paths for the default contexts that are installed
            'context_mgr_path' => '#manager_path',
            'context_mgr_url' => '/manager/',
            'context_connectors_path' => '#connectors_path',
            'context_connectors_url' => '/connectors/',
            'context_web_path' => '#base_path',
            'context_web_url' => '/',
            'assets_path' => '#assets_path',
            'assets_url' => '/assets/',

            // Whether or not to remove the setup/ directory after installation
            'remove_setup_directory' => 1
        ),

        // Should config.inc.php and the config.core.php files be automatically updated after a
        // restore? Use when restoring a site on different server. Saves you from having to update
        // configuration files by hand.
        // Uses data from the xmldata options.
        'updateConfigFilesAfterRestore' => false,

        // Use addFromString (true) or addFile (false)
        // addFromString uses A LOT of memory. Set to false if you run into memory limit problems.
        'addFromString' => true,

        // Compress archive?
        'compressArchive' => true,

        // Should this file be removed after update/install?
        'removeThisFile' => false // boolean
    );

} else {

    //-------------------------------------------------------------------------
    //  Configuration for LOCAL site
    //-------------------------------------------------------------------------

    define('PASSWORD', '');

    $config = array(
        'base_path' => __DIR__,
        'core_path' => '#base_path/core',
        'manager_path' => '#base_path/manager',
        'connectors_path' => '#base_path/connectors',
        'assets_path' => '#base_path/assets',
        'backup_path' => '#base_path/../backup',
        'includeInBackup' => array(
            //'#base_path/../media'
        ),
        'excludeInBackup' => array(
            __FILE__,
            '#core_path/cache'
        ),
        'excludeOnDelete' => array(
            __FILE__,
            '#core_path/.htaccess',
            '#core_path/components',
            '#core_path/packages',
            '#core_path/config',
            '#assets_path',
            '#base_path/.htaccess',
            '#base_path/config.core.php',
            '#base_path/robots.txt',
            '#base_path/apple-touch-icon-precomposed.png',
            '#base_path/apps',
            '#manager_path/.htaccess'
        ),
        'excludeDB' => array(
            'session',
            'manager_log'
        ),
        'backupBeforeUpdate' => false, // boolean
        'saveToRemote' => false, // boolean
        'removeLocalCopy' => false, // boolean
        'restoreFromRemote' => false, // boolean
        'ftp' => array(
            'server' => '',
            'username' => '',
            'password' => '',
            'passive_mode' => false, // boolean
            'remote_path' => '/'
        ),
        'usecli' => false,
        'xmldata' => array(
            'database_type' => 'mysql',
            'database_server' => '127.0.0.1',
            'database' => 'modx',
            'database_user' => 'db_username',
            'database_password' => 'db_password',
            'database_connection_charset' => 'utf8',
            'database_charset' => 'utf8',
            'database_collation' => 'utf8_general_ci',
            'table_prefix' => 'modx_',
            'https_port' => '443',
            'http_host' => 'localhost',
            'cache_disabled' => 0,
            'inplace' => 0,
            'unpacked' => 0,
            'language' => 'en',
            'cmsadmin' => 'username',
            'cmspassword' => 'password',
            'cmsadminemail' => 'email@address.com',
            'core_path' => '#core_path',
            'context_mgr_path' => '#manager_path',
            'context_mgr_url' => '/manager/',
            'context_connectors_path' => '#connectors_path',
            'context_connectors_url' => '/connectors/',
            'context_web_path' => '#base_path',
            'context_web_url' => '/',
            'assets_path' => '#assets_path',
            'assets_url' => '/assets/',
            'remove_setup_directory' => 1
        ),
        'updateConfigFilesAfterRestore' => false,
        'compressArchive' => true,
        'addFromString' => false,
        'removeThisFile' => false
    );

}

ini_set('display_errors', 0);

date_default_timezone_set('Europe/Stockholm');
setlocale(LC_ALL, array('sv_SE.UTF-8','sv_SE@euro','sv_SE','swedish'));

header('Content-Type: text/html; charset=UTF-8');


/**
 *  New areas of research
 *
 *  https://github.com/Grandt/PHPZip
 *  https://github.com/phpmyadmin/phpmyadmin/blob/master/libraries/ZipFile.php
 *  https://gist.github.com/phred/1e2aa1e096ddb1ee7f84
 *  https://github.com/maennchen/ZipStream-PHP
 *  http://www.tinybutstrong.com/apps/tbszip/tbszip_help.html
 *  http://www.phpconcept.net/pclzip
 *
 *
 */


class Zip extends ZipArchive
{

    private $excludeInZip = array(),
            $compress = false,
            $root = '';

    public function setExcluded($dirs)
    {
        $this->excludeInZip = $dirs;
    }

    public function setMethod($fromString)
    {
        $this->fromString = $fromString;
    }

    public function setCompression($compress)
    {
        $this->compress = $compress;
    }

    public function setRoot($dir)
    {
        $this->root = $dir;
    }

    public function addOne($filename, $localname)
    {
        if ($this->fromString) {
            $success = $this->addFromString($localname, file_get_contents($filename));
        } else {
            $success = $this->addFile($filename, $localname);
        }
        if ($success && ! $this->compress) {
            $this->setCompressionName($localname, ZipArchive::CM_STORE);
        }
        return $success;
    }

    public function addAll($path)
    {
        $nodes = glob($path . '/{*,.htaccess}', GLOB_BRACE);
        if (!empty($nodes)) {
            foreach ($nodes as $node) {
                if (is_dir($node)) {
                    $this->addEmptyDir(str_replace($this->root, '', $node));
                    if (! in_array($node, $this->excludeInZip)) {
                        $this->addAll($node);
                    }
                } else if (is_file($node) && ! in_array($node, $this->excludeInZip)) {
                    $this->addOne($node, str_replace($this->root, '', $node));
                }
            }
        }
    }
}

class DB extends PDO {

    public function __construct($dbase, $database_dsn, $database_user, $database_password) {
        try {
            parent::__construct($database_dsn, $database_user, $database_password, array());
        } catch (PDOException $error) {
            echo $error->getMessage();
            exit;
        }

        $this->query('SET NAMES utf8'); // this needs to be specific per db-type

        //Set the error mode to show warning along with error codes
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    }
}

class ModxUtilities {

    private $config,
            $log = array(),
            $zip,
            $db = null,
            $timestamp,
            $installOrUpgrade,
            $newInstall = false,
            $distro = null,
            $version = null,
            $backupfile = null,
            $file = null,
            $password = null,
            $deletebeforeimport = null;

    public $installed,
           $action = null,
           $currentversion = '',
           $currentdistro = '';

    public function __construct($config) {
        $this->setVariables($config);
        $this->setPostedProperties();
        $this->checkInstalled();
        $this->zip = new Zip;
        $this->zip->setExcluded($this->config->excludeInBackup);
        $this->zip->setMethod($this->config->addFromString);
        $this->zip->setCompression($this->config->compressArchive);
        $this->timestamp = date('ymd_His');
    }

    public function log($msg, $em = false, $date = true) {
        $this->log[] = ($date ? date('H:i:s  ') : '          ') . ($em ? "<span>$msg</span>" : $msg);
    }
    public function getLog() {
        return implode("\n", $this->log);
    }

    public function authenticated() {
        return $this->password;
    }

    public function getConfig($key) {
        return property_exists($this->config, $key) ? $this->config->$key : null;
    }

    // http://edmondscommerce.github.io/php/php-realpath-for-none-existant-paths.html
    // Updated to work with windows paths
    private function normalizePath($path) {
        $path = str_replace('\\', '/', $path);
        $parts = explode('/', $path);
        $drive = (strpos($parts[0], ':') !== false) ? array_shift($parts) : '';
        return $drive . array_reduce($parts, function($a, $b) {
            if ($a === 0) {
                $a = '/';
            }
            if ($b === '' || $b === '.') {
                return $a;
            }
            if ($b === '..') {
                return ($a == '/' || preg_match('/^\/[^\/]+$/', $a)) ? '/' : dirname($a);
            }
            return preg_replace('/\/+/', '/', "$a/$b");
        }, 0);
    }

    private function replaceValues($value) {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->replaceValues($v);
            }
        } else {
            $value = str_replace('\\', '/', $value);
            if (preg_match('/^#([a-z]+_[a-z]+)/', $value, $matches)) {
                $value = str_replace('#' . $matches[1], $this->config->{$matches[1]}, $value);
            }
            $value = (strpos($value, '/') !== false) ? $this->normalizePath($value) : $value;
        }
        return $value;
    }

    private function setVariables($config) {
        $this->config = (object)$config;
        foreach ($this->config as $key => $value) {
            $this->config->$key = $this->replaceValues($value);
        }
        $this->config->ftp = (object)$this->config->ftp;
    }

    private function setPostedProperties() {
        $variables = ['action', 'distro', 'version', 'backupfile', 'file', 'password', 'deletebeforeimport'];
        foreach ($variables as $key) {
            if (isset($_POST[$key])) {
                switch ($key) {
                    case 'action':
                        $this->action = preg_replace('/[^a-z]+/', '', $_POST['action']);
                        break;
                    case 'distro':
                        $this->distro = in_array($_POST['distro'], array('traditional', 'advanced')) ? $_POST['distro'] : false;
                        break;
                    case 'version':
                        $this->version = (preg_match('/^\d+\.\d+\.\d+$/', $_POST['version'])) ? $_POST['version'] : false;
                        break;
                    case 'backupfile':
                        $this->backupfile = (preg_match('/^[\d]{12}$/', $_POST['backupfile'])) ? $_POST['backupfile'] : false;
                        break;
                    case 'file':
                        $this->file = (preg_match('/^[\w\-\.]+.zip$/', $_POST['file'])) ? $_POST['file'] : false;
                        break;
                    case 'password':
                        $this->password = ($_POST['password'] === PASSWORD);
                        break;
                    case 'deletebeforeimport':
                        $this->deletebeforeimport = true;
                        break;
                }
            } else {
                $this->$key = null;
            }
        }
    }

    private function checkInstalled() {
        $this->installed = is_readable($this->config->core_path . '/config/config.inc.php');
        if ($this->installed) {
            if (is_readable($this->config->core_path . '/docs/version.inc.php')) {
                require $this->config->core_path . '/docs/version.inc.php';
                $this->currentversion = preg_replace('/[^\d\.]/', '', $v['full_version']);
                $this->currentdistro = trim($v['distro'], '@');
            }
            $this->includeConfig();
        }
    }

    private function includeConfig() {
        if (is_readable($this->config->core_path . '/config/config.inc.php')) {
            require $this->config->core_path . '/config/config.inc.php';
            $this->config->table_prefix = $table_prefix;
            $this->config->dbase = $dbase;
            $this->config->lastInstallTime = $lastInstallTime;
            $this->config->site_id = $site_id;
            $this->config->site_sessionname = $site_sessionname;
            $this->config->https_port = $https_port;
            $this->config->uuid = $uuid;
            $this->db = new DB($dbase, $database_dsn, $database_user, $database_password);
        }
    }

    private function getSiteName() {
        if ($this->db) {
            $stmt = $this->db->prepare("SELECT `value` FROM " . $this->config->table_prefix . "system_settings WHERE `key`='site_name'");
            $stmt->execute();
            $site_name = $stmt->fetch(PDO::FETCH_OBJ);
            if ($site_name) {
                if (function_exists('transliterator_transliterate') && $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower()')) {
                    return str_replace(' ', '_', preg_replace('/[^a-z0-9 ]/', '', iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $transliterator->transliterate($site_name->value))));
                } else {
                    ini_set('mbstring.substitute_character', "none");
                    $site_name->value = mb_convert_encoding($site_name->value, 'UTF-8', 'UTF-8');
                    return str_replace(' ', '_', iconv('UTF-8', 'ASCII//TRANSLIT', $site_name->value));
                }
            }
        }
        return '';
    }

    public function getLatestVersion() {
        $version = '';
        if (isset($_SESSION['version'])) {
            $version = $_SESSION['version'];
        } else {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://modx.com/download/latest',
                CURLOPT_NOBODY => true,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => false
            ));
            curl_exec($ch);
            $headers = curl_getinfo($ch);
            curl_close($ch);
            if ($headers !== false) {
                $file = explode('/', $headers['url']);
                $file = array_pop($file);
                if (preg_match('/\d+\.\d+\.\d+/', $file, $version)) {
                    $_SESSION['version'] = $version[0];
                    $version = $version[0];
                }
            }

            /*
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://modx.com/download',
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ));
            $page = curl_exec($ch);
            curl_close($ch);
            if ($page !== false) {
                preg_match('/' . preg_quote('<h2>Current Version � ') . '([\d\.]+).*' . preg_quote('</h2>', '/') . '/', $page, $match);
                if (isset($match[1])) {
                    $_SESSION['version'] = $match[1];
                    $version = $match[1];
                }
            }
            */
        }
        return $version;
    }

    private function getFile() {
        $this->filename = 'modx-' . $this->version . '-pl' . ($this->distro == 'advanced' ? '-advanced' : '') . '.zip';
        if (is_readable($this->config->backup_path . '/' . $this->filename)) {
            $this->log('Using local file');
            return true;
        } else {
            $path = 'https://modx.com/download/direct?id=' . $this->filename;
            $this->log('Downloading file: ' . $this->filename);
            if ($this->downloadFile($path)) {
                $this->log('File downloaded');
                return true;
            } else {
                $this->log('Error downloading file. Aborting!', true);
            }
        }
        return false;
    }

    private function downloadFile($path) {
        $headers = $this->getHeaders($path);
        if ($headers['http_code'] === 200) {
            if (!is_dir($this->config->backup_path)) {
                mkdir($this->config->backup_path, 0755, true);
            }
            return $this->download($path, $this->config->backup_path . '/' . $this->filename);
        } else {
            $this->log('Cannot find file. Server returned code ' . $headers['http_code'], true);
        }
        return false;
    }

    private function getHeaders($url) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ));
        curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);
        return $headers;
    }

    private function download($url, $path) {
        $fp = fopen($path, 'w+');
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 50,
            CURLOPT_FILE => $fp
        ));
        if (curl_exec($ch) === false) {
            $this->log(curl_error($ch));
        }
        curl_close($ch);
        fclose($fp);
        return (filesize($path) > 0);
    }

    public function backup() {
        $return = true;
        $site_name = $this->getSiteName();
        if ($site_name != '') {
            $site_name .= '_';
        }
        $zip_file = $this->config->backup_path . '/site_backup_' . $site_name . $this->timestamp . '.zip';
        $zipfile = basename($zip_file);
        if (!is_dir($this->config->backup_path)) {
            mkdir($this->config->backup_path, 0755, true);
        }
        if ($this->zip->open($zip_file, ZIPARCHIVE::CREATE)) {
            $this->log('Zipping files');
            $this->zip->setRoot($this->config->base_path . '/');
            $this->zip->addAll($this->config->base_path);
            if (strpos($this->config->core_path, $this->config->base_path) !== 0) {
                $this->zip->setRoot(dirname($this->config->core_path) . '/');
                $this->zip->addAll($this->config->core_path);
            }
            if (! empty($this->config->includeInBackup)) {
                foreach ($this->config->includeInBackup as $dir) {
                    $this->zip->setRoot(dirname($dir) . '/');
                    $this->zip->addAll($dir);
                }
            }
            $this->log('Files zipped');

            $sqlfile = $this->exportSQL();
            if ($sqlfile && $this->zip->addOne($this->config->backup_path . '/' . $sqlfile, $sqlfile)) {
                $this->log('SQL file added to zip');
            } else {
                $this->log('Could not add SQL file to zip', true);
                $return = false;
            }

            if ($this->zip->close()) {
                unlink($this->config->backup_path . '/' . $sqlfile);
                $this->log('Zip file closed');
                if ($sqlfile && $this->config->saveToRemote) {
                    if ($this->saveToRemote($zipfile) && $this->config->removeLocalCopy) {
                        if (unlink($zipfile)) {
                            $this->log('Removed local file');
                        } else {
                            $this->log('Could not remove local file', true);
                            $return = false;
                        }
                    }
                }
            } else {
                $this->log('Could not close the zip file', true);
                $return = false;
            }
        } else {
            $this->log('Could not open the zip file. Aborting!', true);
            $return = false;
        }
        return $return;
    }

    private function saveToRemote($zipfile) {
        $this->log('Copying zip file to remote server');
        $handle = fopen($this->config->backup_path . '/' . $zipfile, 'r');
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'ftp://' . $this->config->ftp->server . $this->config->ftp->remote_path . '/' . $zipfile,
            CURLOPT_USERPWD => $this->config->ftp->username . ':' . $this->config->ftp->password,
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $handle,
            CURLOPT_INFILESIZE => filesize($this->config->backup_path . '/' . $zipfile)
        ));
        $uploaded = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($handle);

        if ($uploaded) {
            $this->log('Zipfile copied to remote server');
        } else {
            $this->log('Could not copy zipfile to remote server', true);
            $this->log($error, true);
        }
        return $uploaded;
    }

    private function listRemoteFiles() {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => 'ftp://' . $this->config->ftp->server . $this->config->ftp->remote_path . '/',
            CURLOPT_USERPWD => $this->config->ftp->username . ':' . $this->config->ftp->password,
            CURLOPT_FTPLISTONLY => true,
            CURLOPT_RETURNTRANSFER => true
        ));
        $remoteFiles = curl_exec($ch);
        curl_close($ch);

        if ($remoteFiles !== false) {
            $remoteFiles = array_map('trim', explode("\n", trim($remoteFiles)));
        }
        return $remoteFiles;
    }

    public function restoreFromRemote() {
        $remoteFiles = $this->listRemoteFiles();
        $files = array();
        if ($remoteFiles !== false) {
            $remote_file = false;
            foreach ($remoteFiles as $file) {
                if (strpos($file, vsprintf('%s_%s', str_split($this->backupfile, 6)) . '.zip') !== false) {
                    $remote_file = $this->config->ftp->remote_path . '/' . $file;
                    break;
                }
            }
            if (! $remote_file) {
                $this->log('Could not find remote file. Aborting!', true);
                return false;
            }

            $local_file = $this->config->backup_path . '/_site_backup_' . time() . '.zip';
            $sqlfile = 'db_backup_' . vsprintf('%s_%s', str_split($this->backupfile, 6)) . '.sql';

            $this->log('Downloading remote file');
            $handle = fopen($local_file, 'w');
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'ftp://' . $this->config->ftp->server . $remote_file,
                CURLOPT_USERPWD => $this->config->ftp->username . ':' . $this->config->ftp->password,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FILE => $handle
            ));
            $downloaded = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            fclose($handle);

            if ($downloaded) {
                $this->log('Remote file downloaded');
                if (is_readable($local_file)) {
                    $this->filename = basename($local_file);
                    $this->deletebeforeimport = true;
                    if ($this->importFile()) {
                        if (unlink($local_file)) {
                            $this->log('Removed local file');
                        } else {
                            $this->log('Could not remove local file', true);
                        }
                        if (! $this->installed) {
                            $this->installed = true;
                            $this->includeConfig();
                        }
                        if ($this->config->updateConfigFilesAfterRestore) {
                            $this->updateConfigIncFile();
                            $this->updateConfigCoreFiles();
                        }
                        $this->importSQL($sqlfile);
                    }
                } else {
                    $this->log('Cannot access the archive file. Aborting!', true);
                }
            } else {
                $this->log('Could not download remote file. Aborting!', true);
                $this->log($error, true);
            }
        } else {
            $this->log('Could not list remote files. Aborting!', true);
        }
    }

    public function restore() {
        $files = glob($this->config->backup_path . '/site_backup_*' . vsprintf('%s_%s', str_split($this->backupfile, 6)) . '.zip', GLOB_BRACE);
        $zip_file = basename($files[0]);
        $sqlfile = 'db_backup_' . vsprintf('%s_%s', str_split($this->backupfile, 6)) . '.sql';
        if (is_readable($this->config->backup_path . '/' . $zip_file)) {
            $this->filename = $zip_file;
            $this->deletebeforeimport = true;
            if ($this->importFile()) {
                if (! $this->installed) {
                    $this->installed = true;
                    $this->includeConfig();
                }
                if ($this->config->updateConfigFilesAfterRestore) {
                    $this->updateConfigIncFile();
                    $this->updateConfigCoreFiles();
                }
                $this->importSQL($sqlfile);
            }
        } else {
            $this->log('Cannot access the archive file. Aborting!', true);
        }
    }

    private function getLanguage() {
        $stmt = $this->db->prepare("SELECT `value` FROM " . $this->config->table_prefix . "system_settings WHERE `key`='manager_language'");
        $stmt->execute();
        $language = $stmt->fetch(PDO::FETCH_OBJ);
        return $language->value;
    }

    public function getBackupFiles() {
        $site_name = $this->getSiteName();
        if ($site_name) {
            $files = glob($this->config->backup_path . '/site_backup_' . $site_name . '_*.zip', GLOB_BRACE);
            if (! empty($files)) {
                return array_map(function($v) {
                    return array(preg_replace('/[^\d]/', '', basename($v)), '');
                }, $files);
            }
        }
        $files = glob($this->config->backup_path . '/site_backup_*.zip', GLOB_BRACE);
        return array_map(function($v) {
            $site_name = substr(basename($v), 12, -18);
            return array(preg_replace('/[^\d]/', '', basename($v)), $site_name);
        }, $files);
    }

    public function getRemoteBackupFiles() {
        $remoteFiles = $this->listRemoteFiles();
        $files = array();
        if ($remoteFiles !== false) {
            $site_name = $this->getSiteName();
            if ($site_name) {
                foreach ($remoteFiles as $file) {
                    if (preg_match('/^site_backup_' . $site_name . '_\d{6}_\d{6}.zip$/', $file)) {
                        $files[] = array(preg_replace('/[^\d]/', '', $file), '');
                    }
                }
            }
            if (empty($files)) {
                foreach ($remoteFiles as $file) {
                    if (preg_match('/^site_backup_[a-z0-9_]*\d{6}_\d{6}.zip$/', $file)) {
                        $name = substr(basename(trim($file)), 12, -18);
                        $files[] = array(preg_replace('/[^\d]/', '', $file), $name);
                    }
                }
            }
        }
        return $files;
    }

    public function import() {
        if ($this->file && is_readable($this->config->backup_path . '/' . $this->file)) {
            $this->filename = $this->file;
            $this->importFile();
            if ($this->db) {
                //$sqlfile = 'db_backup_' . vsprintf('%s_%s', str_split($this->file, 6)) . '.sql';
                //$this->importSQL($sqlfile);
            } else {

            }
        } else {
            $this->log('File could not be read. Aborting!', true);
        }
    }

    private function importFile() {
        $this->installOrUpgrade = false;
        $return = $this->unpackFiles();
        if ($return) {
            // Recreate cache dir if missing from restored site
            if (! is_dir($this->config->core_path . '/cache')) {
                $this->log('core/cache directory missing');
                if (mkdir($this->config->core_path . '/cache', 0755)) {
                    $this->log('core/cache directory created');
                } else {
                    $this->log('core/cache directory could not be created!', true);
                }
            }
        }
        return $return;
    }

    public function update() {
        $this->installOrUpgrade = true;

        if (! $this->distro) {
            $this->log('Incorrect distro. Aborting!', true);
            return false;
        }

        if (! $this->version) {
            $this->log('Incorrect version numbering. Aborting!', true);
            return false;
        }

        $current = vsprintf("%s%02s%02s", explode('.', $this->currentversion));
        $new = vsprintf("%s%02s%02s", explode('.', $this->version));
        if ($new < $current) {
            $this->log('New version (' . $this->version . ') is older than current version (' . $this->currentversion . '). Aborting!', true);
            return false;
        }

        if ($this->getFile()) {
            if ($this->config->backupBeforeUpdate) {
                $this->log('Running backup');
                if ($this->backup()) {
                    $this->log('Backup complete');
                } else {
                    $this->log('Could not backup website. Aborting!', true);
                    return false;
                }
            }
            if (! $this->deletebeforeimport) {
                $this->log('Deleting core/cache/');
                set_error_handler(array($this, 'rmdirHandler'), E_WARNING);
                $this->deltree($this->config->core_path . '/cache', false, true);
                restore_error_handler();
            }

            /*
            // From Janitor by Shamblett
            // Remove the core directory and the transport.zip
            set_error_handler(array($this, 'rmdirHandler'), E_WARNING);
            $this->deltree($this->config->core_path . '/packages/core', false, true);
            restore_error_handler();
            $transportFile = $this->config->core_path . '/packages/core.transport.zip';
            if (file_exists($transportFile)) {
                unlink($transportFile);
            }

            require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
            $modx = new modX();
            $modx->initialize('mgr');
            $modx->getService('error','error.modError', '', '');

            // Empty the logs
            if ($this->modx->exec("TRUNCATE {$modx->getTableName('modManagerLog')}") === false) {
                $this->log('Could not truncate manager log', true);
            }
            if ($this->modx->exec("TRUNCATE {$modx->getTableName('modEventLog')}") === false) {
                $this->log('Could not truncate event log', true');
            }
            $logFile = $modx->getOption(xPDO::OPT_CACHE_PATH) . 'logs/error.log';
            if (file_exists($logFile)) {
                $cacheManager = $modx->getCacheManager();
                $cacheManager->writeFile($logFile, ' ');
            }

            // Clear the cache
            $contexts = $modx->getCollection('modContext');
            foreach ($contexts as $context) {
                $paths[] = $context->get('key') . '/';
            }
            $options = array(
                'publishing' => 1,
                'extensions' => array('.cache.php', '.msg.php', '.tpl.php'),
            );
            if ($modx->getOption('cache_db')) {
                $options['objects'] = '*';
            }
            $modx->cacheManager->clearCache($paths, $options);

            // Flush permissions for the logged in user
            if ($modx->getUser()) {
                $modx->user->getAttributes(array(), '', true);
            }

            // Flush sessions
            if ($modx->getOption('session_handler_class', null, 'modSessionHandler') == 'modSessionHandler') {
                $sessionTable = $modx->getTableName('modSession');
                $modx->exec("TRUNCATE {$sessionTable}");
                $modx->user->endSession();
            }
            unset($modx);
            */

            if ($this->unpackFiles()) {
                $manual_install = true;
                if ($this->config->usecli) {
                    $output = shell_exec('php -v');
                    if (substr($output, 0, 3) == 'PHP') {
                        $this->log('Running upgrade in cli mode');
                        if ($this->createConfigXML()) {
                            $output = shell_exec('php -d error_reporting=0 ' . $this->config->base_path . '/setup/index.php --installmode=upgrade --core_path=' . $this->config->xmldata['core_path'] . ' --config=' . $this->config->backup_path . '/config.xml');
                            unlink($this->config->backup_path . '/config.xml');
                            $this->log(trim($output));
                            $manual_install = false;
                        }
                    } else {
                        $this->log('Could not upgrade in cli mode', true);
                    }
                }
                if ($manual_install) {
                    $this->log('<a href="setup">Update the website immediately!</a>');
                }
                if ($this->config->removeThisFile) {
                    if (unlink(__FILE__)) {
                        $this->log('This file is deleted');
                    } else {
                        $this->log('Could not delete this file. Remove it manually.', true);
                    }
                }
            } else {
                $this->log('Errors occurred during unpacking', true);
            }
        }
    }

    public function install() {
        if ($this->installed) {
            $this->log('MODX already installed. Aborting!', true);
            return false;
        }

        $this->installOrUpgrade = true;
        $this->newInstall = true;

        if (! $this->distro) {
            $this->log('Incorrect distro. Aborting!', true);
            return false;
        }

        if (! $this->version) {
            $this->log('Incorrect version numbering. Aborting!', true);
            return false;
        }

        if ($this->getFile()) {
            if ($this->unpackFiles()) {
                $manual_install = true;
                if ($this->config->usecli) {
                    $output = shell_exec('php -v');
                    if (substr($output, 0, 3) == 'PHP') {
                        $this->log('Running install in cli mode');
                        if ($this->createConfigXML()) {
                            $output = shell_exec('php -d error_reporting=0 ' . $this->config->base_path . '/setup/index.php --installmode=new --core_path=' . $this->config->xmldata['core_path'] . ' --config=' . $this->config->backup_path . '/config.xml');
                            unlink($this->config->backup_path . '/config.xml');
                            $this->log(trim($output));
                            $manual_install = false;
                        }
                    } else {
                        $this->log('Could not install in cli mode', true);
                    }
                }
                if ($manual_install) {
                    $this->log('<a href="/setup">Install the website immediately!</a>');
                }
                if ($this->config->removeThisFile) {
                    if (unlink(__FILE__)) {
                        $this->log('This file is deleted');
                    } else {
                        $this->log('Could not delete this file. Remove it manually.', true);
                    }
                }
            } else {
                $this->log('Errors occurred during unpacking', true);
            }
        }
    }

    private function unpackFiles() {
        $return = true;
        $zip_file = $this->config->backup_path . '/' . $this->filename;
        if (file_exists($zip_file)) {
            $zip = zip_open($zip_file);
            if ($zip) {
                if ($this->deletebeforeimport) {
                    // Clear out old installation
                    $this->log('Removing old installation');
                    set_error_handler(array($this, 'rmdirHandler'), E_WARNING);
                    $this->deltree($this->config->core_path, $this->installOrUpgrade, true);
                    $this->deltree($this->config->manager_path, $this->installOrUpgrade, true);
                    if ($this->installOrUpgrade) {
                        $this->config->excludeOnDelete[] = $this->config->core_path;
                        $this->config->excludeOnDelete[] = $this->config->manager_path;
                    } else {
                        $this->config->excludeOnDelete = array($this->normalizePath(__FILE__));
                    }
                    $this->deltree($this->config->base_path, true, true);
                    restore_error_handler();
                    $this->log('Old installation removed');
                }

                $this->log('Unpacking files');
                $routes = $this->installOrUpgrade ? array(
                    'core' => $this->config->core_path,
                    'connectors' => $this->config->connectors_path,
                    'manager' => $this->config->manager_path,
                    'setup' => $this->config->base_path . '/setup'
                ) : array(
                    basename($this->config->core_path) => $this->config->core_path,
                    basename($this->config->connectors_path) => $this->config->connectors_path,
                    basename($this->config->manager_path) => $this->config->manager_path,
                    basename($this->config->assets_path) => $this->config->assets_path
                );
                if (! $this->installOrUpgrade && ! empty($this->config->includeInBackup)) {
                    foreach ($this->config->includeInBackup as $dir) {
                        $routes[basename($dir)] = $dir;
                    }
                }
                while ($zip_entry = zip_read($zip)) {
                    $file = zip_entry_name($zip_entry);
                    if ($this->installOrUpgrade) {
                        $parts = explode('/', $file);
                        array_shift($parts);
                        $route = array_shift($parts);
                        if ($route == '.') {
                            continue;
                        }
                        if (in_array($route, array('config.core.php', 'index.php', 'ht.access'))) {
                            if ($this->newInstall) {
                                $path = $this->config->base_path . '/' . $route;
                            } else {
                                $path = ($route == 'index.php') ? $this->config->base_path . '/' . $route : false;
                            }
                        } else {
                            $path = $routes[$route] . '/' . implode('/', $parts);
                        }
                    } else {
                        if (strpos($file, '/') === false) {
                            if (preg_match('/db_backup_\d{6}_\d{6}.sql/', $file)) {
                                $path = $this->config->backup_path . '/' . $file;
                            } else {
                                $path = $this->config->base_path . '/' . $file;
                            }
                        } else {
                            $parts = explode('/', $file);
                            $route = array_shift($parts);
                            $path = $routes[$route] . '/' . implode('/', $parts);
                        }
                    }
                    if ($path) {
                        $dir = dirname($path);
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        if (substr($file, -1) != '/') {
                            if (zip_entry_open($zip, $zip_entry, "r")) {
                                if ($fp = fopen($path, "w")) {
                                    $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                                    if ($buf !== false) {
                                        fwrite($fp, $buf);
                                    } else {
                                        $this->log('Cannot read file: ' . $file, true);
                                        $return = false;
                                    }
                                    fclose($fp);
                                } else {
                                    $this->log('Cannot open file: ' . $path, true);
                                    $return = false;
                                }
                                zip_entry_close($zip_entry);
                            } else {
                                $this->log('Cannot open file in zip: ' . $file, true);
                                $return = false;
                            }
                        } else if (!is_dir($path)) {
                            mkdir($path, 0755, true);
                        }
                    }
                }
                zip_close($zip);

                if ($return) {
                    $this->log('Files unpacked');
                } else {
                    $this->log('There were problems unpacking the files', true);
                }

            } else {
                $this->log('Could not open the zip file!', true);
                $return = false;
            }
        } else {
            $this->log("Could not find zip file!\n  $zip_file", true);
            $return = false;
        }
        return $return;
    }

    public function delete() {
        set_error_handler(array($this, 'rmdirHandler'), E_WARNING);
        $this->deltree($this->config->core_path);
        $this->deltree($this->config->base_path, false, true);
        restore_error_handler();
        $this->log('The installation is deleted. The database is still intact.');
    }

    private function deltree($dirname, $filtered = false, $root = false) {
        if (!is_dir($dirname)) {
            return false;
        }
        foreach (glob($dirname . "/{*,.htaccess,.gitignore,.DS_Store}", GLOB_BRACE) as $object) {
            if ($filtered ? ! in_array($object, $this->config->excludeOnDelete) : true) {
                if (is_dir($object)) {
                    $this->deltree($object, $filtered);
                } else {
                    unlink($object);
                }
            }
        }
        if (! $root) {
            rmdir($dirname);
        }
    }

    private function rmdirHandler($errno, $errstr) {
        $this->log($errstr, true);
    }

    /**
     * @name:  exportSQL
     * @desc:  text
     * @param: $selected - text
     */
    private function exportSQL() {

        $this->log('Exporting database');

        // http://davidwalsh.name/backup-mysql-database-php

        //get all of the tables
        $stmt = $this->db->prepare('SHOW TABLES');
        $stmt->execute();
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tables = array();
        foreach ($all as $table) {
            $tables[] = $table['Tables_in_' . $this->config->dbase];
        }

        $sqlfile = 'db_backup_' . $this->timestamp . '.sql';

        $handle = fopen($this->config->backup_path . '/' . $sqlfile, 'w+');

        $stmt = $this->db->prepare("SHOW VARIABLES WHERE Variable_name='max_allowed_packet'");
        $stmt->execute();
        $max_allowed_packet = $stmt->fetch(PDO::FETCH_ASSOC);
        $max_length = $max_allowed_packet['Value'] < 100000 ? $max_allowed_packet['Value'] : 100000;

        $to_print = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
        if ($this->fwrite_stream($handle, $to_print) === false) {
            return false;
        }

        //cycle through
        foreach ($tables as $table) {
            $stmt = $this->db->prepare('SHOW COLUMNS FROM ' . $table);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $column = array();
            foreach ($columns as $col) {
                $column[] = $col['Field'];
            }

            $to_print = 'DROP TABLE IF EXISTS `' . $table . '`;';

            $stmt = $this->db->prepare('SHOW CREATE TABLE ' . $table);
            $stmt->execute();
            $create = $stmt->fetch(PDO::FETCH_ASSOC);

            if (in_array(str_replace($this->config->table_prefix, '', $table), $this->config->excludeDB)) {
                $create['Create Table'] = preg_replace('/AUTO_INCREMENT=\d+/', 'AUTO_INCREMENT=1', $create['Create Table']);
            }

            $to_print .= "\n\n" . $create['Create Table'] . ";\n\n";
            if ($this->fwrite_stream($handle, $to_print) === false) {
                return false;
            }

            if (!in_array(str_replace($this->config->table_prefix, '', $table), $this->config->excludeDB)) {
                $to_print = "LOCK TABLES `$table` WRITE;\n/*!40000 ALTER TABLE `$table` DISABLE KEYS */;\n\n";
                if ($this->fwrite_stream($handle, $to_print) === false) {
                    return false;
                }

                $insert_into = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $column) . "`) VALUES\n";

                $stmt = $this->db->prepare('SELECT * FROM ' . $table);
                $stmt->execute();
                $num_fields = $stmt->columnCount();

                $length = 0;
                $rows = array();

                while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                    if (empty($rows)) {
                        $length += strlen($insert_into);
                    }
                    for ($j = 0; $j < $num_fields; $j++) {
                        if (isset($row[$j])) {
                            $row[$j] = addslashes($row[$j]);
                            $row[$j] = preg_replace('/\r/', '\\r', $row[$j]);
                            $row[$j] = preg_replace('/\n/', '\\n', $row[$j]);
                            if ($row[$j] === null) {
                                $row[$j] = 'null';
                            } else {
                                $row[$j] = '"' . $row[$j] . '"';
                            }
                        } else {
                            $row[$j] = '""';
                        }
                    }
                    $row = '(' . implode(',', $row) . ")";
                    $row_length = strlen($row) + 3;
                    if ($row_length + $length > $max_length) {
                        if (empty($rows)) {
                            $this->log('The MySQL variable max_allowed_packet is set to low! It needs to be at least ' . ($row_length + $length) . ' bytes.', true);
                            return false;
                        }
                        $to_print = $insert_into . implode(",\n", $rows) . ";\n\n";
                        if ($this->fwrite_stream($handle, $to_print) === false) {
                            return false;
                        }
                        $length = 0;
                        $rows = array();
                    }

                    $rows[] = $row;
                    $length += $row_length;

                }

                if (!empty($rows)) {
                    $to_print = $insert_into . implode(",\n", $rows) . ";\n\n";
                    if ($this->fwrite_stream($handle, $to_print) === false) {
                        return false;
                    }
                }

                $to_print = "/*!40000 ALTER TABLE `$table` ENABLE KEYS */;\nUNLOCK TABLES;\n\n\n\n";
                if ($this->fwrite_stream($handle, $to_print) === false) {
                    return false;
                }
            }
        }

        $to_print = "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";
        if ($this->fwrite_stream($handle, $to_print) === false) {
            return false;
        }

        fclose($handle);

        $this->log('Database exported');
        return $sqlfile;
    }

    private function fwrite_stream($fp, $string) {
        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            $fwrite = fwrite($fp, substr($string, $written));
            if ($fwrite === false || $fwrite === 0) {
                $this->log('Could not print to backup file. Aborting!', true);
                return false;
            }
        }
        return $written;
    }

    private function importSQL($sqlfile) {
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);

        if (is_readable($this->config->backup_path . '/' . $sqlfile)) {
            $sql = file_get_contents($this->config->backup_path . '/' . $sqlfile);
            $this->log('Importing DB');
            try {
                if ($this->db->exec($sql) !== false) {
                    $this->log('DB imported');
                } else {
                    $this->log('Could not import DB!', true);
                }
            }
            catch (PDOException $error) {
                echo $error->getMessage();
                exit;
            }
            if (unlink($this->config->backup_path . '/' . $sqlfile)) {
                $this->log('Removed SQL file');
            } else {
                $this->log('Could not remove SQL file', true);
            }
        } else {
            $this->log('Could not read SQL file', true);
        }
    }

    private function createConfigXML() {
        if ($this->installed) {
            $language = $this->getLanguage();
            $language = $language ? $language : $this->config->xmldata['language'];
            $this->config->xmldata = array(
                'inplace' => 0,
                'unpacked' => 0,
                'language' => $language,
                'core_path' => MODX_CORE_PATH,
                'assets_path' => $this->config->assets_path,
                'remove_setup_directory' => 1
            );
        }
        $xml = new SimpleXMLElement('<modx/>');
        reset($this->config->xmldata);
        foreach($this->config->xmldata as $key => $value) {
            if (strpos($key, 'path') !== false || strpos($key, 'url') !== false) {
                // paths need trailing /
                $value = rtrim($value, '/') . '/';
            }
            $xml->addChild($key, htmlspecialchars($value, ENT_NOQUOTES));
        }
        if ($fh = fopen($this->config->backup_path . '/config.xml', 'w')) {
            fwrite($fh, $xml->asXML());
            fclose($fh);
            $this->log('config.xml file created');
            return true;
        } else {
            $this->log('Could not create config.xml', true);
            return false;
        }
    }

    private function updateConfigIncFile() {
        if (is_readable($this->config->core_path . '/docs/config.inc.tpl')) {
            $data = array(
                '{database_type}' => $this->config->xmldata['database_type'],
                '{database_server}' => $this->config->xmldata['database_server'],
                '{database_user}' => $this->config->xmldata['database_user'],
                '{database_password}' => $this->config->xmldata['database_password'],
                '{database_connection_charset}' => $this->config->xmldata['database_charset'],
                '{dbase}' => $this->config->xmldata['database'],
                '{table_prefix}' => $this->config->xmldata['table_prefix'],
                '{database_dsn}' => $this->config->xmldata['database_type'] . ':host=' . $this->config->xmldata['database_server'] . ';dbname=' . $this->config->xmldata['database'] . ';charset=' . $this->config->xmldata['database_charset'],
                '{config_options}' => 'array()',
                '{driver_options}' => 'array()',
                '{last_install_time}' => $this->config->lastInstallTime,
                '{site_id}' => $this->config->site_id,
                '{site_sessionname}' => $this->config->site_sessionname,
                '{https_port}' => $this->config->https_port,
                '{uuid}' => $this->config->uuid,
                '{core_path}' => $this->config->core_path . '/',
                '{processors_path}' => $this->config->core_path . '/model/modx/processors/',
                '{connectors_path}' => $this->config->connectors_path . '/',
                '{connectors_url}' => str_replace($this->config->base_path, '', $this->config->connectors_path) . '/',
                '{mgr_path}' => $this->config->manager_path . '/',
                '{mgr_url}' => str_replace($this->config->base_path, '', $this->config->manager_path) . '/',
                '{web_path}' => $this->config->base_path . '/',
                '{web_url}' => str_replace($this->config->base_path, '', $_SERVER['DOCUMENT_ROOT']) . '/',
                '{http_host}' => $this->config->xmldata['http_host'],
                '{assets_path}' => $this->config->assets_path . '/',
                '{assets_url}' => str_replace($this->config->base_path, '', $this->config->assets_path) . '/',
                '{cache_disabled}' => MODX_CACHE_DISABLED ? 'true' : 'false'
            );
            $content = file_get_contents($this->config->core_path . '/docs/config.inc.tpl');
            $content = str_replace(array_keys($data), $data, $content);
            if (file_put_contents($this->config->core_path . '/config/config.inc.php', $content, LOCK_EX)) {
                $this->log('Updated config.inc.php');
            } else {
                $this->log('Could not update config.inc.php', true);
            }
        } else {
            $this->log('Could not find config.inc.tpl', true);
        }
    }

    private function updateConfigCoreFiles() {
        $content = file($this->config->base_path . '/config.core.php');
        if (strpos($content[1], $this->config->core_path . '/') === false) {
            $files = array(
                $this->config->base_path,
                $this->config->manager_path,
                $this->config->connectors_path
            );
            $content = "<?php
define('MODX_CORE_PATH', '" . $this->config->core_path . "/');
define('MODX_CONFIG_KEY', 'config');
?>";
            foreach ($files as $file) {
                if ($fh = fopen($file . '/config.core.php', 'w')) {
                    fwrite($fh, $content);
                    fclose($fh);
                    $this->log('Updated ' . $file . '/config.core.php');
                } else {
                    $this->log('Could not update ' . $file . '/config.core.php', true);
                }
            }
        }
    }

}


?>
<!DOCTYPE html>
<html lang ="en">
<head>
<title></title>
<meta charset="utf-8" />
<meta name="robots" content="noindex, nofollow" />

<style type="text/css">
body {
    text-align: center;
}
form, #result {
    display: inline-block;
    text-align: left;
    margin: 100px auto;
    border-radius: 4px;
    min-width: 300px;
    padding: 20px;
    box-shadow: 0 0 6px #888;
}
fieldset {
    border: 1px solid #ddd;
    border-radius: 4px;
    margin: 0;
}
legend {
    font: bold 16px calibri,sans-serif;
}
label {
    display: block;
    margin: 0;
    font: normal 14px calibri,sans-serif;
}
label span {
    font-weight: normal;
    font-size: 80%;
}
label[for] {
    margin: 16px 0 0 0;
    font: bold 16px calibri,sans-serif;
}
input[type=submit] {
    width: 80px;
}
input:focus {
    -moz-box-shadow: 0 0 4px #666;
    -webkit-box-shadow: 0 0 4px #666;
    box-shadow: 0 0 4px #666;
}
.animated {
    height: 0px;
    overflow: hidden;
    transition: .4s all cubic-bezier(0.455, 0.030, 0.515, 0.955); /* easeInOutQuad */
}
h2 {
    font: bold 16px calibri,sans-serif;
    margin: 0 0 12px 0;
}
pre {
    font: normal 12px consolas,mono-type;
}
pre span {
    color: #f00;
    margin: 0 0 16px 0;
}
p {
    display: inline-box;
    font: normal 14px calibri,sans-serif;
    margin: 0 0 4px 0;
    max-width: 300px;
}
a {
    color: #00f;
}
</style>

<script type="text/javascript">
onload = function() {
    if (document.forms[0]) {
        document.forms[0].reset();
    }
    var actions = document.querySelectorAll('[name=action]');
    for (var i = 0; i < actions.length; i++) {
        actions[i].addEventListener('click', toggle, false);
    }
}
function displayElement(id) {
    var elem = document.getElementById(id),
        node = elem.cloneNode(true),
        body = document.getElementsByTagName("body")[0];
    node.style.cssText = 'visibility:hidden;position:absolute;height:auto';
    body.appendChild(node);
    elem.style.height = node.offsetHeight + 'px';
    body.removeChild(node);
}
function toggle() {
    hideInputs();
    var divs = this.getAttribute('data').split(',');
    if (divs[0] != '') {
        for (var i = 0; i < divs.length; i++) {
            displayElement(divs[i]);
        }
    }
}
function hideInputs() {
    var elems = document.getElementsByClassName('animated');
    for (var i = 0; i < elems.length; i++) {
        elems[i].style.height = 0;
    }
}
function checkRadios(radios) {
    for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked == true) {
            return radios[i].value;
        }
    }
    return false;
}
function submitForm(elem) {
    var action = checkRadios(document.querySelectorAll('[name=action]'));
    switch (action) {
        case 'restore':
            if (checkRadios(document.querySelectorAll('[name=backupfile]')) === false) {
                return false;
            }
            break;
        case 'update':
        case 'install':
            if (checkRadios(document.querySelectorAll('[name=distro]')) === false || document.getElementById('version').value == '') {
                return false;
            }
            break;
        case 'delete':
            if (document.querySelector('#yes').checked != true) {
                return false;
            }
            break;
    }
    elem.value = 'Wait...';
    elem.disabled = true;
    elem.parentNode.submit();
    return false;
}
</script>
</head>
<body>

<?php

$start = microtime(true);

session_cache_limiter('nocache');
session_start();
$_SESSION['time'] = isset($_SESSION['time']) ? $_SESSION['time'] : time() + 60 * 60;
if ($_SESSION['time'] < time()) {
    session_unset();
    session_destroy();
    session_start();
}

$utility = new ModxUtilities($config);

if (is_null($utility->action)) {

    $backupfiles = $utility->getConfig('restoreFromRemote') ? $utility->getRemoteBackupFiles() : $utility->getBackupFiles();
    $backups = array();
    foreach ($backupfiles as $file) {
        $date = vsprintf('20%s-%s-%s %s:%s:%s', str_split($file[0], 2));
        $backups[] = '<label><input type="radio" value="' . $file[0] . '" name="backupfile" /> ' . $date . ' ' . $file[1] . '</label>';
    }
    if (empty($backups)) {
        $backups[] = '<label>No files to import</label>';
    }
    $backups = implode("\n", $backups);

    if ($utility->installed) {
        $updateinstall = '<label><input type="radio" value="update" name="action" id="update" data="deletecurrent,distro,versioninput" /> Update</label>';
    } else {
        $updateinstall = '<label><input type="radio" value="install" name="action" id="install" data="distro,versioninput" /> Install</label>';
    }

?>

<form action="<?= basename(__FILE__); ?>" method="post">
    <fieldset>
        <legend>Action</legend>
        <label><input type="radio" value="check" name="action" id="check" data="" /> Check paths</label>
        <label><input type="radio" value="backup" name="action" id="backup" data="" /> Backup</label>
        <label><input type="radio" value="restore" name="action" id="restore" data="backupfiles" /> Restore backup</label>
        <label><input type="radio" value="import" name="action" id="import" data="deletecurrent,fileinput" /> Import</label>
        <?= $updateinstall; ?>
        <label><input type="radio" value="delete" name="action" id="delete" data="really" /> Delete</label>
    </fieldset>
    <div id="deletecurrent" class="animated">
        <label for="yesdelete">Delete current installation?</label>
        <p>This will delete all files on your website that isn't on the exclude list. The database will not be touched. Are you sure?</p>
        <label><input type="checkbox" value="yes" name="deletebeforeimport" id="yesdelete" /> Yes</label>
    </div>
    <div id="really" class="animated">
        <label for="yes">Really?</label>
        <p>This will completely delete all files on your website. The database will not be touched. Are you sure?</p>
        <label><input type="checkbox" value="yes" name="yes" id="yes" /> Yes</label>
    </div>
    <div id="distro" class="animated">
        <label for="distro">Distro</label>
        <label><input type="radio" value="traditional" name="distro"<?= ($utility->currentdistro == 'traditional' ? ' checked' : ''); ?> /> Traditional</label>
        <label><input type="radio" value="advanced" name="distro"<?= ($utility->currentdistro == 'advanced' ? ' checked' : ''); ?> /> Advanced</label>
    </div>
    <div id="versioninput" class="animated">
        <label for="version">Version<?= ($utility->currentversion ? ' <span>(current: ' . $utility->currentversion . ')</span>' : ''); ?></label>
        <input type="text" id="version" name="version" value="<?= $utility->getLatestVersion(); ?>" />
    </div>
    <div id="fileinput" class="animated">
        <label for="file">Filename</label>
        <input type="text" id="file" name="file" />
    </div>
    <div id="backupfiles" class="animated">
        <label for="file">Available backups</label>
        <?= $backups; ?>
    </div>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" />
    <br /><br />
    <input type="submit" value="Execute" onclick="return submitForm(this);" />
</form>
</body>
</html>

<?php

} else if ($utility->authenticated() === true) {

    echo '<div id="result">';
    echo '<h2>Result</h2>';
    echo '<pre>';

    switch ($utility->action) {

        case 'check':
            $utility->log("Check paths\n");

            if ($utility->installed) {
                $utility->log('MODX paths in config.inc.php', false, false);
                $utility->log('----------------------------', false, false);
                $utility->log('MODX_BASE_PATH:       ' . MODX_BASE_PATH, false, false);
                $utility->log('MODX_CORE_PATH:       ' . MODX_CORE_PATH, false, false);
                $utility->log('MODX_MANAGER_PATH:    ' . MODX_MANAGER_PATH, false, false);
                $utility->log('MODX_CONNECTORS_PATH: ' . MODX_CONNECTORS_PATH, false, false);
                $utility->log('MODX_ASSETS_PATH:     ' . MODX_ASSETS_PATH, false, false);
            } else {
                $utility->log('Cannot find MODX config file. MODX installed?', true);
            }

            $utility->log('', false, false);
            $utility->log('Configured paths', false, false);
            $utility->log('----------------', false, false);
            $correct = $utility->installed ? MODX_BASE_PATH != $utility->getConfig('base_path') . '/' : false;
            $utility->log('base_path:       ' . $utility->getConfig('base_path'), $correct, false);
            $correct = $utility->installed ? MODX_CORE_PATH != $utility->getConfig('core_path') . '/' : false;
            $utility->log('core_path:       ' . $utility->getConfig('core_path'), $correct, false);
            $correct = $utility->installed ? MODX_MANAGER_PATH != $utility->getConfig('manager_path') . '/' : false;
            $utility->log('manager_path:    ' . $utility->getConfig('manager_path'), $correct, false);
            $correct = $utility->installed ? MODX_CONNECTORS_PATH != $utility->getConfig('connectors_path') . '/' : false;
            $utility->log('connectors_path: ' . $utility->getConfig('connectors_path'), $correct, false);
            $correct = $utility->installed ? MODX_ASSETS_PATH != $utility->getConfig('assets_path') . '/' : false;
            $utility->log('assets_path:     ' . $utility->getConfig('assets_path'), $correct, false);
            $utility->log('backup_path:     ' . $utility->getConfig('backup_path'), false, false);
            $utility->log('', false, false);
            $utility->log('Include in backup: ', false, false);
            $utility->log('    ' . implode("\n              ", $utility->getConfig('includeInBackup')), false, false);
            $utility->log('', false, false);
            $utility->log('Exclude in backup: ', false, false);
            $utility->log('    ' . implode("\n              ", $utility->getConfig('excludeInBackup')), false, false);
            $utility->log('', false, false);
            $utility->log('Exclude on delete: ', false, false);
            $utility->log('    ' . implode("\n              ", $utility->getConfig('excludeOnDelete')), false, false);
            break;

        case 'backup':
            $utility->log('Backup website');
            $utility->backup();
            break;

        case 'import':
            $utility->log('Import website');
            $utility->import();
            break;

        case 'restore':
            if ($utility->getConfig('restoreFromRemote')) {
                $utility->log('Restore website from remote backup');
                $utility->restoreFromRemote();
            } else {
                $utility->log('Restore website from backup');
                $utility->restore();
            }
            break;

        case 'update':
            $utility->log('Update website');
            $utility->update();
            break;

        case 'install':
            $utility->log('Install website');
            $utility->install();
            break;

        case 'delete':
            $utility->log('Delete website');
            $utility->delete();
            break;

    }

    echo $utility->getLog();

    $end = microtime(true);
    echo "\n\nExecution time: " . round($end - $start, 2) . " s";
    echo "\nPeak memory usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . ' MB';

    echo '</pre>';
    echo '<p><a href="' . basename(__FILE__) . '">Back</a></p>';
    echo '</div>';

} else {
    sleep(1);
    header('Location: ' . basename(__FILE__));
    exit;
}

?>

</body>
</html>
