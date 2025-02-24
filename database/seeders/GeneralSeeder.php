<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Tax;
use App\Models\IncomeCategory;
use App\Models\ExpenseCategory;
use App\Models\ProductCategory;
use App\Models\Project;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class GeneralSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::factory()->create([
            'name' => 'Admin',
            'email' => 'andreupp38@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $clientUser = User::factory()->create([
            'name' => 'Client',
            'email' => 'dohaaliat0@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        $iva = Tax::factory()->create(['name' => 'IVA', 'rate' => 21]);
        $irpf = Tax::factory()->create(['name' => 'IRPF', 'rate' => 15]);
        $incomeCategories = IncomeCategory::factory(20)->create();
        $expenseCategories = ExpenseCategory::factory(20)->create();
        $productCategories = collect();

        $users = [$adminUser, $clientUser];

        foreach ($users as $user) {
            $userProductCategories = ProductCategory::factory(20)->create(['user_id' => $user->id]);
            $productCategories = $productCategories->concat($userProductCategories);

            $company = Company::factory()->create(['user_id' => $user->id]);

            Client::factory(5)->create(['user_id' => $user->id])->each(function ($client) use ($company, $iva, $irpf, $userProductCategories) {
                $project = Project::factory()->create([
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                ]);

                Invoice::factory(3)->create([
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'project_id' => $project->id,
                ])->each(function ($invoice) use ($iva, $irpf, $userProductCategories) {
                    Product::factory(5)->create([
                        'invoice_id' => $invoice->id,
                        'tax_id' => rand(0, 1) ? $iva->id : $irpf->id,
                        'category_id' => $userProductCategories->random()->id,
                    ]);

                    $subtotal = $invoice->products->sum(function ($product) {
                        return $product->price * $product->quantity;
                    });
                    $taxAmount = $invoice->products->sum(function ($product) {
                        return $product->price * $product->quantity * ($product->tax->rate / 100);
                    });
                    $invoice->update([
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'total' => $subtotal + $taxAmount,
                    ]);
                });
            });

            Income::factory(10)->create([
                'category_id' => $incomeCategories->random()->id,
                'id_company' => $company->id,
                'date' => Carbon::create(2025, 2, rand(1, 28))->format('Y-m-d'),
            ])->each(function ($income) {
                $income->category->update(['user_id' => $income->company->user_id]);
            });

            Expense::factory(10)->create([
                'category_id' => $expenseCategories->random()->id,
                'id_company' => $company->id,
                'date' => Carbon::create(2025, 2, rand(1, 28))->format('Y-m-d'),
            ])->each(function ($expense) {
                $expense->category->update(['user_id' => $expense->company->user_id]);
            });
        }
    }
}
