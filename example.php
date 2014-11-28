#! /usr/bin/env hhvm
<?hh

// Find the composer autoloader
$basedir = __DIR__;

do {
    if(file_exists($basedir . '/composer.json') && file_exists($basedir . '/vendor/autoload.php')){
        require_once($basedir . '/vendor/autoload.php');
        break;
    }
    $basedir = dirname($basedir);
    if($basedir === '/'){
        die('You need to set up the project dependencies using the following commands:' . PHP_EOL .
            'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
            'php composer.phar install' . PHP_EOL);
    }
} while(true);

use kilahm\Clio\Clio;

$clio = Clio::fromCli();

$clio
    ->arg('arg1')
    ->arg('arg2')->describedAs('The second argument')
    ->opt('a')->describedAs(str_repeat('This is a really long description. ', 10))
    ->opt('long-option')
    ->opt('another-option')->describedAs('That one option that does nothing.');

$clio->help();
