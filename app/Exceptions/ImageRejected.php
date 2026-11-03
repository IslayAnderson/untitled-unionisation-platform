<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An upload that couldn't be read as an image.
 */
class ImageRejected extends RuntimeException {}
