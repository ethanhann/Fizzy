<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';
$app = new Silex\Application();

$app->get('/person', 'Acme\\Person\\PersonController::get');

//$app->register(new JDesrosiers\Silex\Provider\JmsSerializerServiceProvider(), array(
//    'serializer.srcDir' => __DIR__.'/vendor/jms/serializer/src',
////    'serializer.cacheDir' => __DIR__.'/cache',
//));

$app['debug'] = true;
$app->run();
