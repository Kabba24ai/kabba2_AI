<?php

namespace App\Enums\Communication;

enum SmsType: string
{
    case DELIVERY_DAY_BEFORE      = 'delivery_day_before';
    case DELIVERY_SAME_DAY        = 'delivery_same_day';
    case DELIVERY_SAME_DAY_COD    = 'delivery_same_day_cod';
    case RETURN_DAY_BEFORE        = 'return_day_before';
    case RETURN_SAME_DAY          = 'return_same_day';
    case SALES_FUNNEL_BEFORE      = 'sales_funnel_before';
    case SALES_FUNNEL_AFTER      = 'sales_funnel_after';
    case TERMS_AND_CONDITIONS     = 'terms_and_conditions';
    case NEW_CUSTOMER_SIGNUP_NOTIFICATION = 'new_customer_signup_notification';
    case COD_ORDER_NOTIFICATION   = 'cod_order_notification';
    case CARD_ORDER_NOTIFICATION  = 'card_order_notification';
    case POD_PAYMENT_LINK         = 'pod_payment_link';
    case POD_PAYMENT_REMINDER_1   = 'pod_payment_reminder_1';
    case POD_PAYMENT_REMINDER_2   = 'pod_payment_reminder_2';
    case POD_PAYMENT_REMINDER_3   = 'pod_payment_reminder_3';
    case POD_PAYMENT_REMINDER_4          = 'pod_payment_reminder_4';
    case POD_PAYMENT_LINK_MANUAL_RESEND  = 'pod_payment_link_manual_resend';
    case POD_FINAL_REMINDER              = 'pod_final_reminder';
    case POD_LAST_DITCH                  = 'pod_last_ditch';
}
