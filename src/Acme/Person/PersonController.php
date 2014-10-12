<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Acme\Person;

use Acme\IWebServiceController;
use Silex\Application;

//use Symfony\Component\HttpFoundation\Request;

/*
 * IService interface indicates that that the class is a web api controller.
 */

class PersonController implements IWebServiceController
{
//    /**
//     * @param PersonRequest $request
//     * @return Acme\Person\Person
//     */
//    public function get(PersonRequest $request)
//    {
//        // use $request to do something
//        return (new Person())->setName($request->getName());
//    }

    public function get(PersonListRequest $request)
    {
        // use $request to do something
        return [new Person(), new Person()];
    }
}
