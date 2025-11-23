<?php
// src/Support/Validator.php
declare(strict_types=1);

namespace App\Support;

class Validator
{
    /**
     * $rules:
     * [
     *   'email' => 'required|email',
     *   'password' => 'required|min:6'
     * ]
     *
     * $messages:
     * [
     *   'email.required' => 'El correo es obligatorio',
     *   'password.min'   => 'La contraseña debe tener al menos :min caracteres'
     * ]
     */
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $ruleParts = explode('|', $ruleString);

            foreach ($ruleParts as $rulePart) {
                [$ruleName, $param] = array_pad(explode(':', $rulePart, 2), 2, null);

                $failed = false;

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || $value === '') {
                            $failed = true;
                        }
                        break;

                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $failed = true;
                        }
                        break;

                    case 'min':
                        $min = (int)$param;
                        if (is_string($value) && mb_strlen($value) < $min) {
                            $failed = true;
                        }
                        break;

                    case 'max':
                        $max = (int)$param;
                        if (is_string($value) && mb_strlen($value) > $max) {
                            $failed = true;
                        }
                        break;
                }

                if ($failed) {
                    $key = $field . '.' . $ruleName;

                    // Mensaje personalizado
                    if (isset($messages[$key])) {
                        $msg = $messages[$key];
                        if ($ruleName === 'min') {
                            $msg = str_replace(':min', (string)$param, $msg);
                        }
                        if ($ruleName === 'max') {
                            $msg = str_replace(':max', (string)$param, $msg);
                        }
                    } else {
                        // Mensaje default sencillo
                        $msg = self::defaultMessage($field, $ruleName, $param);
                    }

                    $errors[$field][] = $msg;
                }
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    protected static function defaultMessage(string $field, string $rule, ?string $param = null): string
    {
        $fieldLabel = $field; // aquí podrías mapear "email" => "correo electrónico"

        return match ($rule) {
            'required' => "El campo {$fieldLabel} es obligatorio.",
            'email'    => "El campo {$fieldLabel} debe ser un correo válido.",
            'min'      => "El campo {$fieldLabel} debe tener al menos {$param} caracteres.",
            'max'      => "El campo {$fieldLabel} no debe exceder {$param} caracteres.",
            default    => "El campo {$fieldLabel} no cumple la regla {$rule}.",
        };
    }
}
