<?php
/**
 * @author  Ethan Hann <ethanhann@gmail.com>
 * @license For the full copyright and license information, please view the LICENSE
 *          file that was distributed with this source code.
 */

namespace Eeh\Fizzy;


interface ContainerInterface
{
    /**
     * @param string $serviceName
     * @param $service
     * @return mixed
     */
    public function register(string $serviceName, object $service);

    /**
     * @param string $serviceName
     * @return mixed
     */
    public function resolve(string $serviceName);
}