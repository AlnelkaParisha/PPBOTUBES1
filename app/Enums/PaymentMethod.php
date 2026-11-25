<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Ewallet = 'ewallet';
    case Qris = 'qris';
    case CreditCard = 'credit_card';
    case Credit = 'credit'; // saldo kredit prorata, dipakai sistem, bukan pilihan user

    /** @return string[] metode yang boleh dipilih user saat membayar */
    public static function selectable(): array
    {
        return [
            self::BankTransfer->value,
            self::Ewallet->value,
            self::Qris->value,
            self::CreditCard->value,
        ];
    }
}
