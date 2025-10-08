<?php

namespace App\Enums\Configurations;

enum SettingType: string
{
    case EMAIL = 'Email Settings';
    case PRODUCT = 'Product Settings';
    case ADMIN = 'Admin Settings';
    case PAYMENT = 'Payment Settings';
    case SOCIAL = 'Social Media Settings';
    case ALLOCATED_HOURS = 'Allocated Hours Settings';

    case CONTACT_US = 'Contact Us Settings';

    case COMMUNICATION  = 'Communication Settings';

    case DEFAULT_SALES_FUNNEL_SETTINGS = 'Default Sales Funnel Settings';

    case MAIL_SEND_SETTINGS = 'Mail Send Settings';

    case INVOICE_SETTINGS = 'Invoice Settings';


    case OTHER = 'Other Settings';

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Email Settings',
            self::PRODUCT => 'Product Settings',
            self::ADMIN => 'Admin Control',
            self::PAYMENT => 'Payment Integration',
            self::SOCIAL => 'Social Media Integration',
            self::CONTACT_US => 'Contact Us - Website Contact Page',
            self::ALLOCATED_HOURS => 'Allocated Hours Settings',
            self::COMMUNICATION => 'Communication Settings',
            self::OTHER => 'Other Settings',
            self::DEFAULT_SALES_FUNNEL_SETTINGS => 'Default Sales Funnel Settings',
            self::MAIL_SEND_SETTINGS => 'Mail Send Settings',
            self::INVOICE_SETTINGS => 'Invoice Settings',
        };
    }
}
