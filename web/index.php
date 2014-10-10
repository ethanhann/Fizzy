<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationRegistry;
use KPhoen\Provider\NegotiationServiceProvider;
use Macedigital\Silex\Provider\SerializerProvider;
use Symfony\Component\HttpFoundation\Response;

// Autoload composer dependencies.
require_once __DIR__ . '/../vendor/autoload.php';
// Autoload annotations.
AnnotationRegistry::registerLoader('class_exists');

// Create and configure app.
$app = new Silex\Application();
$app->register(new SerializerProvider);
$app->register(new NegotiationServiceProvider(array(
    'json' => array('application/json'),
)));

/*
 * Need scan src directory for controllers, then auto register them.
 */
//$app->get('/person', 'Acme\\Person\\PersonController::get');
$app->get('/person', function () use ($app) {
    $serializer = $app['serializer'];
    $httpRequest = $app['request'];
    $formatNegotiator = $app['format.negotiator'];
    $params = $serializer->serialize($httpRequest->query->all(), 'json');

    $requestDto = $serializer->deserialize($params, 'Acme\\Person\\PersonRequest', 'json');
    $responseDto = (new \Acme\Person\PersonController())->get($requestDto);
    // Content negotiation
    $priorities = array('json', 'xml');
    $acceptableContentTypes = implode(',', $httpRequest->getAcceptableContentTypes());
    $format = $formatNegotiator->getBestFormat($acceptableContentTypes, $priorities);
    return new Response($serializer->serialize($responseDto, $format), 200, array(
        'Content-Type' => $app['request']->getMimeType($format)
    ));
});

//$returnTagTypes = $method->getDocBlock()->getTag('return')->getTypes();
//var_dump($returnTagTypes);

// Need to create custom routes on the fly via reflection.
// Need to serialize result of IService verb methods

$app['debug'] = true;
$app->run();
