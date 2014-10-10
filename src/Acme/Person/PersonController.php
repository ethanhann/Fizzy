<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Acme\Person;

use Acme\IService;
use Silex\Application;
//use Symfony\Component\HttpFoundation\Request;

/*
 * IService interface indicates that that the class is a web api controller.
 */
class PersonController implements IService
{
    /**
     * @return string
     */
    public function get(PersonRequest $request)
    {
        return 'blah';
    }
}


