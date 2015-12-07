<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Acme\Company;

use JMS\Serializer\Annotation AS JMS;

class CompanyRequest
{
    /**
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @JMS\Type("string")
     */
    private $location;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     * @return Company
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }
}
