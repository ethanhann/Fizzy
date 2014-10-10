<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

use KPhoen\Provider\NegotiationServiceProvider;
use Macedigital\Silex\Provider\SerializerProvider;
use Symfony\Component\HttpFoundation\Response;

//use Zend\Code\Reflection\MethodReflection;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app->register(new SerializerProvider);
$app->register(new NegotiationServiceProvider(array(
    'json' => array('application/json'),
)));

/*
 * Need scan src directory for controllers, then auto register them.
 */
$app->get('/person', 'Acme\\Person\\PersonController::get');
$app->get('/person2', function () use ($app) {
    $request = new \Acme\Person\PersonRequest();
    $response = (new \Acme\Person\PersonController())->get($request);
    $formatNegotiator = $app['format.negotiator'];
    $httpRequest = $app['request'];
    $priorities = array('json', 'xml');
    $acceptableContentTypes = implode(',', $httpRequest->getAcceptableContentTypes());
    $format = $formatNegotiator->getBestFormat($acceptableContentTypes, $priorities);
    return new Response($app['serializer']->serialize($response, $format), 200, array(
        'Content-Type' => $app['request']->getMimeType($format)
    ));
});

//$returnTagTypes = $method->getDocBlock()->getTag('return')->getTypes();
//var_dump($returnTagTypes);

// Need to create custom routes on the fly via reflection.
// Need to serialize result of IService verb methods

$app['debug'] = true;
$app->run();
