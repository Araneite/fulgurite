<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        
        $customers = [
            [
                'customer' => [
                    'name' => 'Acme Corporation',
                    'legal_name' => 'Acme Corporation SAS',
                    'registration_number' => '85274196300018',
                    'vat_number' => 'FR12852741963',
                    'website' => 'https://acme.example',
                    'industry' => 'SaaS',
                    'status' => 'active',
                    'metadata' => [
                        'tier' => 'enterprise',
                        'support_level' => 'premium',
                    ],
                ],
                'billing_profile' => [
                    'company_name' => 'Acme Corporation SAS',
                    'billing_email' => 'billing@acme.example',
                    'address_line1' => '12 rue de la Paix',
                    'address_line2' => 'Batiment B',
                    'postal_code' => '75002',
                    'city' => 'Paris',
                    'state' => null,
                    'country' => 'FR',
                    'currency' => 'EUR',
                    'language' => 'fr',
                    'payment_terms' => 'net_30',
                    'billing_reference' => 'ACME-OPS-2026',
                    'reverse_charge_vat' => false,
                    'external_billing_provider' => 'stripe',
                    'external_billing_id' => 'cus_acme_demo',
                    'metadata' => [
                        'invoice_footer' => 'Merci de rappeler la reference ACME-OPS-2026.',
                    ],
                ],
                'billing_contacts' => [
                    [
                        'name' => 'Marie Laurent',
                        'email' => 'marie.laurent@acme.example',
                        'phone_number' => 142000000,
                        'phone_extension' => null,
                        'role' => 'finance_manager',
                        'is_primary' => true,
                        'receives_invoices' => true,
                        'receives_payment_reminders' => true,
                        'language' => 'fr',
                        'metadata' => null,
                    ],
                    [
                        'name' => 'Comptabilite Acme',
                        'email' => 'accounting@acme.example',
                        'phone_number' => null,
                        'phone_extension' => null,
                        'role' => 'accounting',
                        'is_primary' => false,
                        'receives_invoices' => true,
                        'receives_payment_reminders' => false,
                        'language' => 'fr',
                        'metadata' => [
                            'notes' => 'Adresse generique du service comptabilite.',
                        ],
                    ],
                ],
            ],
            [
                'customer' => [
                    'name' => 'Northwind Labs',
                    'legal_name' => 'Northwind Labs SARL',
                    'registration_number' => '74125896300022',
                    'vat_number' => 'FR45741258963',
                    'website' => 'https://northwind.example',
                    'industry' => 'HealthTech',
                    'status' => 'active',
                    'metadata' => [
                        'tier' => 'business',
                        'support_level' => 'standard',
                    ],
                ],
                'billing_profile' => [
                    'company_name' => 'Northwind Labs SARL',
                    'billing_email' => 'finance@northwind.example',
                    'address_line1' => '8 avenue des Sciences',
                    'address_line2' => null,
                    'postal_code' => '69003',
                    'city' => 'Lyon',
                    'state' => null,
                    'country' => 'FR',
                    'currency' => 'EUR',
                    'language' => 'fr',
                    'payment_terms' => 'net_15',
                    'billing_reference' => 'NORTHWIND-2026',
                    'reverse_charge_vat' => false,
                    'external_billing_provider' => 'pennylane',
                    'external_billing_id' => 'nw_demo_001',
                    'metadata' => null,
                ],
                'billing_contacts' => [
                    [
                        'name' => 'Thomas Bernard',
                        'email' => 'thomas.bernard@northwind.example',
                        'phone_number' => 478000000,
                        'phone_extension' => null,
                        'role' => 'procurement',
                        'is_primary' => true,
                        'receives_invoices' => true,
                        'receives_payment_reminders' => true,
                        'language' => 'fr',
                        'metadata' => null,
                    ],
                ],
            ],
            [
                'customer' => [
                    'name' => 'Blue Orbit',
                    'legal_name' => 'Blue Orbit SAS',
                    'registration_number' => '96385274100031',
                    'vat_number' => 'FR73963852741',
                    'website' => 'https://blueorbit.example',
                    'industry' => 'E-commerce',
                    'status' => 'inactive',
                    'metadata' => [
                        'tier' => 'starter',
                        'support_level' => 'basic',
                    ],
                ],
                'billing_profile' => [
                    'company_name' => 'Blue Orbit SAS',
                    'billing_email' => 'billing@blueorbit.example',
                    'address_line1' => '24 boulevard Maritime',
                    'address_line2' => null,
                    'postal_code' => '33000',
                    'city' => 'Bordeaux',
                    'state' => null,
                    'country' => 'FR',
                    'currency' => 'EUR',
                    'language' => 'fr',
                    'payment_terms' => 'due_on_receipt',
                    'billing_reference' => null,
                    'reverse_charge_vat' => false,
                    'external_billing_provider' => null,
                    'external_billing_id' => null,
                    'metadata' => null,
                ],
                'billing_contacts' => [
                    [
                        'name' => 'Claire Moreau',
                        'email' => 'claire.moreau@blueorbit.example',
                        'phone_number' => 556000000,
                        'phone_extension' => null,
                        'role' => 'accounting',
                        'is_primary' => true,
                        'receives_invoices' => true,
                        'receives_payment_reminders' => true,
                        'language' => 'fr',
                        'metadata' => null,
                    ],
                ],
            ],
        ];
        
        foreach ($customers as $entry) {
            $customerData = $entry['customer'];
            $slug = Str::slug($customerData['name']);
            
            DB::table('fg_customers')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $customerData['name'],
                    'slug' => $slug,
                    'legal_name' => $customerData['legal_name'],
                    'registration_number' => $customerData['registration_number'],
                    'vat_number' => $customerData['vat_number'],
                    'website' => $customerData['website'],
                    'industry' => $customerData['industry'],
                    'status' => $customerData['status'],
                    'metadata' => json_encode($customerData['metadata']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            
            $customer = DB::table('fg_customers')->where('slug', $slug)->first();
            
            DB::table('fg_customer_billing_profiles')->updateOrInsert(
                ['customer_id' => $customer->id],
                array_merge($entry['billing_profile'], [
                    'customer_id' => $customer->id,
                    'metadata' => json_encode($entry['billing_profile']['metadata']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
            
            foreach ($entry['billing_contacts'] as $contact) {
                DB::table('fg_customer_billing_contacts')->updateOrInsert(
                    [
                        'customer_id' => $customer->id,
                        'email' => $contact['email'],
                    ],
                    array_merge($contact, [
                        'customer_id' => $customer->id,
                        'metadata' => json_encode($contact['metadata']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
            }
        }
    }
}
