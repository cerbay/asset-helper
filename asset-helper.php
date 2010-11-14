<?php
/**
 * Asset Helper
 * Brings Rails-style asset helper functions to PHP sites. 
 * Includes support for static asset hosts and timestamped query
 * strings to support long-term caching for better performance.
 * 
 * Complete documentation can be found at:
 * http://www.corvidworks.com/projects/asset-helper/
 *
 * @author   Kenn Wilson - http://www.corvidworks.com/
 * @version  1.3
 */
?>
<?php
/**
 * Configuration - Set your document root here if you have trouble detecting it
 * automatically. Default is to use the $_SERVER['DOCUMENT_ROOT'] superglobal.
 */
$document_root = $_SERVER['DOCUMENT_ROOT'];
//$document_root = '';

/**
 * Create <link /> tag for the specified stylesheets.
 * 
 * @param   string    List of strings containing stylesheet(s) to load.
 *                    Loads WordPress default style.css if no file is specified.
 */
function stylesheet_tag()
{
	if (is_wordpress()) {
		$default = 'style.css';
	}
	$num_args = func_num_args();
	$files = array();
	$media = 'all';
	if (($num_args === 0) && (isSet($default))) {
		$files[] = $default;
	} else {
		for ($i = 0; $i < $num_args; $i++) {
			$arg = func_get_arg($i);
			if (is_array($arg)) {
				if (array_key_exists('media', $arg)) {
					$media = $arg['media'];
					unset($arg['media']);
				}
			} elseif ((is_string($arg)) && (!empty($arg))) {
				$files[] = $arg;
			}
		}
	}	
	foreach ($files as $file) {
		if ((empty($file)) && (isSet($default))) {
			$file = $default;
		}
		if (!preg_match('|\.css$|', $file)) {
			$file = $file . '.css';
		}
		$path = get_path($file, 'css');
		print "<link rel='stylesheet' href='${path}' type='text/css' media='${media}' />\n";
	}
}

/**
 * Create <script></script> tags for the specified JavaScript.
 * 
 * @param   mixed     List of strings containing JavaScript file(s) to load.
 * @return  string    Returns false if no files are requested.
 */
function javascript_tag()
{
	$num_args = func_num_args();
	$files = array();
	if ($num_args === 0) return false;
	for ($i = 0; $i < $num_args; $i++) {
		$arg = func_get_arg($i);
		if ((is_string($arg)) && (!empty($arg))) {
			$files[] = $arg;
		}
	}
	foreach ($files as $file) {
		if (!preg_match('|\.js$|', $file)) {
			$file = $file . '.js';
		}
		$path = get_path($file, 'js');
		print "<script src='${path}' type='text/javascript'></script>\n";
	}
}

/**
 * Create <img /> tag for the specified image.
 * 
 * @param   string     String containing filename of image to load
 * @param   array      Options array
 * @return  boolean    Returns false if no files are requested.
 */
function image_tag($file = false, $options = array())
{
	if (empty($file)) return false;
	$path = get_path($file, 'img');
	
	// Add empty 'alt' attribute if no alternate text was specified
	if (isSet($options['alt'])) {
		$alt = $options['alt'];
		unset($options['alt']);
	} else {
		$alt = "";
	}
	$alt = "alt='${alt}'";
	
	// Add width/height attributes
	if ((isSet($options['size'])) && (preg_match('/([0-9]+)x([0-9]+)/', $options['size'], $matches))) {
		$size = "width='${matches[1]}' height='${matches[2]}'";
		unset($options['size']);
	} else {
		$size = "";
	}
	
	// Add any additional elements to <img /> tag as attribute/value pairs.
	$add_attr = "";
	foreach ($options as $attr => $value) {
		$add_attr .= "${attr}='${value}' ";
	}
	print "<img src='${path}' ${size} ${alt} ${add_attr} />";
}

/**
 * Standardize paths for our various types of allowed path formats.
 * 
 * @param   string    File to process, in a variety of path formats
 * @param   string    Type of file we're working with: css, js, or img
 * @return  string    URL path to file, relative to root, with timestamp appended.
 */
