<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Eeh\Fizzy;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Exception;
use JMS\Serializer\SerializerInterface;
use KPhoen\Provider\NegotiationServiceProvider;
use Macedigital\Silex\Provider\SerializerProvider;
use Negotiation\FormatNegotiatorInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zend\Code\Reflection\ClassReflection;
use Webmozart\Json\JsonDecoder;
use Zend\Code\Reflection\MethodReflection;

class App
{
    /**
     * @var object
     */
    private $config;

    /**
     * @var Application
     */
    private $app;

    /**
     * @var FormatNegotiatorInterface
     */
    private $formatNegotiator;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ClassLoader
     */
    private $classLoader;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(string $configFilePath, ClassLoader $classLoader, ContainerInterface $container)
    {
        // Load annotations.
        AnnotationRegistry::registerLoader('class_exists');

        // Load config.
        $this->config = (new JsonDecoder())->decodeFile($configFilePath);
        $this->classLoader = $classLoader;

        $this->app = new Application();
        $this->app->register(new SerializerProvider);
        $this->app->register(new NegotiationServiceProvider(array(
            'json' => array('application/json'),
        )));

        // Services
        $this->formatNegotiator = $this->app['format.negotiator'];
        $this->serializer = $this->app['serializer'];

        $app['debug'] = true;

        $this->container = $container;
    }

    public function configure()
    {
        $this->registerRoutes();
        return $this;
    }

    public function run()
    {
        $this->app->run();
    }

    protected function getWebServiceClasses($namespacePrefix, $sourcePath)
    {
        $path = $sourcePath;
        $recursiveIteratorIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $regexIterator = new RegexIterator($recursiveIteratorIterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
        $fullyQualifiedClasses = [];
        foreach ($regexIterator as $absolutePath => $object) {
            $namespacePathArray = explode($sourcePath, $absolutePath);
            $namespacePath = array_pop($namespacePathArray);
            $fullyQualifiedClass = sprintf('%s%s\\%s',
                $namespacePrefix,
                pathinfo($namespacePath, PATHINFO_DIRNAME),
                pathinfo($namespacePath, PATHINFO_FILENAME)
            );
            $fullyQualifiedClasses[] = str_replace('/', '\\', trim($fullyQualifiedClass, '/'));
        }
        $webServiceInterface = WebServiceControllerInterface::class;
        return array_filter($fullyQualifiedClasses, function ($class) use ($webServiceInterface) {
            $implementedInterfaces = class_implements($class);
            return $implementedInterfaces !== false ?
                in_array(WebServiceControllerInterface::class, $implementedInterfaces) :
                false;
        });
    }

    function registerRoutes()
    {
        // Auto-register routes.
        if (!isset($this->classLoader->getPrefixesPsr4()[$this->config->namespacePrefix . '\\'])) {
            throw new Exception(sprintf('Namespace prefix "%s" defined in the config was not found in the autoloader.',
                $this->config->namespacePrefix
            ));
        }
        $sourcePath = array_pop($this->classLoader->getPrefixesPsr4()[$this->config->namespacePrefix . '\\']);
        $routes = [];
        $app = $this->app;
        $formatNegotiator = $this->formatNegotiator;
        $serializer = $this->serializer;
        $config = $this->config;
        foreach ($this->getWebServiceClasses($this->config->namespacePrefix, $sourcePath) as $webServiceClass) {
            // Need to get methods and DTOs from class
            $classReflection = new ClassReflection($webServiceClass);
            $httpMethodNames = $this->config->httpMethodNames;
            $httpMethodReflections = array_filter(
                $classReflection->getMethods(),
                function ($methodReflection) use ($httpMethodNames) {
                    return in_array($methodReflection->name, $httpMethodNames);
                }
            );
            // @todo add a check to make sure DTOs are unique. This might happen implicitly when registering routes.
            // Call for each http method/DTO in web service
            /** @var MethodReflection $httpMethodReflection */
            foreach ($httpMethodReflections as $httpMethodReflection) {
                // This assumes that the first argument of the HTTP method is a DTO.
                $httpMethodReflectionPrototype = $httpMethodReflection->getPrototype();
                $requestDtoClass = array_shift($httpMethodReflectionPrototype['arguments'])['type'];
                $requestDtoClassReflection = new ClassReflection($requestDtoClass);
                $requestDtoProperties = $requestDtoClassReflection->getProperties();
                $returnDtoClass = $httpMethodReflection->getReturnType();
                $returnDtoProperties = (new ClassReflection($returnDtoClass))->getProperties();
                $requestMethod = $httpMethodReflectionPrototype['name'];
                $route = '/' . $this->config->baseUrl . '/' . $requestDtoClassReflection->getShortName();
                $routes[] = new class(
                    $route,
                    $requestDtoClass,
                    $requestDtoProperties,
                    $returnDtoClass,
                    $returnDtoProperties
                )
                {
                    public $path;
                    public $requestDto;
                    public $requestDtoParameters;
                    public $returnDto;
                    public $returnDtoProperties;

                    public function __construct(string $path,
                                                string $requestDto,
                                                array $requestDtoParameters,
                                                string $returnDto,
                                                array $returnDtoProperties)
                    {
                        $this->path = $path;
                        $this->requestDto = $requestDto;
                        $this->requestDtoParameters = $requestDtoParameters;
                        $this->returnDto = $returnDto;
                        $this->returnDtoProperties = $returnDtoProperties;
                    }
                };
                $app->get($route, function () use ($app, $formatNegotiator, $serializer, $config, $webServiceClass, $requestDtoClass, $requestMethod) {
                    /** @var Request $httpRequest */
                    $httpRequest = $app['request'];
                    // Convert request parameters to the request DTO.
                    $params = $serializer->serialize($httpRequest->query->all(), 'json');
                    $requestDto = $serializer->deserialize($params, $requestDtoClass, 'json');
                    // Get the response DTO by calling the HTTP method of the web service class, with the request DTO.
                    $responseDto = (new $webServiceClass)->$requestMethod($requestDto);
                    // Content negotiation
                    $format = $formatNegotiator->getBestFormat(
                        implode(',', $httpRequest->getAcceptableContentTypes()),
                        $config->contentNegotiation->priorities
                    );
                    return new Response($serializer->serialize($responseDto, $format), 200, array(
                        'Content-Type' => $app['request']->getMimeType($format)
                    ));
                });
            }
        };

        /**
         * Register custom _routes meta route
         */
        $app->get($config->baseUrl . '/_routes', function () use ($app, $formatNegotiator, $serializer, $config, $routes) {
            $httpRequest = $app['request'];
            $format = $formatNegotiator->getBestFormat(
                implode(',', $httpRequest->getAcceptableContentTypes()),
                $config->contentNegotiation->priorities
            );
            $serializedData = $serializer->serialize($routes, $format);
            $responseCode = Response::HTTP_OK;
            if ($serializedData === false) {
                $serializedData = '';
                $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
            return new Response($serializedData, $responseCode, array(
                'Content-Type' => $app['request']->getMimeType($format)
            ));
        });
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     * @return App
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }
}
