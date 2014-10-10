<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Acme\Request\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use JMS\SerializerBundle\Serializer\SerializerInterface;
use JMS\SerializerBundle\Exception\XmlErrorException;

class SerializedParamConverter implements ParamConverterInterface
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function supports(ConfigurationInterface $configuration)
    {
        if (!$configuration->getClass()) {
            return false;
        }
        // for simplicity, everything that has a "class" type hint is supported
        return true;
    }

    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        $class = $configuration->getClass();

        try {
            $object = $this->serializer->deserialize(
                $request->getContent(),
                $class,
                'xml'
            );
        }
        catch (XmlErrorException $e) {
            throw new NotFoundHttpException(sprintf('Could not deserialize request content to object of type "%s"',
                $class));
        }

        // set the object as the request attribute with the given name
        // (this will later be an argument for the action)
        $request->attributes->set($configuration->getName(), $object);
    }
}
