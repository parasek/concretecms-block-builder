<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class ControllerUseStatement
{
    public string $className;

    public function __construct(
        string $className,
        public ?string $alias = null,
    ) {
        $className = ltrim(trim($className), '\\');
        if ($className === '') {
            throw new \InvalidArgumentException('A controller use statement class name cannot be empty.');
        }
        if ($alias !== null && !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $alias)) {
            throw new \InvalidArgumentException(sprintf('The controller use alias "%s" is invalid.', $alias));
        }

        $this->className = $className;
    }

    public function getKey(): string
    {
        if ($this->alias !== null) {
            return $this->alias;
        }

        $namespaceSeparatorPosition = strrpos($this->className, '\\');

        return $namespaceSeparatorPosition === false
            ? $this->className
            : substr($this->className, $namespaceSeparatorPosition + 1);
    }
}
