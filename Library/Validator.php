<?php

declare(strict_types=1);

namespace Library;

use Exceptions\ValidationException;

final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function validate(array $rules): array
    {
        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $ruleArray = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleArray as $rule) {
                $this->applyRule($field, $rule, $value);
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        return array_intersect_key($this->data, array_flip(array_keys($rules)));
    }

    private function applyRule(string $field, string $rule, $value): void
    {
        $parameters = [];
        if (strpos($rule, ':') !== false) {
            [$rule, $paramString] = explode(':', $rule);
            $parameters = explode(',', $paramString);
        }

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->addError($field, "The {$field} field is required.");
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "The {$field} must be a valid email address.");
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "The {$field} must be a number.");
                }
                break;

            case 'min':
                $min = (int)$parameters[0];
                if (is_string($value) && strlen($value) < $min) {
                    $this->addError($field, "The {$field} must be at least {$min} characters.");
                } elseif (is_numeric($value) && $value < $min) {
                    $this->addError($field, "The {$field} must be at least {$min}.");
                }
                break;

            case 'max':
                $max = (int)$parameters[0];
                if (is_string($value) && strlen($value) > $max) {
                    $this->addError($field, "The {$field} may not be greater than {$max} characters.");
                } elseif (is_numeric($value) && $value > $max) {
                    $this->addError($field, "The {$field} may not be greater than {$max}.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
