<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Exception;

use Exception;
use Flarum\Foundation\KnownError;

class MethodNotAllowedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'method_not_allowed';
    }
}
