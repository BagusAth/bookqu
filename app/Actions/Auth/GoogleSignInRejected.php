<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use RuntimeException;

/**
 * A Google account that may not sign in or register (BQ-172). The reason stays internal;
 * users see one generic message.
 */
class GoogleSignInRejected extends RuntimeException {}
