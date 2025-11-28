<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedEmailDomain implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Le champ :attribute doit être une adresse email valide.');

            return;
        }

        // Récupérer les domaines autorisés depuis la configuration
        $allowedDomains = config('auth.allowed_email_domains', []);

        // Si aucun domaine n'est configuré, accepter tous les domaines
        if (empty($allowedDomains)) {
            return;
        }

        // Extraire le domaine de l'email
        $emailParts = explode('@', $value);
        if (count($emailParts) !== 2) {
            $fail('Le champ :attribute doit être une adresse email valide.');

            return;
        }

        $domain = strtolower($emailParts[1]);

        // Vérifier si le domaine est dans la liste autorisée
        $isAllowed = false;
        foreach ($allowedDomains as $allowedDomain) {
            $allowedDomain = strtolower(trim($allowedDomain));

            // Support des wildcards (ex: *.example.com)
            if (str_starts_with($allowedDomain, '*.')) {
                $pattern = substr($allowedDomain, 2); // Enlever "*."
                if (str_ends_with($domain, $pattern)) {
                    $isAllowed = true;
                    break;
                }
            } elseif ($domain === $allowedDomain) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            $domainsString = implode(', ', $allowedDomains);
            $fail("Le domaine de l'email doit être l'un des suivants : {$domainsString}");
        }
    }
}
