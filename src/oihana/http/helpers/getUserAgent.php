<?php

namespace oihana\http\helpers;

use oihana\enums\ServerParam;

/**
 * Returns the User-Agent string of the current HTTP user.
 *
 * Reads the value from `$_SERVER[ServerParam::HTTP_USER_AGENT]`. Returns
 * `null` when the entry is missing (CLI invocation, header stripped, …).
 *
 * @return string|null The user agent string, or null if not available.
 */
function getUserAgent(): ?string
{
    return $_SERVER[ ServerParam::HTTP_USER_AGENT ] ?? null ;
}