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
use Zend\Code\Reflection\ClassReflection;

// Load composer dependencies.
$loader = require_once __DIR__ . '/../vendor/autoload.php';
// Load annotations.
AnnotationRegistry::registerLoader('class_exists');

// Create and configure app.
$app = new Silex\Application();
$app->register(new SerializerProvider);
$app->register(new NegotiationServiceProvider(array(
    'json' => array('application/json'),
)));

function get_web_service_classes($namespacePrefix, $sourcePath)
{
    $webServiceInterface = 'Ehann\IWebServiceController';
    $path = join(DIRECTORY_SEPARATOR, [$sourcePath, $namespacePrefix]);
    $recursiveIteratorIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
    $regexIterator = new RegexIterator($recursiveIteratorIterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
    $fullyQualifiedClasses = [];
    foreach ($regexIterator as $absolutePath => $object) {
        $namespacePathArray = explode($sourcePath, $absolutePath);
        $namespacePath = array_pop($namespacePathArray);
        $fullyQualifiedClasses[] = sprintf('%s\\%s', pathinfo($namespacePath, PATHINFO_DIRNAME), pathinfo($namespacePath, PATHINFO_FILENAME));
    }
    return array_filter($fullyQualifiedClasses, function ($class) use ($webServiceInterface) {
        return in_array($webServiceInterface, class_implements($class));
    });
}

/*
 * Auto-register routes for the Acme namespace.
 */
$namespacePrefix = 'Acme';
$sourcePath = array_pop($loader->getPrefixes()[$namespacePrefix]);
foreach (get_web_service_classes($namespacePrefix, $sourcePath) as $webServiceClass) {
    // Need to get methods and DTOs from class
    $classReflection = new ClassReflection($webServiceClass);
    $httpMethodReflections = array_filter($classReflection->getMethods(), function ($methodReflection) {
        return in_array($methodReflection->name, ['get', 'getList', 'post', 'put', 'delete']);
    });
    // @todo add a check to make sure DTOs are unique. This might happen implicitly when registering routes.
    // Call for each http method/DTO in web service
    foreach ($httpMethodReflections as $httpMethodReflection) {
        // This assumes that the first argument of the HTTP method is a DTO.
        $httpMethodReflectionPrototype = $httpMethodReflection->getPrototype();
        $requestDtoClass = array_shift($httpMethodReflectionPrototype['arguments'])['type'];
        $requestMethod = $httpMethodReflectionPrototype['name'];
        $route = '/' . pathinfo($requestDtoClass, PATHINFO_FILENAME);
        $app->get($route, function () use ($app, $webServiceClass, $requestDtoClass, $requestMethod) {
            $serializer = $app['serializer'];
            $httpRequest = $app['request'];
            $formatNegotiator = $app['format.negotiator'];
            // Convert request parameters to the request DTO.
            $params = $serializer->serialize($httpRequest->query->all(), 'json');
            $requestDto = $serializer->deserialize($params, $requestDtoClass, 'json');
            // Get the response DTO by calling the HTTP method of the web service class, with the request DTO.
            $responseDto = (new $webServiceClass)->$requestMethod($requestDto);
            // Content negotiation
            $priorities = array('json', 'xml');
            $acceptableContentTypes = implode(',', $httpRequest->getAcceptableContentTypes());
            $format = $formatNegotiator->getBestFormat($acceptableContentTypes, $priorities);
            return new Response($serializer->serialize($responseDto, $format), 200, array(
                'Content-Type' => $app['request']->getMimeType($format)
            ));
        });
    }
};

$app['debug'] = true;
$app->run();
