<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            //  Drop old/unnecessary columns
            $table->dropColumn([
                'account_number',
                'street_address',
                'main_phone',
                'main_email',
                'sales_name',
                'sales_phone',
                'sales_cell',
                'sales_email',
                'inside_sales_name',
                'inside_sales_phone',
                'inside_sales_cell',
                'inside_sales_email',
                'technical_name',
                'technical_phone',
                'technical_cell',
                'technical_email',
                'parts_name',
                'parts_phone',
                'parts_cell',
                'parts_email',
                'shipping_terms',
                'notes',
            ]);

            //  Add new columns
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');


            $table->string('address')->nullable()->after('website');


            // Add new FK columns
            $table->foreignId('state_id')->nullable()->after('city')->constrained('states')->nullOnDelete();
            $table->string('country')->nullable()->after('zip_code');

            // Replace old tax_id column (same name is fine)
            $table->string('tax_id')->nullable()->change();

            // Add category FK
            $table->foreignId('supplier_category_id')->nullable()->after('tax_id')->constrained('supplier_categories')->nullOnDelete();

            // Replace status enum
            $table->enum('status', ['Active', 'Inactive', 'Pending'])->default('Active')->change();

            // Add new business fields
            $table->string('payment_terms')->default('Net 30')->after('status')->change();
            $table->text('tags')->nullable()->after('payment_terms');

            // Add new contact fields
            $table->string('primary_contact_name')->nullable()->after('tags');
            $table->string('primary_contact_email')->nullable()->after('primary_contact_name');
            $table->string('primary_contact_phone')->nullable()->after('primary_contact_email');

            $table->string('secondary_contact_name')->nullable()->after('primary_contact_phone');
            $table->string('secondary_contact_email')->nullable()->after('secondary_contact_name');
            $table->string('secondary_contact_phone')->nullable()->after('secondary_contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Reverse additions
            $table->dropForeign(['state_id']);
            $table->dropForeign(['supplier_category_id']);

            $table->dropColumn([
                'email',
                'phone',
                'website',
                'address',
                'city',
                'state_id',
                'zip_code',
                'country',
                'supplier_category_id',
                'tags',
                'primary_contact_name',
                'primary_contact_email',
                'primary_contact_phone',
                'secondary_contact_name',
                'secondary_contact_email',
                'secondary_contact_phone',
            ]);
        });
    }
};
