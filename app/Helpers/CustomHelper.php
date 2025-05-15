<?php

namespace App\Helpers;

use Carbon\Carbon;

// Models
use App\Models\Customers\Customer;
use App\Models\Points\PointLedger;
use App\Services\NotificationService;

class CustomHelper
{

    /**
     * Create a new transaction in the point_ledger for a customer.
     *
     * @param object $objEvent The event object containing event details
     * @param int $customerId The customer ID
     * @return \App\Models\PointLedger|null
     */
    public static function createCreditTransaction($objEvent, $customerId)
    {
        // Convert event date to 'Y-m-d' format
        $eventDate = Carbon::createFromFormat('m-d-Y', $objEvent->date)->format('Y-m-d');

        // Check if a transaction already exists with the same customer ID, event date, and event day
        $existingTransaction = PointLedger::where('customer_id', $customerId)
            ->where('event_date', $eventDate)
            ->where('event_day', $objEvent->day)
            ->first();

        // If an existing transaction is found, return null (no points will be added)
        if ($existingTransaction) {
            return null; // No new transaction created
        }

        // Call adjustCustomerPoints to handle crediting points
        $customer = self::adjustCustomerPoints($customerId, 5, 'Credit');

        // Send notification using NotificationService
        $notificationService = new NotificationService();
        $notificationService->sendNotification(
            $customer->fcm_token,
            '5 Points Added to Your Account!',
            'Congratulations! 5 points have been successfully added to your account. Keep playing and earn more rewards!',
            $customerId,
        );

        // Create a new transaction in the point_ledger if no existing one is found
        return PointLedger::create([
            'customer_id' => $customerId,
            'transaction_type' => "Credit", // 'Credit' transaction type
            'transaction_date' => Carbon::now()->toDateString(), // Current date
            'event_date' => $eventDate, // Event date
            'event_day' => $objEvent->day, // Event day (e.g., 'Monday')
            'event_name' => $objEvent->name, // Event name
            'event_price' => $objEvent->price, // Event price
            'points' => 5, // Points to be credited
            'description' => null, // No description by default
        ]);
    }

    /**
     * Create a new transaction in the point_ledger for a customer.
     *
     * @param object $objEvent The event object containing event details
     * @param int $customerId The customer ID
     * @return \App\Models\PointLedger|null
     */
    public static function createCreditTournamentTransaction($objTournament,$customerId)
    {
        // Convert event date to 'Y-m-d' format
        $tournamentDate = Carbon::parse($objTournament->date_time)->format('Y-m-d');

        // Check if a transaction already exists with the same customer ID, event date, and event day
        $existingTransaction = PointLedger::where('customer_id', $customerId)
        ->where('event_date', $tournamentDate)
        ->where('event_day', "Tournament")
        ->first();

        // If an existing transaction is found, return null (no points will be added)
        if ($existingTransaction) {
            return null; // No new transaction created
        }

        // Call adjustCustomerPoints to handle crediting points
        $customer = self::adjustCustomerPoints($customerId, 1, 'Credit');

        // Send notification using NotificationService
        $notificationService = new NotificationService();
        $notificationService->sendNotification(
            $customer->fcm_token,
            '1 Points Added to Your Account!',
            'Congratulations! 1 points have been successfully added to your account. Keep playing and earn more rewards!',
            $customerId,
        );

        // Create a new transaction in the point_ledger if no existing one is found
        return PointLedger::create([
            'customer_id' => $customerId,
            'transaction_type' => "Credit", // 'Credit' transaction type
            'transaction_date' => Carbon::now()->toDateString(), // Current date
            'event_date' => $tournamentDate, // Event date
            'event_day' => "Tournament", // Event day (e.g., 'Monday')
            'event_name' => $objTournament->name, // Event name
            'event_price' => null, // Event price
            'points' => 1, // Points to be credited
            'description' => null, // No description by default
        ]);
    }

    /**
     * Adjust points for a customer (either add or subtract points based on the transaction type).
     *
     * @param int $customerId The customer ID
     * @param int $points The number of points to add or subtract
     * @param string $transactionType The type of transaction ('Credit' or 'Debit')
     * @return \App\Models\Customer The updated customer model
     */
    public static function adjustCustomerPoints($customerId, $points, $transactionType)
    {
        // Get the customer from the database
        $customer = Customer::findOrFail($customerId);

        // Handle 'Credit' transaction type (adding points)
        if ($transactionType === 'Credit') {
            $customer->points += $points; // Add points to the customer's balance
        }

        // Handle 'Debit' transaction type (subtracting points)
        if ($transactionType === 'Debit') {
            // Ensure points don't go below 0
            $customer->points = max(0, $customer->points - $points); // Prevent going below 0
        }

        // Save the updated customer record
        $customer->save();

        return $customer;
    }

