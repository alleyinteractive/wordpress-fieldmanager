<?php
if (strpos($_SERVER['argv'][0] ?? '', 'doctum.phar') === false) {
	exit("This script must be run via doctum\n");
}

use Doctum\Doctum;
use Doctum\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/php')
    ->in(__DIR__ . '/php/datasource')
    ->append([__DIR__ . '/fieldmanager.php']);

return new Doctum($iterator, [
    'title'                => 'Fieldmanager API Documentation',
    'build_dir'            => __DIR__ . '/docs',
    'cache_dir'            => __DIR__ . '/cache',
    'remote_repository'    => new GitHubRemoteRepository('alleyinteractive/wordpress-fieldmanager', __DIR__),
    'default_opened_level' => 2,
]);

