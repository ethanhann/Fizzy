<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Acme\Person;

use Ehann\WebServiceControllerInterface;

/*
 * WebServiceControllerInterface interface indicates that that the class is a web api controller.
 */
class PersonController implements WebServiceControllerInterface
{
    public function get(PersonRequest $request) : Person
    {
        // Use $request to do something.
        return new Person();
    }

    public function getList(PersonListRequest $request) : array
    {
        // Use $request to do something.
        return [new Person(), new Person()];
    }
}
