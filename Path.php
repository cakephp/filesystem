<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Filesystem;

/**
 * Path utility class for cross-platform path manipulation.
 *
 * Provides static methods for normalizing, joining, and working with filesystem paths
 * in a platform-independent way.
 */
class Path
{
    /**
     * Normalizes a path by converting directory separators to forward slashes
     * and removing redundant separators.
     *
     * @param string $path The path to normalize
     * @param bool $trailing Whether to preserve trailing slashes
     * @return string The normalized path
     */
    public static function normalize(string $path, bool $trailing = false): string
    {
        // Convert all directory separators to forward slashes
        $path = str_replace('\\', '/', $path);

        // Remove duplicate slashes
        $normalized = preg_replace('#/+#', '/', $path);
        if ($normalized === null) {
            $normalized = $path;
        }

        // Handle trailing slash
        if (!$trailing) {
            $normalized = rtrim($normalized, '/');
        } elseif (!str_ends_with($normalized, '/') && $normalized !== '') {
            $normalized .= '/';
        }

        return $normalized;
    }

    /**
     * Makes a path relative to a base path.
     *
     * @param string $path The absolute path to make relative
     * @param string $from The base path to make relative from
     * @return string The relative path
     */
    public static function makeRelative(string $path, string $from): string
    {
        $path = static::normalize($path);
        $from = static::normalize($from);

        // Split paths into segments
        $pathParts = explode('/', trim($path, '/'));
        $fromParts = explode('/', trim($from, '/'));

        // Find common base
        $commonLength = 0;
        $maxLength = min(count($pathParts), count($fromParts));

        for ($i = 0; $i < $maxLength; $i++) {
            if ($pathParts[$i] === $fromParts[$i]) {
                $commonLength++;
            } else {
                break;
            }
        }

        // Build relative path - add .. for each directory we need to go up
        $upCount = count($fromParts) - $commonLength;
        $relativeParts = $upCount > 0 ? array_fill(0, $upCount, '..') : [];

        // Add remaining path segments
        $relativeParts = array_merge(
            $relativeParts,
            array_slice($pathParts, $commonLength),
        );

        return implode('/', $relativeParts);
    }

    /**
     * Joins path segments together.
     *
     * Preserves existing directory separators (/ or \) from the input segments.
     * Use Path::normalize() if you need to convert all separators to forward slashes.
     *
     * @param string|bool|null ...$segments Path segments to join. The last argument
     *  can be a bool|null to control trailing slash handling:
     *  - If true, ensures a trailing forward-slash is added if one doesn't exist
     *  - If false, ensures any trailing slash is removed
     *  - If null, ignores trailing slashes (leaves as-is)
     * @return string The joined path
     */
    public static function join(string|bool|null ...$segments): string
    {
        $isSlash = fn(string $char): bool => $char === '/' || $char === '\\';

        // Extract trailing parameter if last argument is bool or null (not string)
        $trailing = null;
        $lastIdx = count($segments) - 1;
        if ($lastIdx >= 0) {
            $last = $segments[$lastIdx];
            // If last is bool, or null with preceding string, it's a trailing parameter
            if (is_bool($last) || ($last === null && ($lastIdx === 0 || is_string($segments[$lastIdx - 1])))) {
                $trailing = array_pop($segments);
            }
        }

        $numParts = count($segments);
        if ($numParts === 0) {
            return $trailing === true ? '/' : '';
        }

        $path = (string)$segments[0];
        for ($i = 1; $i < $numParts; ++$i) {
            $part = $segments[$i];
            if ($part === '' || $part === null) {
                continue;
            }
            $part = (string)$part;

            $pathEndsWithSlash = $path !== '' && $isSlash($path[-1]);
            $partStartsWithSlash = $isSlash($part[0]);

            if ($pathEndsWithSlash && $partStartsWithSlash) {
                $path .= substr($part, 1);
            } elseif ($pathEndsWithSlash || $partStartsWithSlash) {
                $path .= $part;
            } else {
                $path .= '/' . $part;
            }
        }

        // Handle trailing slash
        if ($trailing === true) {
            if ($path === '' || !$isSlash($path[-1])) {
                $path .= '/';
            }
        } elseif ($trailing === false) {
            if ($path !== '' && $isSlash($path[-1])) {
                $path = substr($path, 0, -1);
            }
        }

        return $path;
    }

    /**
     * Checks if a path matches a glob pattern.
     *
     * @param string $pattern The glob pattern
     * @param string $path The path to check
     * @return bool True if the path matches the pattern
     */
    public static function matches(string $pattern, string $path): bool
    {
        $pattern = static::normalize($pattern);
        $path = static::normalize($path);

        // Convert glob pattern to regex
        $regex = '#^' . preg_quote($pattern, '#') . '$#';

        // Replace glob wildcards
        $regex = str_replace(
            ['\*\*', '\*', '\?'],
            ['.*', '[^/]*', '[^/]'],
            $regex,
        );

        return (bool)preg_match($regex, $path);
    }
}
