<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Acme\Person;

use Ehann\IWebServiceController;

/*
 * IService interface indicates that that the class is a web api controller.
 */
class PersonController implements IWebServiceController
{
    public function get(PersonRequest $request)
    {
        // Use $request to do something.
        return new Person();
    }

    public function getList(PersonListRequest $request)
    {
        // Use $request to do something.
        return [new Person(), new Person()];
    }
}
