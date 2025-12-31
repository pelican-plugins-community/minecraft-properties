<?php

namespace Pelican\MinecraftProperties\Services;

/**
 * Service responsible for parsing server.properties content and mapping
 * between form field keys and properties. Extracting this logic to a
 * dedicated service keeps the page class focused on UI concerns.
 */
class PropertiesService
{
    private array $propertyMapping;
    private array $fieldTypes;
    private array $defaultValues;

    public function __construct(array $propertyMapping, array $fieldTypes, array $defaultValues)
    {
        $this->propertyMapping = $propertyMapping;
        $this->fieldTypes = $fieldTypes;
        $this->defaultValues = $defaultValues;
    }

    /**
     * Parse a server.properties-like content into an associative array.
     *
     * @param string $content
     * @return array<string,string>
     */
    public function parseProperties(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2) + [null, null]);
            if ($key && $value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Map form state to properties suitable for writing back to server.properties.
     *
     * @param array $state form state keyed by field names
     * @param array $originalProps the parsed original properties (key => value)
     * @param array $availableProperties list of property keys present in the file
     * @return array<string,string>
     */
    public function mapStateToProperties(array $state, array $originalProps, array $availableProperties): array
    {
        $props = $originalProps;

        foreach ($this->propertyMapping as $field => $property) {
            if (!in_array($property, $availableProperties, true)) {
                continue;
            }

            $value = $state[$field] ?? ($this->defaultValues[$property] ?? null);

            if (is_bool($value)) {
                $props[$property] = $value ? 'true' : 'false';
            } elseif ($value !== null) {
                $props[$property] = (string) $value;
            }
        }

        return $props;
    }

    /**
     * Convert parsed properties into form field values (useful when raw content
     * was edited) — returns an array mapping field => typed value.
     *
     * @param array $parsed parsed properties (propertyKey => value)
     * @return array<string,mixed>
     */
    public function mapParsedToFormData(array $parsed): array
    {
        $reverse = array_flip($this->propertyMapping);
        $formData = [];
        foreach ($parsed as $prop => $value) {
            if (!isset($reverse[$prop])) {
                continue;
            }
            $field = $reverse[$prop];
            $type = $this->fieldTypes[$field] ?? 'string';
            if ($type === 'bool') {
                $formData[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } else {
                $formData[$field] = $value;
            }
        }
        return $formData;
    }
}
