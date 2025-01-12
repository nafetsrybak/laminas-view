<?php

declare(strict_types=1);

namespace Laminas\View\Resolver;

use Laminas\Stdlib\SplStack;
use Laminas\View\Exception;
use Laminas\View\Renderer\RendererInterface as Renderer;
use SplFileInfo;
use Traversable;

use function count;
use function file_exists;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function ltrim;
use function pathinfo;
use function preg_match;
use function rtrim;
use function sprintf;
use function strpos;
use function strtolower;

use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

/**
 * Resolves view scripts based on a stack of paths
 *
 * @psalm-type PathStack = SplStack<string>
 */
class TemplatePathStack implements ResolverInterface
{
    public const FAILURE_NO_PATHS  = 'TemplatePathStack_Failure_No_Paths';
    public const FAILURE_NOT_FOUND = 'TemplatePathStack_Failure_Not_Found';

    /**
     * Default suffix to use
     *
     * Appends this suffix if the template requested does not use it.
     *
     * @var string
     */
    protected $defaultSuffix = 'phtml';

    /** @var PathStack */
    protected $paths;

    /**
     * Reason for last lookup failure
     *
     * @var false|string
     */
    protected $lastLookupFailure = false;

    /**
     * Flag indicating whether or not LFI protection for rendering view scripts is enabled
     *
     * @var bool
     */
    protected $lfiProtectionOn = true;

    /**@-*/

    /**
     * Constructor
     *
     * @param  null|array<string, mixed>|Traversable<string, mixed> $options
     */
    public function __construct($options = null)
    {
        /** @psalm-var PathStack $paths */
        $paths       = new SplStack();
        $this->paths = $paths;
        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * Configure object
     *
     * @param  array<string, mixed>|Traversable<string, mixed> $options
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public function setOptions($options)
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (! is_array($options) && ! $options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Expected array or Traversable object; received "%s"',
                is_object($options) ? get_class($options) : gettype($options)
            ));
        }

        foreach ($options as $key => $value) {
            switch (strtolower($key)) {
                case 'lfi_protection':
                    $this->setLfiProtection($value);
                    break;
                case 'script_paths':
                    $this->addPaths($value);
                    break;
                case 'default_suffix':
                    $this->setDefaultSuffix($value);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Set default file suffix
     *
     * @param  string $defaultSuffix
     * @return TemplatePathStack
     */
    public function setDefaultSuffix($defaultSuffix)
    {
        $this->defaultSuffix = (string) $defaultSuffix;
        $this->defaultSuffix = ltrim($this->defaultSuffix, '.');
        return $this;
    }

    /**
     * Get default file suffix
     *
     * @return string
     */
    public function getDefaultSuffix()
    {
        return $this->defaultSuffix;
    }

    /**
     * Add many paths to the stack at once
     *
     * @param  list<string> $paths
     * @return TemplatePathStack
     */
    public function addPaths(array $paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
        return $this;
    }

    /**
     * Rest the path stack to the paths provided
     *
     * @param  PathStack|list<string> $paths
     * @return TemplatePathStack
     * @throws Exception\InvalidArgumentException
     */
    public function setPaths($paths)
    {
        if ($paths instanceof SplStack) {
            $this->paths = $paths;

            return $this;
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (is_array($paths)) {
            $this->clearPaths();
            $this->addPaths($paths);

            return $this;
        }

        throw new Exception\InvalidArgumentException(
            "Invalid argument provided for \$paths, expecting either an array or SplStack object"
        );
    }

    /**
     * Normalize a path for insertion in the stack
     *
     * @param  string $path
     * @return string
     */
    public static function normalizePath($path)
    {
        $path  = rtrim($path, '/');
        $path  = rtrim($path, '\\');
        $path .= DIRECTORY_SEPARATOR;
        return $path;
    }

    /**
     * Add a single path to the stack
     *
     * @param  string $path
     * @return TemplatePathStack
     * @throws Exception\InvalidArgumentException
     */
    public function addPath($path)
    {
        if (! is_string($path)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid path provided; must be a string, received %s',
                gettype($path)
            ));
        }
        $this->paths[] = static::normalizePath($path);
        return $this;
    }

    /**
     * Clear all paths
     *
     * @return void
     */
    public function clearPaths()
    {
        /** @psalm-var PathStack $paths */
        $paths       = new SplStack();
        $this->paths = $paths;
    }

    /**
     * Returns stack of paths
     *
     * @return PathStack
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Set LFI protection flag
     *
     * @param  bool $flag
     * @return TemplatePathStack
     */
    public function setLfiProtection($flag)
    {
        $this->lfiProtectionOn = (bool) $flag;
        return $this;
    }

    /**
     * Return status of LFI protection flag
     *
     * @return bool
     */
    public function isLfiProtectionOn()
    {
        return $this->lfiProtectionOn;
    }

    /**
     * Retrieve the filesystem path to a view script
     *
     * @param  string $name
     * @return string
     * @throws Exception\DomainException
     */
    public function resolve($name, ?Renderer $renderer = null)
    {
        $this->lastLookupFailure = false;

        if ($this->isLfiProtectionOn() && preg_match('#\.\.[\\\/]#', $name)) {
            throw new Exception\DomainException(
                'Requested scripts may not include parent directory traversal ("../", "..\\" notation)'
            );
        }

        if (! count($this->paths)) {
            $this->lastLookupFailure = static::FAILURE_NO_PATHS;
            return false;
        }

        // Ensure we have the expected file extension
        $defaultSuffix = $this->getDefaultSuffix();
        if (pathinfo($name, PATHINFO_EXTENSION) === '') {
            $name .= '.' . $defaultSuffix;
        }

        foreach ($this->paths as $path) {
            $file = new SplFileInfo($path . $name);
            if ($file->isReadable()) {
                // Found! Return it.
                if (($filePath = $file->getRealPath()) === false && 0 === strpos($path, 'phar://')) {
                    // Do not try to expand phar paths (realpath + phars == fail)
                    $filePath = $path . $name;
                    if (! file_exists($filePath)) {
                        break;
                    }
                }

                return $filePath;
            }
        }

        $this->lastLookupFailure = static::FAILURE_NOT_FOUND;
        return false;
    }

    /**
     * Get the last lookup failure message, if any
     *
     * @return false|string
     */
    public function getLastLookupFailure()
    {
        return $this->lastLookupFailure;
    }
}
