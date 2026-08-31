<?php

namespace App\Services\Installer;

use RuntimeException;

/**
 * A controlled, user-safe installer failure. The message is always
 * presentation-ready (already translated, no stack trace, no credentials)
 * and is shown verbatim on the retry screen.
 */
class InstallerException extends RuntimeException
{
}
