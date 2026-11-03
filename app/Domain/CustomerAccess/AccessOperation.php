<?php

namespace App\Domain\CustomerAccess;

enum AccessOperation: string
{
    case Payment = 'payment';
    case Invoice = 'invoice';
    case CancelPayment = 'cancel-payment';
    case Manage = 'manage';
    case CancelBooking = 'cancel-booking';
    case Reschedule = 'reschedule';
    case Review = 'review';
}
