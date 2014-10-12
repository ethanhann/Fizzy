<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Acme\Person;

use JMS\Serializer\Annotation AS JMS;

class PersonListRequest
{
    /**
     * @JMS\Type("array")
     */
    private $personList;

    public function getPeopleList()
    {
        return $this->personList;
    }

    public function setPersonList($personList)
    {
        $this->personList = $personList;
    }
}