function get_path($file, $type)
{
	global $document_root;
	// Don't do anything with complete URLs
	if (preg_match('|^https?://|', $file)) return $file;
	
	// Make sure DOCUMENT_ROOT does not have a trailing slash
	$document_root = preg_replace('|/$|', '', $document_root);
	if (strpos($file, '/') === 0) {
		// Path from site root
		$fs_path  = $document_root . $file;
		$url_path = $file . asset_timestamp($fs_path);
	} else {
		// Get file subdirectory
		$subdir = get_file_subdir($type);
		// Is this a WordPress site?
		if (is_wordpress()) {
			// Path relative to WP theme directory
			$fs_path  = TEMPLATEPATH . $subdir . '/' . $file;		
			$url_path = str_replace($document_root, '', $fs_path) . asset_timestamp($fs_path);
		} else {
			// Path relative to site root
			$fs_path  = $document_root . $subdir . '/' . $file;	
			$url_path = $subdir . '/' . $file . asset_timestamp($fs_path);
		}
	}
	if (defined('ASSET_HOST')) {
		$url_path = asset_host_url($url_path, $type);
	}
	return $url_path;
}

/**
 * Sets default subdirectories for various static file types
 * 
 * @param   string    Type of file we're looking for
 * @return  string    Name of subdirectory where file can be found
 */
function get_file_subdir($type)
{
	global $asset_helper_subdir;
	$defaults = array('css' => '/css', 'js' => '/js', 'img' => '/images');
	// Is this directory overridden?
	if (isSet($asset_helper_subdir[$type])) {
		$subdir = $asset_helper_subdir[$type];
	} else {
		// WordPress standard is to keep CSS in the theme directory,
		// so let's go with that unless a subdirectory is specified.
		if (($type == 'css') && (is_wordpress())) {
			$subdir = '';
		} else {
			$subdir = $defaults[$type];
		}
	}
	return $subdir;
}

/**
 * Gets the last-modified time for the specified file
 * 
 * @param   string     File to be checked
 * @return  integer    Unix timestamp for last-modified time
 */
function asset_timestamp($fs_path)
{
	if (file_exists($fs_path)) {
		return '?' . filemtime($fs_path);
	} else {
		return '';
	}
}

/**
 * Gets the asset host URL for the specified file
 * 
 * @param   string    URL path for requested file
 * @param   string    Type of file we're working with: css, js, or img
 * @return  string    Full path to file, including asset host and protocol.
 *                    Returns original file path if ASSET_HOST is not defined.
 */
function asset_host_url($file, $type)
{
	if (!defined('ASSET_HOST')) return $file;
	$asset_host = preg_replace('|^https?://|', '', ASSET_HOST);
	
	// Check format of asset host definition and assign hostnames
	preg_match('/(\[([0-9]+)?\])/', $asset_host, $matches);
	if (isSet($matches[2])) {
		$num_hosts   = $matches[2];
		$placeholder = "[${matches[2]}]";
		$num  = rand(1, $num_hosts);
		$host = str_replace($placeholder, $num, $asset_host);
	} elseif (isSet($matches[1])) {
		// If a number of hosts isn't specified, assign one based on file type
		$default_hosts = array('css' => '1', 'js' => '2', 'img' => '3');
		$placeholder = "[]";
		$num  = $default_hosts[$type];
		$host = str_replace($placeholder, $num, $asset_host);
	} else {
		$host = $asset_host;
	}
	// Assemble and return complete file URL
	$protocol = (use_ssl()) ? 'https' : 'http';
	return "${protocol}://${host}${file}";
}

/**
 * Checks for some WordPress-defined constants to see if this is a WP-based site.
 * 
 * @return  boolean    True if this appears to be a WordPress site, otherwise false.
 */
function is_wordpress()
{
	return ((defined('ABSPATH')) && (defined('TEMPLATEPATH')));
}

/**
 * Checks if we're on a SSL-enabled page
 * 
 * @return  boolean    True if this page is accessed via SSL, otherwise false
 */
function use_ssl()
{
	return ((isSet($_SERVER['HTTPS'])) && ($_SERVER['HTTPS'] == 'on'));
}