    public static function createDebitTransaction($customerId, $description, $objProduct)
    {
        // Adjust customer points (debiting points)
        self::adjustCustomerPoints($customerId, $objProduct->points, 'Debit');

        // Create a new transaction in the point_ledger
        return PointLedger::create([
            'customer_id' => $customerId,
            'transaction_type' => 'Debit', // Debit transaction type
            'transaction_date' => Carbon::now()->toDateString(),
            'event_date' => null, // Not applicable for this transaction type
            'event_day' => now()->format('l'), // Not applicable for this transaction type
            'event_name' => null, // Not applicable for this transaction type
            'event_price' => null, // Not applicable for this transaction type
            'points' => -$objProduct->points, // Negative points to signify debit
            'description' => $description, // Add a description for the debit
            'product_id' => $objProduct->id, // Add a product_id for the debit
            'product_name' => $objProduct->name, // Add a product_name for the debit
            'product_points' => $objProduct->points, // Add a product_points for the debit
        ]);
    }

    /**
     * Create a new manual transaction in the point_ledger for a customer.
     *
     * @param int $customerId The customer ID
     * @param string $description Description for the transaction
     * @return \App\Models\PointLedger
     */
    public static function createAdminTransaction($points,$description,$customerId)
    {
        // Adjust the customer's points balance
        $customer = self::adjustCustomerPoints($customerId, $points, 'Credit');

        // Create a new transaction in the point_ledger table
        return PointLedger::create([
            'customer_id' => $customerId,
            'transaction_type' => "Credit", // Transaction type to indicate it's an admin-created credit
            'transaction_date' => Carbon::now()->toDateString(), // Current date
            'event_date' => Carbon::now()->toDateString(), // Use the current date for event date
            'event_day' => Carbon::now()->format('l'), // Day of the week (e.g., 'Monday')
            'event_name' => 'Points Added by Admin', // Fixed event name for admin transactions
            'event_price' => null, // No price associated with manual transactions
            'points' => $points, // Points to be credited
            'description' => $description, // Description provided by the admin
        ]);
    }

    /**
     * Create a new transaction in the point_ledger for an event-based customer credit.
     *
     * @param object $objEvent The event object containing event details
     * @param int $customerId The customer ID
     * @param int $points Number of points to credit
     * @return \App\Models\PointLedger|null
     */
    public static function createCreditEventTransaction($objEvent, $customerId, $points = 1)
    {
        try {
            // Convert event date to 'Y-m-d' format
            $eventDate = Carbon::createFromFormat('m-d-Y', $objEvent->date)->format('Y-m-d');


            // Adjust customer points
            $customer = self::adjustCustomerPoints($customerId, $points, 'Credit');

            // Ensure customer exists before proceeding
            if (!$customer) {
                \Log::error("Customer ID {$customerId} not found or failed to adjust points.");
                return null;
            }

            if (!empty($customer->referrer_by)) {
                self::creditReferralPoints($customer->referrer, $customer);
            }

            // Create a new transaction in the point_ledger
            return PointLedger::create([
                'customer_id' => $customerId,
                'transaction_type' => "Credit", // 'Credit' transaction type
                'transaction_date' => Carbon::now()->toDateString(), // Current date
                'event_date' => $eventDate, // Event date
                'event_day' => $objEvent->day, // Event day (e.g., 'Monday')
                'event_name' => $objEvent->name, // Event name
                'event_price' => $objEvent->price, // Event price
                'points' => $points, // Points to be credited
                'description' => "Credit for event participation", // Default description
            ]);

        } catch (\Exception $e) {
            \Log::error("Error in createCreditEventTransaction: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Credit referral points to a customer who referred a new user.
     *
     * @param int $referrerId The ID of the customer who referred the new user.
     * @param int $referredCustomerId The ID of the newly registered customer.
     * @return \App\Models\Points\PointLedger|null
     */
    public static function creditReferralPoints($referrer, $objCustomer)
    {
        try {

            // Points to be credited
            $points = 0.5;

            // Adjust referrer's points
            self::adjustCustomerPoints($referrer->id, $points, 'Credit');

            // Create a new transaction in the point_ledger
            $transaction = PointLedger::create([
                'customer_id' => $referrer->id,
                'transaction_type' => "Credit",
                'transaction_date' => Carbon::now()->toDateString(), // Current date
                'event_date' => Carbon::now()->toDateString(), // Current date
                'event_day' => Carbon::now()->format('l'), // Day of the week (e.g., 'Monday')
                'event_name' => "Referral Bonus", // Event name
                'event_price' => null,
                'points' => $points, // 5 points
                'description' => "Earned 0.5 points for referring {$objCustomer->name}", // Accurate message
            ]);

            // // Send notification to the referrer
            // $notificationService = new NotificationService();
            // $notificationService->sendNotification(
            //     $referrer->fcm_token,
            //     'Referral Bonus: 5 Points Added!',
            //     "Congratulations! You earned 5 points for referring {$objCustomer->name}. Keep sharing and earning rewards!",
            //     $referrer->id
            // );

            return $transaction;

        } catch (\Exception $e) {
            \Log::error("Error in creditReferralPoints: " . $e->getMessage());
            return null;
        }
    }
}
