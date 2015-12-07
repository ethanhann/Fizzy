<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Acme\Company;

use Ehann\WebServiceControllerInterface;

class CompanyController implements WebServiceControllerInterface
{
    public function get(CompanyRequest $request) : Company
    {
        // Use $request to do something.
        return new Company();
    }
}
