<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    public static function make(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $rulesList = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($rulesList as $rule) {
                $rule = trim($rule);
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = 'This field is required.';
                }

                if ($rule === 'email' && !filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Invalid email address.';
                }

                if (str_starts_with($rule, 'min:')) {
                    $length = (int) substr($rule, 4);
                    if (is_numeric($value)) {
                        if ((float) $value < $length) {
                            $errors[$field] = "Must be at least {$length}.";
                        }
                    } elseif (strlen((string) $value) < $length) {
                        $errors[$field] = "Must be at least {$length} characters.";
                    }
                }

                if (str_starts_with($rule, 'max:')) {
                    $length = (int) substr($rule, 4);
                    if (is_numeric($value)) {
                        if ((float) $value > $length) {
                            $errors[$field] = "Must be less than or equal to {$length}.";
                        }
                    } elseif (strlen((string) $value) > $length) {
                        $errors[$field] = "Must be at most {$length} characters.";
                    }
                }

                if ($rule === 'confirmed') {
                    $confirmation = $data[$field . '_confirmation'] ?? null;
                    if ($value !== $confirmation) {
                        $errors[$field] = 'The confirmation does not match.';
                    }
                }

                if ($rule === 'boolean') {
                    $lower = strtolower((string) $value);
                    if (!in_array($lower, ['1', '0', 'true', 'false', 'on', 'off', 'yes', 'no'], true)) {
                        $errors[$field] = 'Invalid boolean value.';
                    }
                }

                if ($rule === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = 'This field must be numeric.';
                }

                if (str_starts_with($rule, 'in:')) {
                    $allowed = array_map('trim', explode(',', substr($rule, 3)));
                    if (!in_array((string) $value, $allowed, true)) {
                        $errors[$field] = 'Invalid selection.';
                    }
                }
            }
        }

        return $errors;
    }
}
