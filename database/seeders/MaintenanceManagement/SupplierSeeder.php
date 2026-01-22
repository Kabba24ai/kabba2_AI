<?php

namespace Database\Seeders\MaintenanceManagement;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceManagement\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [

            [
                'name' => 'ABC Industrial Supplies',
                'email' => 'sales@abcindustrial.com',
                'phone' => '+1 312-555-0101',
                'address' => '1200 Industrial Ave, Chicago, IL',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 30',

                'primary_contact_name' => 'John Miller',
                'primary_contact_email' => 'john.miller@abcindustrial.com',
                'primary_contact_phone' => '+1 312-555-1001',

                'inside_sales_name' => 'Sarah Thompson',
                'inside_sales_email' => 'sarah.thompson@abcindustrial.com',
                'inside_sales_phone' => '+1 312-555-1002',

                'technical_support_name' => 'David Chen',
                'technical_support_email' => 'support@abcindustrial.com',
                'technical_support_phone' => '+1 312-555-1003',

                'billing_contact_name' => 'Emily Rogers',
                'billing_contact_email' => 'billing@abcindustrial.com',
                'billing_contact_phone' => '+1 312-555-1004',
            ],

            [
                'name' => 'Global Maintenance Co.',
                'email' => 'info@globalmaintenance.com',
                'phone' => '+1 214-555-0102',
                'address' => '450 Service Rd, Dallas, TX',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 45',

                'primary_contact_name' => 'Michael Brown',
                'primary_contact_email' => 'michael.brown@globalmaintenance.com',
                'primary_contact_phone' => '+1 214-555-2001',

                'inside_sales_name' => 'Laura White',
                'inside_sales_email' => 'laura.white@globalmaintenance.com',
                'inside_sales_phone' => '+1 214-555-2002',

                'technical_support_name' => 'Kevin Patel',
                'technical_support_email' => 'support@globalmaintenance.com',
                'technical_support_phone' => '+1 214-555-2003',

                'billing_contact_name' => 'Rachel Green',
                'billing_contact_email' => 'billing@globalmaintenance.com',
                'billing_contact_phone' => '+1 214-555-2004',
            ],

            [
                'name' => 'Prime Tools & Hardware',
                'email' => 'orders@primetools.com',
                'phone' => '+1 602-555-0103',
                'address' => '789 Tool St, Phoenix, AZ',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 15',

                'primary_contact_name' => 'Daniel Lopez',
                'primary_contact_email' => 'daniel.lopez@primetools.com',
                'primary_contact_phone' => '+1 602-555-3001',

                'inside_sales_name' => 'Amanda King',
                'inside_sales_email' => 'amanda.king@primetools.com',
                'inside_sales_phone' => '+1 602-555-3002',

                'technical_support_name' => 'Chris Evans',
                'technical_support_email' => 'support@primetools.com',
                'technical_support_phone' => '+1 602-555-3003',

                'billing_contact_name' => 'Olivia Scott',
                'billing_contact_email' => 'billing@primetools.com',
                'billing_contact_phone' => '+1 602-555-3004',
            ],

            [
                'name' => 'Metro Electrical Supply',
                'email' => 'contact@metroelectrical.com',
                'phone' => '+1 718-555-0104',
                'address' => '88 Voltage Blvd, Brooklyn, NY',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 30',

                'primary_contact_name' => 'Anthony Russo',
                'primary_contact_email' => 'anthony.russo@metroelectrical.com',
                'primary_contact_phone' => '+1 718-555-4001',

                'inside_sales_name' => 'Jessica Moore',
                'inside_sales_email' => 'jessica.moore@metroelectrical.com',
                'inside_sales_phone' => '+1 718-555-4002',

                'technical_support_name' => 'Brian Lee',
                'technical_support_email' => 'support@metroelectrical.com',
                'technical_support_phone' => '+1 718-555-4003',

                'billing_contact_name' => 'Natalie Adams',
                'billing_contact_email' => 'billing@metroelectrical.com',
                'billing_contact_phone' => '+1 718-555-4004',
            ],

            [
                'name' => 'Northern Safety Equipment',
                'email' => 'support@northernsafety.com',
                'phone' => '+1 612-555-0105',
                'address' => '56 Safety Way, Minneapolis, MN',
                'country' => 'USA',
                'status' => 'inactive',
                'payment_terms' => 'Net 60',

                'primary_contact_name' => 'Mark Jensen',
                'primary_contact_email' => 'mark.jensen@northernsafety.com',
                'primary_contact_phone' => '+1 612-555-5001',

                'inside_sales_name' => 'Linda Park',
                'inside_sales_email' => 'linda.park@northernsafety.com',
                'inside_sales_phone' => '+1 612-555-5002',

                'technical_support_name' => 'Eric Wilson',
                'technical_support_email' => 'support@northernsafety.com',
                'technical_support_phone' => '+1 612-555-5003',

                'billing_contact_name' => 'Karen Olson',
                'billing_contact_email' => 'billing@northernsafety.com',
                'billing_contact_phone' => '+1 612-555-5004',
            ],

            [
                'name' => 'Pacific Mechanical Parts',
                'email' => 'sales@pacificmechanical.com',
                'phone' => '+1 415-555-0106',
                'address' => '901 Harbor Rd, San Francisco, CA',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 30',

                'primary_contact_name' => 'Steven Kim',
                'primary_contact_email' => 'steven.kim@pacificmechanical.com',
                'primary_contact_phone' => '+1 415-555-6001',

                'inside_sales_name' => 'Rachel Wong',
                'inside_sales_email' => 'rachel.wong@pacificmechanical.com',
                'inside_sales_phone' => '+1 415-555-6002',

                'technical_support_name' => 'Tom Baker',
                'technical_support_email' => 'support@pacificmechanical.com',
                'technical_support_phone' => '+1 415-555-6003',

                'billing_contact_name' => 'Sophie Turner',
                'billing_contact_email' => 'billing@pacificmechanical.com',
                'billing_contact_phone' => '+1 415-555-6004',
            ],

            [
                'name' => 'Summit Facility Services',
                'email' => 'admin@summitfacility.com',
                'phone' => '+1 303-555-0107',
                'address' => '77 Alpine Dr, Denver, CO',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 45',

                'primary_contact_name' => 'Ryan Cooper',
                'primary_contact_email' => 'ryan.cooper@summitfacility.com',
                'primary_contact_phone' => '+1 303-555-7001',

                'inside_sales_name' => 'Megan Lewis',
                'inside_sales_email' => 'megan.lewis@summitfacility.com',
                'inside_sales_phone' => '+1 303-555-7002',

                'technical_support_name' => 'Jason Hill',
                'technical_support_email' => 'support@summitfacility.com',
                'technical_support_phone' => '+1 303-555-7003',

                'billing_contact_name' => 'Paula Reed',
                'billing_contact_email' => 'billing@summitfacility.com',
                'billing_contact_phone' => '+1 303-555-7004',
            ],

            [
                'name' => 'Elite HVAC Solutions',
                'email' => 'service@elitehvac.com',
                'phone' => '+1 404-555-0108',
                'address' => '310 Cooling Ln, Atlanta, GA',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 30',

                'primary_contact_name' => 'Andrew Collins',
                'primary_contact_email' => 'andrew.collins@elitehvac.com',
                'primary_contact_phone' => '+1 404-555-8001',

                'inside_sales_name' => 'Nicole Perez',
                'inside_sales_email' => 'nicole.perez@elitehvac.com',
                'inside_sales_phone' => '+1 404-555-8002',

                'technical_support_name' => 'Robert Allen',
                'technical_support_email' => 'support@elitehvac.com',
                'technical_support_phone' => '+1 404-555-8003',

                'billing_contact_name' => 'Hannah Brooks',
                'billing_contact_email' => 'billing@elitehvac.com',
                'billing_contact_phone' => '+1 404-555-8004',
            ],

            [
                'name' => 'Urban Plumbing Supplies',
                'email' => 'orders@urbanplumbing.com',
                'phone' => '+1 213-555-0109',
                'address' => '142 Pipe St, Los Angeles, CA',
                'country' => 'USA',
                'status' => 'inactive',
                'payment_terms' => 'Net 75',

                'primary_contact_name' => 'Carlos Mendoza',
                'primary_contact_email' => 'carlos.mendoza@urbanplumbing.com',
                'primary_contact_phone' => '+1 213-555-9001',

                'inside_sales_name' => 'Stephanie Young',
                'inside_sales_email' => 'stephanie.young@urbanplumbing.com',
                'inside_sales_phone' => '+1 213-555-9002',

                'technical_support_name' => 'James Foster',
                'technical_support_email' => 'support@urbanplumbing.com',
                'technical_support_phone' => '+1 213-555-9003',

                'billing_contact_name' => 'Vanessa Ortiz',
                'billing_contact_email' => 'billing@urbanplumbing.com',
                'billing_contact_phone' => '+1 213-555-9004',
            ],

            [
                'name' => 'Evergreen Industrial Services',
                'email' => 'info@evergreenindustrial.com',
                'phone' => '+1 206-555-0110',
                'address' => '600 Greenway Ave, Seattle, WA',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 60',

                'primary_contact_name' => 'Peter Anderson',
                'primary_contact_email' => 'peter.anderson@evergreenindustrial.com',
                'primary_contact_phone' => '+1 206-555-1001',

                'inside_sales_name' => 'Emma Johnson',
                'inside_sales_email' => 'emma.johnson@evergreenindustrial.com',
                'inside_sales_phone' => '+1 206-555-1002',

                'technical_support_name' => 'Samuel Nguyen',
                'technical_support_email' => 'support@evergreenindustrial.com',
                'technical_support_phone' => '+1 206-555-1003',

                'billing_contact_name' => 'Grace Miller',
                'billing_contact_email' => 'billing@evergreenindustrial.com',
                'billing_contact_phone' => '+1 206-555-1004',
            ],

            [
                'name' => 'Titan Heavy Equipment',
                'email' => 'sales@titanheavyeq.com',
                'phone' => '+1 713-555-0111',
                'address' => '999 Iron Rd, Houston, TX',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 45',

                'primary_contact_name' => 'Jonathan Reed',
                'primary_contact_email' => 'jonathan.reed@titanheavyeq.com',
                'primary_contact_phone' => '+1 713-555-1101',

                'inside_sales_name' => 'Melissa Carter',
                'inside_sales_email' => 'melissa.carter@titanheavyeq.com',
                'inside_sales_phone' => '+1 713-555-1102',

                'technical_support_name' => 'Nathan Scott',
                'technical_support_email' => 'support@titanheavyeq.com',
                'technical_support_phone' => '+1 713-555-1103',

                'billing_contact_name' => 'Lauren Phillips',
                'billing_contact_email' => 'billing@titanheavyeq.com',
                'billing_contact_phone' => '+1 713-555-1104',
            ],

            [
                'name' => 'Blue Ridge Facility Supplies',
                'email' => 'contact@blueridgesupplies.com',
                'phone' => '+1 828-555-0112',
                'address' => '21 Ridge View, Asheville, NC',
                'country' => 'USA',
                'status' => 'active',
                'payment_terms' => 'Net 30',

                'primary_contact_name' => 'William Parker',
                'primary_contact_email' => 'william.parker@blueridgesupplies.com',
                'primary_contact_phone' => '+1 828-555-1201',

                'inside_sales_name' => 'Julia Bennett',
                'inside_sales_email' => 'julia.bennett@blueridgesupplies.com',
                'inside_sales_phone' => '+1 828-555-1202',

                'technical_support_name' => 'Adam Reynolds',
                'technical_support_email' => 'support@blueridgesupplies.com',
                'technical_support_phone' => '+1 828-555-1203',

                'billing_contact_name' => 'Claire Watson',
                'billing_contact_email' => 'billing@blueridgesupplies.com',
                'billing_contact_phone' => '+1 828-555-1204',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['name' => $supplier['name']],
                $supplier
            );
        }
    }
}
