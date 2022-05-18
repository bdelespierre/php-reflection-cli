<?php

namespace Bdelespierre\PhpReflectionCli\Command\Output;

use Symfony\Component\Console\Input\InputInterface;

class ReflectionRenderer
{
    private const COLORS = [
        'class' => [
            'name' => 'blue',
        ],
        'method' => [
            'visibility' => [
                'public' => 'green',
                'protected' => 'yellow',
                'private' => 'red',
            ],
            'name' => 'green',
            'modifiers' => 'yellow',
            'parameter' => [
                'name' => 'magenta',
                'type' => [
                    'builtin' => 'yellow',
                    'other' => 'blue',
                    'optional' => 'white',
                ],
            ]
        ],
    ];

    public const OPT_SHOW_NAMESPACES = 1;
    public const OPT_SHOW_PARAMETER_TYPES = 2;
    public const OPT_MULTILINE_METHOD_ARGUMENTS = 3;
    public const OPT_ESCAPE_NAMESPACES = 4;

    /**
     * @var array<self::OPT_*, bool>
     */
    private array $options = [
        self::OPT_SHOW_NAMESPACES => true,
        self::OPT_SHOW_PARAMETER_TYPES => true,
        self::OPT_MULTILINE_METHOD_ARGUMENTS => true,
        self::OPT_ESCAPE_NAMESPACES => false,
    ];

    /**
     * @param self::OPT_* $name
     */
    public function setOption(int $name, bool $value): void
    {
        $this->options[$name] = $value;
    }

    public function renderMethodPrototype(\ReflectionMethod $method): string
    {
        return sprintf(
            '%s %s::%s(%s)%s',
            $this->renderModifiers($method),
            $this->renderClassName($method->getDeclaringClass()),
            $this->renderMethodName($method),
            $this->renderMethodArguments($method),
            $this->renderMethodReturnType($method->getReturnType()),
        );
    }

    public function renderModifiers(\ReflectionMethod $method): string
    {
        $rendered = '';

        switch (true) {
            case $method->isPublic():
                $color =  self::COLORS['method']['visibility']['public'];
                $label = '+';
                break;

            case $method->isProtected():
                $color = self::COLORS['method']['visibility']['protected'];
                $label = '-';
                break;

            case $method->isPrivate():
                $color = self::COLORS['method']['visibility']['private'];
                $label = '-';
                break;
        }

        assert(isset($color, $label));

        $modifiers = '';

        if ($method->isFinal()) {
            $modifiers .= 'final';
        }

        if ($method->isAbstract()) {
            $modifiers .= 'abstract';
        }

        return sprintf(
            '<fg=%s>%s</><fg=%s>%s</>',
            $color,
            $label,
            self::COLORS['method']['modifiers'],
            $modifiers,
        );
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    public function renderClassName(\ReflectionClass|string $class): string
    {
        if ($class instanceof \ReflectionClass) {
            $class = $class->getName();
        }

        if ($this->options[self::OPT_SHOW_NAMESPACES] == false) {
            $class = ltrim(substr($class, strrpos($class, '\\') ?: 0), "\\");
        }

        if ($this->options[self::OPT_ESCAPE_NAMESPACES] == true) {
            $class = str_replace('\\', '\\\\', $class);
        }

        return sprintf(
            '<fg=%s>%s</>',
            self::COLORS['class']['name'],
            $class,
        );
    }

    public function renderMethodName(\ReflectionMethod $method): string
    {
        $options = '';

        if ($method->isStatic()) {
            $options .= 'underscore';
        }

        if ($method->isFinal() || $method->isAbstract()) {
            $options .= 'bold';
        }

        return sprintf(
            '<fg=%s;options=%s>%s</>',
            self::COLORS['method']['name'],
            $options,
            $method->getName(),
        );
    }

    public function renderMethodArguments(\ReflectionMethod $method): string
    {
        if (empty($method->getParameters())) {
            return "";
        }

        $params = array_map(
            [$this, 'renderMethodParameter'],
            $method->getParameters(),
        );

        if ($this->options[self::OPT_MULTILINE_METHOD_ARGUMENTS] == false) {
            return implode(', ', $params);
        }

        return sprintf(
            "\n%s\n",
            implode(",\n", array_map(fn($s) => "  {$s}", $params))
        );
    }

    public function renderMethodParameter(\ReflectionParameter $parameter): string
    {
        $rendered = $this->renderMethodParameterName($parameter);

        if (
            $this->options[self::OPT_SHOW_PARAMETER_TYPES] == true
            && $renderedType = $this->renderMethodParameterType($parameter->getType())
        ) {
            $rendered = "{$renderedType} {$rendered}";
        }

        return $rendered;
    }

    public function renderMethodParameterType(?\ReflectionType $type): string
    {
        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();
            $rendered = $this->renderClassName($name);

            if ($type->isBuiltin()) {
                $rendered = sprintf(
                    '<fg=%s>%s</>',
                    self::COLORS['method']['parameter']['type']['builtin'],
                    $name
                );
            }

            if ($type->allowsNull() && $name != 'null') {
                $rendered = sprintf(
                    '<fg=%s>?%s</>',
                    self::COLORS['method']['parameter']['type']['optional'],
                    $rendered,
                );
            }

            return $rendered;
        }

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(
                [$this, 'renderMethodParameterType'],
                $type->getTypes(),
            ));
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map(
                [$this, 'renderMethodParameterType'],
                $type->getTypes(),
            ));
        }

        return "";
    }

    public function renderMethodParameterName(\ReflectionParameter $parameter): string
    {
        return sprintf(
            '<fg=%s>$%s</>',
            self::COLORS['method']['parameter']['name'],
            $parameter->getName(),
        );
    }

    public function renderMethodReturnType(?\ReflectionType $type): string
    {
        if (is_null($type)) {
            return "";
        }

        return ': ' . $this->renderMethodParameterType($type);
    }
}
