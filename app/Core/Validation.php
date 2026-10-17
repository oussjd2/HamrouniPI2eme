<?php
namespace App\Core;
use DomainException;

final class Validation {
    public static function text(array $data, string $key, string $label, int $max, bool $required = true): string {
        $value = trim(is_scalar($data[$key] ?? '') ? (string) ($data[$key] ?? '') : '');
        if (($required && $value === '') || mb_strlen($value) > $max) { throw new DomainException("$label : champ obligatoire, maximum $max caractères."); }
        return $value;
    }
    public static function integer(array $data, string $key, string $label, int $min = 0, int $max = 100000): int {
        $value = $data[$key] ?? null;
        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < $min || (int) $value > $max) { throw new DomainException("$label : entier entre $min et $max attendu."); }
        return (int) $value;
    }
    public static function cents($raw, bool $positive = false): int {
        if (!is_scalar($raw)) { throw new DomainException('Montant incorrect.'); }
        $value = str_replace(',', '.', trim((string) $raw));
        if (!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $value)) { throw new DomainException('Montant : nombre positif, maximum 999999,99 et deux décimales.'); }
        [$whole, $fraction] = array_pad(explode('.', $value), 2, '0');
        $cents = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        if ($positive && $cents === 0) { throw new DomainException('Le tarif doit être supérieur à zéro.'); }
        return $cents;
    }
    public static function decimal(int $cents): string { return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100); }
    public static function date($value): string {
        if (!is_string($value)) { throw new DomainException('Date incorrecte.'); }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value || $value < '2000-01-01' || $value > '2100-12-31') { throw new DomainException('Date incorrecte (2000 à 2100).'); }
        return $value;
    }
}
